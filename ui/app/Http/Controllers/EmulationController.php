<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class EmulationController extends Controller
{
    private string $appRoot;
    private string $payloadsDir;
    private string $scriptsDir;

    public function __construct()
    {
        $this->appRoot     = config('emulation.app_root');
        $this->payloadsDir = config('emulation.payloads_dir');
        $this->scriptsDir  = config('emulation.scripts_dir');
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
     * POST /payload - Create a new payload with encrypted credentials.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'target_url'       => 'required|url',
            'username'         => 'nullable|string',
            'password'         => 'nullable|string',
            'script_path'      => 'required|string',
            'token_keys'       => 'nullable|array',
            'token_keys.*'     => 'nullable|string',
            'token_values'     => 'nullable|array',
            'token_values.*'   => 'nullable|string',
            's3_output_bucket' => 'nullable|string',
            's3_output_prefix' => 'nullable|string',
            'payload_name'     => 'required|string|regex:/^[a-zA-Z0-9_\-]+$/',
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
                    ->withErrors(['credentials' => 'Encryption failed. Check Python/uv installation.']);
            }
        }

        $tokens = $this->parseTokenArrays(
            $validated['token_keys'] ?? [],
            $validated['token_values'] ?? []
        );

        $payload = [
            'target_url'       => $validated['target_url'],
            'script_path'      => $validated['script_path'],
            'tokens'           => (object) $tokens,
            'credentials'      => $credentials,
            's3_output_bucket' => $validated['s3_output_bucket'] ?? null,
            's3_output_prefix' => $validated['s3_output_prefix'] ?? null,
        ];

        $payload = array_filter($payload, fn($v) => $v !== null);

        $filename = $validated['payload_name'] . '.json';
        $filepath = $this->payloadsDir . DIRECTORY_SEPARATOR . $filename;

        file_put_contents($filepath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // If "Run Job" was clicked, save then immediately execute
        if ($request->input('action') === 'save_and_run') {
            return $this->run($validated['payload_name']);
        }

        return redirect('/')->with('success', "Payload saved: {$filename}");
    }

    /**
     * POST /payload/upload - Upload an existing payload JSON file.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'payload_file' => 'required|file|mimes:json,txt|max:512',
        ]);

        $file = $request->file('payload_file');
        $contents = file_get_contents($file->getRealPath());

        // Validate it is valid JSON
        $decoded = json_decode($contents, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->withErrors(['payload_file' => 'Invalid JSON file.']);
        }

        // Validate it has a target_url at minimum
        if (empty($decoded['target_url'])) {
            return back()->withErrors(['payload_file' => 'Payload must contain a target_url field.']);
        }

        // Use original filename (sanitized)
        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name);
        $filename = $name . '.json';

        $filepath = $this->payloadsDir . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($filepath, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return redirect('/')->with('success', "Payload uploaded: {$filename}");
    }

    /**
     * GET /payload/{name} - View a payload file as JSON.
     */
    public function show(string $name)
    {
        $filepath = $this->payloadsDir . DIRECTORY_SEPARATOR . $name . '.json';

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
        $filepath = $this->payloadsDir . DIRECTORY_SEPARATOR . $name . '.json';

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
        $filepath = $this->payloadsDir . DIRECTORY_SEPARATOR . $name . '.json';

        if (!file_exists($filepath)) {
            return back()->withErrors(['run' => "Payload not found: {$name}.json"]);
        }

        $relativePath = 'payloads/' . $name . '.json';

        try {
            $startTime = microtime(true);

            $uv = $this->getUvCommand();

            $result = Process::path($this->appRoot)
                ->timeout(300)
                ->run([$uv, 'run', 'python', 'run.py', $relativePath]);

            $elapsed = round(microtime(true) - $startTime);
            $output  = $result->output() . "\n" . $result->errorOutput();
            $success = $result->successful();

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
        $escapedUser = str_replace(["\\", '"'], ["\\\\", '\\"'], $username);
        $escapedPass = str_replace(["\\", '"'], ["\\\\", '\\"'], $password);

        $pythonCode = sprintf(
            'import json; from engine import CredentialManager; cm = CredentialManager(); print(json.dumps(cm.encrypt_credentials(\"%s\", \"%s\")))',
            $escapedUser,
            $escapedPass
        );

        try {
            $uv = $this->getUvCommand();

            $result = Process::path($this->appRoot)->run([
                $uv, 'run', 'python', '-c', $pythonCode
            ]);

            if ($result->successful()) {
                $output = trim($result->output());
                $decoded = json_decode($output, true);

                if (is_array($decoded) && isset($decoded['username'], $decoded['password'])) {
                    return $decoded;
                }
            }

            Log::error('Credential encryption failed', [
                'exit_code' => $result->exitCode(),
                'output'    => $result->output(),
                'error'     => $result->errorOutput(),
            ]);

        } catch (\Exception $e) {
            Log::error('Credential encryption exception', ['message' => $e->getMessage()]);
        }

        return null;
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
        $files = glob($this->payloadsDir . DIRECTORY_SEPARATOR . '*.json');
        return array_map(fn($f) => basename($f, '.json'), $files ?: []);
    }

    private function listScripts(): array
    {
        $files = glob($this->scriptsDir . DIRECTORY_SEPARATOR . '*.py');
        return array_map(fn($f) => 'scripts/' . basename($f), $files ?: []);
    }

    private function loadSettings(): array
    {
        $settingsPath = $this->appRoot . DIRECTORY_SEPARATOR . '.emulation_settings.json';

        if (file_exists($settingsPath)) {
            $decoded = json_decode(file_get_contents($settingsPath), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [
            'uv_path'          => '',
            'driver'           => 'selenium',
            's3_output_bucket' => '',
            's3_output_prefix' => '',
        ];
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
}
