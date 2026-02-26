<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class EmulationController extends Controller
{
    private string $appRoot;
    private string $jobsDir;

    public function __construct()
    {
        $this->appRoot = config('emulation.app_root') ?? base_path('../');
        $this->jobsDir = config('emulation.jobs_dir') ?? base_path('../jobs');
    }

    /**
     * GET / - Show the dashboard.
     */
    public function index()
    {
        $payloads = $this->listPayloads();
        $scripts  = $this->listScripts();
        $settings = $this->loadSettings();

        return view('dashboard', compact('payloads', 'scripts', 'settings'));
    }

    /**
     * POST /payload - Save a job configuration with encrypted credentials.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'target_url'        => 'nullable|url',
            'username'          => 'nullable|string',
            'password'          => 'nullable|string',
            'script_path'       => 'nullable|string',
            'script_mode'       => 'nullable|in:pagecast,existing',
            'needs_developer'   => 'nullable',
            'token_keys'        => 'nullable|array',
            'token_keys.*'      => 'nullable|string',
            'token_values'      => 'nullable|array',
            'token_values.*'    => 'nullable|string',
            's3_output_bucket'  => 'nullable|string',
            's3_output_prefix'  => 'nullable|string',
            'payload_name'      => 'required|string|regex:/^[a-zA-Z0-9_\-]+$/',
        ]);

        // -- Load existing JSON if present (to preserve credentials / tokens) --
        $filename = $validated['payload_name'] . '.json';
        $filepath = $this->jobsDir . DIRECTORY_SEPARATOR . $filename;
        $existing = null;
        if (file_exists($filepath)) {
            $existing = json_decode(file_get_contents($filepath), true);
        }

        // Also search all jobs for a config that uses the same script
        if (!$existing && !empty($validated['script_path'])) {
            $existing = $this->findCompanionConfig($validated['script_path']);
        }

        // -- Credentials: encrypt new ones, or preserve existing --
        $credentials = null;
        if (!empty($validated['username']) && !empty($validated['password'])) {
            // User entered new credentials → encrypt them
            $credentials = $this->encryptCredentials(
                $validated['username'],
                $validated['password']
            );

            if ($credentials === null) {
                return back()
                    ->withInput()
                    ->withErrors(['credentials' => 'Encryption failed. Check .emulation_key file and PHP openssl extension.']);
            }
        } elseif ($existing && !empty($existing['credentials'])) {
            // User left fields blank → preserve existing encrypted credentials
            $credentials = $existing['credentials'];
        }

        // -- Tokens --
        $tokens = $this->parseTokenArrays(
            $validated['token_keys'] ?? [],
            $validated['token_values'] ?? []
        );

        // Inject target_url into tokens so scripts can use tokens["target_url"]
        $targetUrl = $validated['target_url'] ?? null;

        // If target_url is blank, try to preserve from existing
        if (empty($targetUrl) && $existing && !empty($existing['target_url'])) {
            $targetUrl = $existing['target_url'];
        }

        if (!empty($targetUrl)) {
            $tokens['target_url'] = $targetUrl;
        }

        // Merge with existing token values for any keys that were left blank
        if ($existing && !empty($existing['tokens']) && is_array($existing['tokens'])) {
            foreach ($existing['tokens'] as $k => $v) {
                if (!isset($tokens[$k]) || $tokens[$k] === '') {
                    $tokens[$k] = $v;
                }
            }
        }

        $needsDeveloper = !empty($validated['needs_developer']);
        $scriptMode     = $validated['script_mode'] ?? '';

        $status = 'ready';
        if ($needsDeveloper) {
            $status = 'needs_developer';
        } elseif ($scriptMode === 'pagecast') {
            $status = 'needs_recording';
        } elseif (empty($validated['script_path'])) {
            $status = 'needs_script';
        }

        $payload = [
            'target_url'       => $targetUrl,
            'script_path'      => $validated['script_path'] ?? null,
            'script_mode'      => $scriptMode,
            'status'           => $status,
            'tokens'           => (object) $tokens,
            'credentials'      => $credentials,
            's3_output_bucket' => $validated['s3_output_bucket'] ?? null,
            's3_output_prefix' => $validated['s3_output_prefix'] ?? null,
        ];

        $payload = array_filter($payload, fn($v) => $v !== null && $v !== '');

        file_put_contents($filepath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // If "Run Job" was clicked, save then immediately execute
        if ($request->input('action') === 'save_and_run' && $status === 'ready') {
            return $this->run($validated['payload_name']);
        }

        if ($request->input('action') === 'save_and_launch' && $status === 'ready') {
            return redirect()->route('payload.launch', $validated['payload_name']);
        }

        $message = match($status) {
            'needs_developer'  => "Configuration saved: {$filename}. Share this file with your developer.",
            'needs_recording'  => "Configuration saved: {$filename}. Record your navigation with PageCast to complete setup.",
            'needs_script'     => "Configuration saved: {$filename}. A navigation script is still needed.",
            default            => "Configuration saved: {$filename}",
        };

        return redirect('/')->with('success', $message);
    }

    /**
     * POST /payload/upload - Upload a .json config or .py navigation script.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'payload_file' => 'required|file|max:512',
        ]);

        $file = $request->file('payload_file');
        $ext  = strtolower($file->getClientOriginalExtension());
        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name);

        // -- Python navigation script --
        if ($ext === 'py') {
            $filename = $name . '.py';
            $file->move($this->jobsDir, $filename);

            return redirect('/')->with('success', "Script uploaded: {$filename}");
        }

        // -- JSON payload config --
        if ($ext === 'json' || $ext === 'txt') {
            $contents = file_get_contents($file->getRealPath());
            $decoded  = json_decode($contents, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->withErrors(['payload_file' => 'Invalid JSON file.']);
            }

            $filename = $name . '.json';
            $filepath = $this->jobsDir . DIRECTORY_SEPARATOR . $filename;
            file_put_contents($filepath, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return redirect('/')->with('success', "Configuration uploaded: {$filename}");
        }

        return back()->withErrors(['payload_file' => 'Unsupported file type. Upload a .py script or .json configuration.']);
    }


    /**
     * GET /script/{name}/tokens - Deep-analyse a .py script.
     *
     * Extracts:
     *   tokens["key"], tokens.get("key", "default")  - token names + default values
     *   Hardcoded https:// URLs                       - suggested target_url
     *   context["target_url"]                         - uses_target_url flag
     *   context["credentials"], creds.get(            - uses_credentials flag
     *   Companion .json (same basename)               - saved values for all fields
     */
    public function scriptTokens(string $name)
    {
        $filepath = $this->jobsDir . DIRECTORY_SEPARATOR . $name;

        $empty = [
            'tokens'            => [],
            'uses_target_url'   => false,
            'uses_credentials'  => false,
            'suggested_url'     => null,
            'has_saved_creds'   => false,
        ];

        if (!file_exists($filepath) || !str_ends_with($name, '.py')) {
            return response()->json($empty, 200);
        }

        $source = file_get_contents($filepath);

        // -- 1. Token names (bracket access): tokens["key"] --
        preg_match_all('/tokens\s*\[\s*["\']([^"\']+)["\']\s*\]/', $source, $m1);

        // -- 2. Token names + defaults (.get access) --
        //    tokens.get("key", "default")  or  tokens.get("key")
        preg_match_all(
            '/tokens\s*\.\s*get\s*\(\s*["\']([^"\']+)["\']\s*(?:,\s*["\']([^"\']*)["\'])?\s*\)/',
            $source, $m2
        );

        // Merge names, preserve defaults
        $allNames = array_values(array_unique(array_merge($m1[1] ?? [], $m2[1] ?? [])));
        $defaults = [];
        if (!empty($m2[1])) {
            foreach ($m2[1] as $i => $key) {
                $val = $m2[2][$i] ?? '';
                if ($val !== '') {
                    $defaults[$key] = $val;
                }
            }
        }

        // Build tokens array with defaults
        $tokens = [];
        foreach ($allNames as $tName) {
            $tokens[] = [
                'name'    => $tName,
                'default' => $defaults[$tName] ?? null,
            ];
        }

        // -- 3. Target URL detection --
        $usesTargetUrl = (bool) preg_match('/context\s*\[\s*["\']target_url["\']\s*\]/', $source)
                      || in_array('target_url', $allNames, true);

        // -- 4. Credentials detection --
        $usesCredentials = (bool) preg_match('/context\s*\[\s*["\']credentials["\']\s*\]/', $source)
                        || (bool) preg_match('/creds\s*[\.\[]/', $source);

        // -- 5. Extract hardcoded URLs from the script --
        preg_match_all('/"(https?:\/\/[^"]+)"/', $source, $urlM1);
        preg_match_all("/\'(https?:\/\/[^']+)\'/", $source, $urlM2);
        $urls = array_values(array_unique(array_merge($urlM1[1] ?? [], $urlM2[1] ?? [])));

        // Suggest target_url: prefer a /login URL, else derive from first URL's domain
        $suggestedUrl = null;
        if ($usesTargetUrl && !empty($urls)) {
            foreach ($urls as $u) {
                if (preg_match('/\/login/i', $u)) {
                    $suggestedUrl = $u;
                    break;
                }
            }
            if (!$suggestedUrl) {
                $parsed = parse_url($urls[0]);
                if ($parsed) {
                    $suggestedUrl = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '') . '/login';
                }
            }
        }

        // -- 6. Check for companion .json with saved values --
        //    Search all json files for one with matching script_path
        $companion = $this->findCompanionConfig($name);
        $hasSavedCreds = false;

        if ($companion) {
            // Override suggested URL with saved value
            if (!empty($companion['target_url'])) {
                $suggestedUrl = $companion['target_url'];
            }

            // Override token defaults with saved values
            if (!empty($companion['tokens']) && is_array($companion['tokens'])) {
                foreach ($tokens as &$tok) {
                    if (isset($companion['tokens'][$tok['name']])) {
                        $tok['default'] = $companion['tokens'][$tok['name']];
                    }
                }
                unset($tok);
            }

            // Flag that credentials are already saved (encrypted)
            if (!empty($companion['credentials']['username']) && !empty($companion['credentials']['password'])) {
                $hasSavedCreds = true;
            }
        }

        return response()->json([
            'tokens'            => $tokens,
            'uses_target_url'   => $usesTargetUrl,
            'uses_credentials'  => $usesCredentials,
            'suggested_url'     => $suggestedUrl,
            'has_saved_creds'   => $hasSavedCreds,
        ]);
    }


    /**
     * GET /script/{name}/content - Return the source code of a .py script for preview.
     */
    public function scriptContent(string $name)
    {
        $filepath = $this->jobsDir . DIRECTORY_SEPARATOR . $name;

        if (!file_exists($filepath) || !str_ends_with($name, '.py')) {
            return response()->json(['content' => '', 'name' => $name], 404);
        }

        $source = file_get_contents($filepath);

        return response()->json(['content' => $source, 'name' => $name]);
    }

    /**
     * GET /payload/{name} - View a payload file as JSON.
     */
    public function show(string $name)
    {
        $filepath = $this->jobsDir . DIRECTORY_SEPARATOR . $name . '.json';

        if (!file_exists($filepath)) {
            abort(404, 'Payload not found.');
        }

        $content = json_decode(file_get_contents($filepath), true);

        return response()->json($content, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * DELETE /payload/{name} - Delete a payload file.
     */
    public function destroy(string $name)
    {
        $filepath = $this->jobsDir . DIRECTORY_SEPARATOR . $name . '.json';

        if (file_exists($filepath)) {
            unlink($filepath);
        }

        return redirect('/')->with('success', "Payload deleted: {$name}.json");
    }

    /**
     * POST /payload/{name}/run - Execute a payload in the background (headless).
     *
     * On Windows/WAMP, Apache runs in a service session so Chrome
     * cannot display a visible window. Use the "Launch" button
     * instead for visible execution.
     */
    public function run(string $name)
    {
        $filepath = $this->jobsDir . DIRECTORY_SEPARATOR . $name . '.json';

        if (!file_exists($filepath)) {
            return back()->withErrors(['run' => "Payload not found: {$name}.json"]);
        }

        $relativePath = 'jobs/' . $name . '.json';

        try {
            $startTime = microtime(true);
            $uv = $this->getUvCommand();

            $result = Process::path($this->appRoot)
                ->timeout(300)
                ->run([$uv, 'run', 'python', 'run.py', $relativePath]);

            $elapsed = round(microtime(true) - $startTime);
            $output  = $result->output() . "\n" . $result->errorOutput();

            $success = $result->successful()
                && !str_contains($output, '"status": "error"');

            $logEntries  = $this->parseLogOutput($output);
            $statusText  = $success
                ? "Job completed successfully - {$elapsed} seconds"
                : "Job failed after {$elapsed} seconds";

            return redirect('/')
                ->with('success', $success ? "Job completed: {$name}.json" : null)
                ->withErrors($success ? [] : ['run' => "Job failed: {$name}.json"])
                ->with('job_log', $logEntries)
                ->with('job_success', $success)
                ->with('job_status_text', $statusText);

        } catch (\Exception $e) {
            $hint = str_contains($e->getMessage(), 'cannot find') || str_contains($e->getMessage(), 'not recognized')
                ? ' Set the full path to uv in Settings (gear icon).'
                : '';

            return redirect('/')
                ->withErrors(['run' => 'Failed to start job: ' . $e->getMessage() . $hint])
                ->with('job_log', [[
                    'time'  => now()->format('H:i:s'),
                    'icon'  => '&#x274C;',
                    'html'  => '<strong>Failed to start</strong> ' . e($e->getMessage() . $hint),
                ]])
                ->with('job_success', false)
                ->with('job_status_text', 'Job could not be started');
        }
    }

    /**
     * GET /payload/{name}/launch - Download a .bat file that runs
     * the job in the user's desktop session with a VISIBLE browser.
     *
     * The user double-clicks the .bat → Chrome opens on their screen →
     * they watch the automation → window stays open to show results.
     */
    public function launch(string $name)
    {
        $filepath = $this->jobsDir . DIRECTORY_SEPARATOR . $name . '.json';

        if (!file_exists($filepath)) {
            return back()->withErrors(['run' => "Payload not found: {$name}.json"]);
        }

        $uv = $this->getUvCommand();
        $relativePath = 'jobs\\' . $name . '.json';

        $bat  = "@echo off\r\n";
        $bat .= "title EmulationApp - {$name}\r\n";
        $bat .= "color 0A\r\n";
        $bat .= "echo.\r\n";
        $bat .= "echo  ========================================\r\n";
        $bat .= "echo   EmulationApp - Running Job\r\n";
        $bat .= "echo   {$name}\r\n";
        $bat .= "echo  ========================================\r\n";
        $bat .= "echo.\r\n";
        $bat .= "cd /d \"" . str_replace('/', '\\', $this->appRoot) . "\"\r\n";
        $bat .= "echo  [%TIME%] Starting job...\r\n";
        $bat .= "echo.\r\n";
        $bat .= "\"{$uv}\" run python run.py \"{$relativePath}\"\r\n";
        $bat .= "echo.\r\n";
        $bat .= "if %ERRORLEVEL% EQU 0 (\r\n";
        $bat .= "    color 0A\r\n";
        $bat .= "    echo  [%TIME%] Job completed successfully.\r\n";
        $bat .= ") else (\r\n";
        $bat .= "    color 0C\r\n";
        $bat .= "    echo  [%TIME%] Job failed with exit code %ERRORLEVEL%.\r\n";
        $bat .= ")\r\n";
        $bat .= "echo.\r\n";
        $bat .= "echo  Press any key to close...\r\n";
        $bat .= "pause >nul\r\n";

        return response($bat)
            ->header('Content-Type', 'application/x-bat')
            ->header('Content-Disposition', 'attachment; filename="run_' . $name . '.bat"');
    }

    /**
     * POST /settings - Save configuration.
     */
    public function saveSettings(Request $request)
    {
        $validated = $request->validate([
            'uv_path'          => 'nullable|string',
            'driver'           => 'required|in:selenium,playwright',
            's3_output_bucket' => 'nullable|string',
            's3_output_prefix' => 'nullable|string',
        ]);

        $settingsPath = $this->appRoot . DIRECTORY_SEPARATOR . '.emulation_settings.json';
        file_put_contents($settingsPath, json_encode($validated, JSON_PRETTY_PRINT));

        return redirect('/')->with('success', 'Settings saved.');
    }

    // ----------------------------------------------------------------
    //  Encryption
    // ----------------------------------------------------------------

    private function encryptCredentials(string $username, string $password): ?array
    {
        try {
            // Read the encryption key from .emulation_key
            $keyFile = $this->appRoot . DIRECTORY_SEPARATOR . '.emulation_key';

            if (!file_exists($keyFile)) {
                // Auto-generate a key (mirrors CredentialManager._load_or_generate_key)
                $keyText = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
                file_put_contents($keyFile, $keyText);
                Log::info('Generated new encryption key', ['path' => $keyFile]);
            }

            $keyText = trim(file_get_contents($keyFile));
            if (empty($keyText)) {
                Log::error('Encryption key file is empty', ['path' => $keyFile]);
                return null;
            }

            // Derive a 32-byte key using SHA-256 (matches CredentialManager._padded_key)
            $derivedKey = hash('sha256', $keyText, true);  // raw 32 bytes

            // AES-256-CTR with a zero IV (matches pyaes default CTR nonce)
            $iv = str_repeat("\0", 16);

            // openssl_encrypt with OPENSSL_RAW_DATA returns raw ciphertext (no base64)
            // We base64-encode ourselves to match the Python side exactly.
            $encUser = openssl_encrypt($username, 'aes-256-ctr', $derivedKey, OPENSSL_RAW_DATA, $iv);
            $encPass = openssl_encrypt($password, 'aes-256-ctr', $derivedKey, OPENSSL_RAW_DATA, $iv);

            if ($encUser === false || $encPass === false) {
                Log::error('openssl_encrypt returned false', ['error' => openssl_error_string()]);
                return null;
            }

            return [
                'username' => base64_encode($encUser),
                'password' => base64_encode($encPass),
            ];

        } catch (\Exception $e) {
            Log::error('Credential encryption exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    // ----------------------------------------------------------------
    //  Helpers
    // ----------------------------------------------------------------

    /**
     * Search all .json files in the jobs directory for one whose
     * script_path matches the given script filename.
     *
     * Returns the decoded JSON array of the most recently modified
     * matching config, or null if none found.
     */
    private function findCompanionConfig(string $scriptName): ?array
    {
        $jsonFiles = glob($this->jobsDir . DIRECTORY_SEPARATOR . '*.json');
        if (!$jsonFiles) {
            return null;
        }

        // Sort newest first so we get the most recent config
        usort($jsonFiles, fn($a, $b) => filemtime($b) - filemtime($a));

        foreach ($jsonFiles as $jsonFile) {
            $decoded = json_decode(file_get_contents($jsonFile), true);
            if (!is_array($decoded)) {
                continue;
            }

            $savedScript = $decoded['script_path'] ?? '';
            // Match by exact name or basename
            if ($savedScript === $scriptName || basename($savedScript) === $scriptName) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Parse console output from runner.py into structured log entries.
     * Format: "    [HH:MM:SS.mmm] message  |  detail"
     */
    private function parseLogOutput(string $output): array
    {
        $entries = [];
        $lines = explode("\n", $output);

        // Icon map: keyword => emoji
        $iconMap = [
            'payload'     => '&#x1F4C4;',
            'credential'  => '&#x1F513;',
            'decrypt'     => '&#x1F513;',
            'encrypt'     => '&#x1F512;',
            'browser'     => '&#x1F310;',
            'navigat'     => '&#x1F4CA;',
            'login'       => '&#x1F511;',
            'download'    => '&#x2B07;&#xFE0F;',
            'screenshot'  => '&#x1F4F7;',
            'upload'      => '&#x2601;&#xFE0F;',
            'cleanup'     => '&#x1F9F9;',
            'closed'      => '&#x1F9F9;',
            'success'     => '&#x2705;',
            'complete'    => '&#x2705;',
            'error'       => '&#x274C;',
            'fail'        => '&#x274C;',
            'warn'        => '&#x26A0;&#xFE0F;',
            'date'        => '&#x1F4C5;',
            'report'      => '&#x1F4CA;',
            'script'      => '&#x1F4DD;',
        ];

        foreach ($lines as $line) {
            $line = trim($line);

            // Match logger format: [HH:MM:SS.mmm] message  |  detail
            if (preg_match('/^\[(\d{2}:\d{2}:\d{2}(?:\.\d+)?)\]\s*(.+)$/', $line, $m)) {
                $time    = substr($m[1], 0, 8); // HH:MM:SS
                $content = $m[2];

                // Split on " | " for detail
                $parts   = preg_split('/\s+\|\s+/', $content, 2);
                $message = $parts[0];
                $detail  = $parts[1] ?? '';

                // Strip log level prefix like [INFO ] or [OK   ]
                $message = preg_replace('/^\[\w+\s*\]\s*/', '', $message);

                // Pick icon
                $icon = '&#x25CF;'; // default dot
                $lower = strtolower($message . ' ' . $detail);
                foreach ($iconMap as $keyword => $emoji) {
                    if (str_contains($lower, $keyword)) {
                        $icon = $emoji;
                        break;
                    }
                }

                // Build HTML: bold the message, detail in normal weight
                $html = '<strong>' . e($message) . '</strong>';
                if ($detail) {
                    $html .= ' ' . e($detail);
                }

                $entries[] = [
                    'time' => $time,
                    'icon' => $icon,
                    'html' => $html,
                ];
            }
        }

        // If no structured lines found, show raw output as single entry
        if (empty($entries) && trim($output) !== '') {
            $entries[] = [
                'time' => now()->format('H:i:s'),
                'icon' => '&#x1F4DD;',
                'html' => '<strong>Output</strong> <br>' . nl2br(e(trim($output))),
            ];
        }

        return $entries;
    }

    private function parseTokenArrays(array $keys, array $values): array
    {
        $tokens = [];

        foreach ($keys as $i => $key) {
            $key = trim($key);
            $value = trim($values[$i] ?? '');

            if ($key !== '') {
                $tokens[$key] = $value;
            }
        }

        return $tokens;
    }

    private function listPayloads(): array
    {
        $files = glob($this->jobsDir . DIRECTORY_SEPARATOR . '*.json');
        return array_map(fn($f) => basename($f, '.json'), $files ?: []);
    }

    private function listScripts(): array
    {
        $files = glob($this->jobsDir . DIRECTORY_SEPARATOR . '*.py');
        return array_map(fn($f) => basename($f), $files ?: []);
    }

    private function loadSettings(): array
    {
        $settingsPath = $this->appRoot . DIRECTORY_SEPARATOR . '.emulation_settings.json';

        $defaults = [
            'uv_path'          => '',
            'driver'           => 'selenium',
            's3_output_bucket' => '',
            's3_output_prefix' => '',
        ];

        if (file_exists($settingsPath)) {
            $decoded = json_decode(file_get_contents($settingsPath), true);
            if (is_array($decoded)) {
                $defaults = array_merge($defaults, $decoded);
            }
        }

        // Auto-discover uv if not set or if the saved path no longer exists
        if (empty($defaults['uv_path']) || !file_exists($defaults['uv_path'])) {
            $discovered = $this->discoverUvPath();
            if ($discovered) {
                $defaults['uv_path'] = $discovered;

                // Persist so we only discover once
                file_put_contents($settingsPath, json_encode($defaults, JSON_PRETTY_PRINT));
            }
        }

        return $defaults;
    }

    /**
     * Resolve the full path to the uv executable from settings, or fall back to 'uv'.
     */
    private function getUvCommand(): string
    {
        $settings = $this->loadSettings();
        $path = trim($settings['uv_path'] ?? '');

        return $path !== '' ? $path : 'uv';
    }

    /**
     * Attempt to find the uv binary on the system.
     *
     * Strategy (Windows):
     *   1. 'where uv' (relies on PATH, often fails under Apache)
     *   2. Resolve the real user home via multiple env vars
     *   3. Check common install locations under each candidate home
     *   4. Scan C:\Users\*\.local\bin\uv.exe as a last resort
     *
     * Strategy (Unix):
     *   1. 'which uv'
     *   2. Check common paths under $HOME
     */
    private function discoverUvPath(): ?string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return $this->discoverUvWindows();
        }

        return $this->discoverUvUnix();
    }

    private function discoverUvWindows(): ?string
    {
        // 1. Try 'where uv'
        $output = [];
        @exec('where uv 2>nul', $output, $code);
        if ($code === 0 && !empty($output[0]) && file_exists(trim($output[0]))) {
            return trim($output[0]);
        }

        // 2. Build a list of candidate home directories
        $homes = array_filter(array_unique([
            getenv('USERPROFILE'),
            getenv('HOMEDRIVE') && getenv('HOMEPATH') ? getenv('HOMEDRIVE') . getenv('HOMEPATH') : '',
            getenv('HOME'),
        ]));

        // Sub-paths where uv might live
        $subPaths = [
            '.local\\bin\\uv.exe',          // Official installer (astral.sh)
            '.cargo\\bin\\uv.exe',           // Cargo install
            'AppData\\Local\\uv\\uv.exe',    // Some package managers
            'AppData\\Roaming\\uv\\uv.exe',
            'AppData\\Local\\Programs\\uv\\uv.exe',
            'scoop\\shims\\uv.exe',          // Scoop
        ];

        foreach ($homes as $home) {
            foreach ($subPaths as $sub) {
                $candidate = $home . '\\' . $sub;
                if (file_exists($candidate)) {
                    return $candidate;
                }
            }
        }

        // 3. Check standalone paths
        $standalone = [
            'C:\\Program Files\\uv\\uv.exe',
            'C:\\Program Files (x86)\\uv\\uv.exe',
        ];

        foreach ($standalone as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // 4. Scan all user profiles as last resort (Apache often runs as SYSTEM)
        $usersDir = 'C:\\Users';
        if (is_dir($usersDir)) {
            $dirs = @scandir($usersDir);
            if ($dirs) {
                foreach ($dirs as $dir) {
                    if ($dir === '.' || $dir === '..' || $dir === 'Public' || $dir === 'Default') {
                        continue;
                    }

                    foreach ($subPaths as $sub) {
                        $candidate = $usersDir . '\\' . $dir . '\\' . $sub;
                        if (file_exists($candidate)) {
                            return $candidate;
                        }
                    }
                }
            }
        }

        return null;
    }

    private function discoverUvUnix(): ?string
    {
        $output = [];
        @exec('which uv 2>/dev/null', $output, $code);
        if ($code === 0 && !empty($output[0]) && file_exists(trim($output[0]))) {
            return trim($output[0]);
        }

        $home = getenv('HOME') ?: (getenv('SUDO_USER') ? '/home/' . getenv('SUDO_USER') : '');

        $candidates = array_filter([
            $home ? $home . '/.local/bin/uv' : null,
            $home ? $home . '/.cargo/bin/uv' : null,
            '/usr/local/bin/uv',
            '/usr/bin/uv',
            '/opt/homebrew/bin/uv',
        ]);

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
