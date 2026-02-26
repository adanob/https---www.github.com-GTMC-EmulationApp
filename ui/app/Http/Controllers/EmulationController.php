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
            'target_url'        => 'required|url',
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

        $credentials = null;
        if (!empty($validated['username']) && !empty($validated['password'])) {
            $credentials = $this->encryptCredentials(
                $validated['username'],
                $validated['password']
            );

            if ($credentials === null) {
                return back()
                    ->withInput()
                    ->withErrors(['credentials' => 'Encryption failed. Check .emulation_key file and PHP openssl extension.']);
            }
        }

        $tokens = $this->parseTokenArrays(
            $validated['token_keys'] ?? [],
            $validated['token_values'] ?? []
        );

        $needsDeveloper = !empty($validated['needs_developer']);
        $scriptMode     = $validated['script_mode'] ?? '';

        // Determine the status of the configuration
        $status = 'ready';
        if ($needsDeveloper) {
            $status = 'needs_developer';
        } elseif ($scriptMode === 'pagecast') {
            $status = 'needs_recording';
        } elseif (empty($validated['script_path'])) {
            $status = 'needs_script';
        }

        $payload = [
            'target_url'       => $validated['target_url'],
            'script_path'      => $validated['script_path'] ?? null,
            'script_mode'      => $scriptMode,
            'status'           => $status,
            'tokens'           => (object) $tokens,
            'credentials'      => $credentials,
            's3_output_bucket' => $validated['s3_output_bucket'] ?? null,
            's3_output_prefix' => $validated['s3_output_prefix'] ?? null,
        ];

        $payload = array_filter($payload, fn($v) => $v !== null && $v !== '');

        $filename = $validated['payload_name'] . '.json';
        $filepath = $this->jobsDir . DIRECTORY_SEPARATOR . $filename;

        file_put_contents($filepath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // If "Run Job" was clicked, save then immediately execute
        if ($request->input('action') === 'save_and_run' && $status === 'ready') {
            return $this->run($validated['payload_name']);
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

            if (empty($decoded['target_url'])) {
                return back()->withErrors(['payload_file' => 'Payload must contain a target_url field.']);
            }

            $filename = $name . '.json';
            $filepath = $this->jobsDir . DIRECTORY_SEPARATOR . $filename;
            file_put_contents($filepath, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return redirect('/')->with('success', "Configuration uploaded: {$filename}");
        }

        return back()->withErrors(['payload_file' => 'Unsupported file type. Upload a .py script or .json configuration.']);
    }

    /**
     * GET /script/{name}/tokens - Detect token names used in a .py script.
     *
     * Scans for tokens["key"], tokens['key'], and tokens.get("key" patterns.
     */
    public function scriptTokens(string $name)
    {
        $filepath = $this->jobsDir . DIRECTORY_SEPARATOR . $name;

        if (!file_exists($filepath) || !str_ends_with($name, '.py')) {
            return response()->json(['tokens' => []], 200);
        }

        $source = file_get_contents($filepath);

        // Match tokens["key"], tokens['key'], tokens.get("key", tokens.get('key'
        preg_match_all('/tokens\s*\[\s*["\']([^"\']+)["\']\s*\]/', $source, $m1);
        preg_match_all('/tokens\s*\.\s*get\s*\(\s*["\']([^"\']+)["\']/', $source, $m2);

        $tokenNames = array_values(array_unique(array_merge($m1[1] ?? [], $m2[1] ?? [])));

        return response()->json(['tokens' => $tokenNames]);
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
     * POST /payload/{name}/run - Execute a payload via runner.py.
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

            // Check both exit code and output for failure indicators
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
