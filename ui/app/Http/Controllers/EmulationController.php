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
            'tokens'           => 'nullable|string',
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

        $tokens = $this->parseTokens($validated['tokens'] ?? '');

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
            $result = Process::path($this->appRoot)
                ->timeout(300)
                ->run(['uv', 'run', 'python', 'run.py', $relativePath]);

            $output = $result->output();
            $error  = $result->errorOutput();

            if ($result->successful()) {
                return redirect('/')
                    ->with('success', "Job completed: {$name}.json")
                    ->with('run_output', $output);
            } else {
                return redirect('/')
                    ->withErrors(['run' => "Job failed: {$name}.json"])
                    ->with('run_output', $output . "\n" . $error);
            }

        } catch (\Exception $e) {
            return redirect('/')
                ->withErrors(['run' => 'Failed to start job: ' . $e->getMessage()]);
        }
    }

    /**
     * POST /settings - Save configuration.
     */
    public function saveSettings(Request $request)
    {
        $validated = $request->validate([
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
            $result = Process::path($this->appRoot)->run([
                'uv', 'run', 'python', '-c', $pythonCode
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

    private function parseTokens(string $raw): array
    {
        $tokens = [];
        $lines = array_filter(array_map('trim', explode("\n", $raw)));

        foreach ($lines as $line) {
            if (str_contains($line, ':')) {
                [$key, $value] = explode(':', $line, 2);
                $tokens[trim($key)] = trim($value);
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
            'driver'           => 'selenium',
            's3_output_bucket' => '',
            's3_output_prefix' => '',
        ];
    }
}
