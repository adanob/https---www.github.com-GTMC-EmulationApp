<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class EmulationController extends Controller
{
    /**
     * Path to the EmulationApp root (where engine/ and payloads/ live).
     * Set in config/emulation.php
     */
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

        return view('dashboard', compact('payloads', 'scripts'));
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

        // Build credentials (encrypt via Python engine)
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

        // Parse tokens from the form (key:value per line)
        $tokens = $this->parseTokens($validated['tokens'] ?? '');

        // Build the payload
        $payload = [
            'target_url'       => $validated['target_url'],
            'script_path'      => $validated['script_path'],
            'tokens'           => (object) $tokens,
            'credentials'      => $credentials,
            's3_output_bucket' => $validated['s3_output_bucket'] ?? null,
            's3_output_prefix' => $validated['s3_output_prefix'] ?? null,
        ];

        // Remove null values
        $payload = array_filter($payload, fn($v) => $v !== null);

        // Write to payloads/ directory
        $filename = $validated['payload_name'] . '.json';
        $filepath = $this->payloadsDir . DIRECTORY_SEPARATOR . $filename;

        file_put_contents($filepath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return redirect('/')
            ->with('success', "Payload saved: {$filename}");
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

    // ----------------------------------------------------------------
    //  Encryption - calls the Python CredentialManager via uv
    // ----------------------------------------------------------------

    /**
     * Encrypt username and password using the Python engine.
     *
     * Shells out to: uv run python -c "..."
     * The CredentialManager uses the .emulation_key file in the project root.
     */
    private function encryptCredentials(string $username, string $password): ?array
    {
        // Escape for Python string literal
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

    /**
     * Parse token text (one per line, key:value format) into an assoc array.
     */
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

    /**
     * List all .json files in the payloads/ directory.
     */
    private function listPayloads(): array
    {
        $files = glob($this->payloadsDir . DIRECTORY_SEPARATOR . '*.json');
        return array_map(fn($f) => basename($f, '.json'), $files ?: []);
    }

    /**
     * List all .py files in the scripts/ directory.
     */
    private function listScripts(): array
    {
        $files = glob($this->scriptsDir . DIRECTORY_SEPARATOR . '*.py');
        return array_map(fn($f) => 'scripts/' . basename($f), $files ?: []);
    }
}
