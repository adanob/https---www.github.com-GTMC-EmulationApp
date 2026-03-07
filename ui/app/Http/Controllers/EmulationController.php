<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmulationController extends Controller
{
    /**
     * Display the dashboard with available base scripts
     */
    public function index()
    {
        // Get all base scripts from ./scripts/ directory
        $scriptFiles = Storage::disk('local')->files('scripts');
        $scripts = array_filter($scriptFiles, function($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'py';
        });

        // Extract just the filenames
        $scripts = array_map(function($file) {
            return basename($file);
        }, $scripts);

        return view('dashboard', [
            'scripts' => array_values($scripts),
            'payload' => null
        ]);
    }

    /**
     * Store a new job (generate .py file in ./jobs/)
     */
    public function store(Request $request)
    {
        $jobName = $request->input('payload_name');
        $jobDate = $request->input('job_date', date('Y-m-d'));
        $targetUrl = $request->input('target_url');
        $baseScript = $request->input('navigation_script'); // from dropdown
        $isDeveloperMode = $request->input('is_developer_config', false);

        // Collect tokens
        $tokens = [];
        if ($request->has('tokens')) {
            foreach ($request->input('tokens') as $key => $value) {
                $tokens[$key] = $value;
            }
        }

        // Collect credentials
        $credentials = [
            'username' => $request->input('username', ''),
            'password' => $request->input('password', '') // Should be encrypted
        ];

        // Generate the appropriate job file
        if ($isDeveloperMode || empty($baseScript)) {
            // Create developer handoff file
            $jobContent = $this->generateDeveloperHandoffJob(
                $jobName,
                $jobDate,
                $targetUrl,
                $tokens,
                $credentials
            );
        } else {
            // Create job that uses existing base script
            $jobContent = $this->generateUserJob(
                $jobName,
                $jobDate,
                $targetUrl,
                $baseScript,
                $tokens,
                $credentials
            );
        }

        // Save to ./jobs/ directory
        $jobPath = "jobs/{$jobName}.py";
        Storage::disk('local')->put($jobPath, $jobContent);

        return redirect()
            ->route('payload.show', $jobName)
            ->with('success', 'Job created successfully');
    }

    /**
     * Generate a user job file that runs an existing base script
     */
    private function generateUserJob($jobName, $jobDate, $targetUrl, $baseScript, $tokens, $credentials)
    {
        $baseScriptModule = pathinfo($baseScript, PATHINFO_FILENAME);

        $template = file_get_contents(base_path('app/Templates/job_template_existing_script.py'));

        return strtr($template, [
            '{job_name}' => $jobName,
            '{job_date}' => $jobDate,
            '{target_url}' => $targetUrl,
            '{base_script}' => $baseScript,
            '{base_script_module}' => $baseScriptModule,
            '{tokens_dict}' => $this->formatPythonDict($tokens),
            '{credentials_dict}' => $this->formatPythonDict($credentials),
        ]);
    }

    /**
     * Generate a developer handoff job file
     */
    private function generateDeveloperHandoffJob($jobName, $jobDate, $targetUrl, $tokens, $credentials)
    {
        $template = file_get_contents(base_path('app/Templates/job_template_developer_handoff.py'));

        $tokenList = implode(', ', array_keys($tokens));

        return strtr($template, [
            '{job_name}' => $jobName,
            '{job_date}' => $jobDate,
            '{target_url}' => $targetUrl,
            '{token_list}' => $tokenList,
            '{tokens_dict}' => $this->formatPythonDict($tokens),
            '{credentials_dict}' => $this->formatPythonDict($credentials),
        ]);
    }

    /**
     * Format PHP array as Python dictionary entries (without outer braces)
     */
    private function formatPythonDict($array)
    {
        if (empty($array)) {
            return '';
        }

        $pairs = [];
        foreach ($array as $key => $value) {
            $escapedValue = str_replace('"', '\\"', $value);
            $pairs[] = "        \"{$key}\": \"{$escapedValue}\"";
        }

        return implode(",\n", $pairs);
    }

    /**
     * Get metadata for a base script (for auto-population)
     */
    public function scriptTokens($name)
    {
        $scriptPath = "scripts/{$name}";

        if (!Storage::disk('local')->exists($scriptPath)) {
            return response()->json([
                'tokens' => [],
                'job_name' => null,
                'target_url' => null,
            ], 404);
        }

        $content = Storage::disk('local')->get($scriptPath);

        // Parse script to extract metadata
        $tokens = $this->parseTokensFromScript($content);
        $targetUrl = $this->parseTargetUrlFromScript($content);
        $jobName = pathinfo($name, PATHINFO_FILENAME);

        return response()->json([
            'tokens' => $tokens,
            'job_name' => $jobName,
            'job_date' => date('Y-m-d'),
            'target_url' => $targetUrl,
            'uses_target_url' => !empty($targetUrl),
            'uses_credentials' => strpos($content, 'credentials') !== false,
        ]);
    }

    /**
     * Parse tokens from Python script
     */
    private function parseTokensFromScript($content)
    {
        $tokens = [];

        // Look for tokens.get("key") or tokens["key"]
        if (preg_match_all('/tokens\.get\(["\']([^"\']+)["\']/i', $content, $matches)) {
            $tokens = array_merge($tokens, $matches[1]);
        }

        if (preg_match_all('/tokens\[["\']([^"\']+)["\']\]/i', $content, $matches)) {
            $tokens = array_merge($tokens, $matches[1]);
        }

        return array_values(array_unique($tokens));
    }

    /**
     * Parse target URL from script comments or code
     */
    private function parseTargetUrlFromScript($content)
    {
        // Look for URLs in comments or docstrings
        if (preg_match('/https?:\/\/[^\s\'"]+/i', $content, $matches)) {
            return $matches[0];
        }

        return null;
    }

    /**
     * Get script content for preview
     */
    public function scriptContent($name)
    {
        $scriptPath = "scripts/{$name}";

        if (Storage::disk('local')->exists($scriptPath)) {
            $content = Storage::disk('local')->get($scriptPath);

            return response()->json([
                'name' => $name,
                'content' => $content,
            ]);
        }

        return response()->json([
            'name' => $name,
            'content' => null,
        ], 404);
    }

    /**
     * Show a specific job
     */
    public function show($name)
    {
        $jobPath = "jobs/{$name}.py";

        if (!Storage::disk('local')->exists($jobPath)) {
            abort(404, 'Job not found');
        }

        $scripts = $this->getBaseScripts();

        return view('dashboard', [
            'scripts' => $scripts,
            'payload' => (object)[
                'name' => $name,
                'path' => $jobPath,
            ]
        ]);
    }

    /**
     * Delete a job
     */
    public function destroy($name)
    {
        $jobPath = "jobs/{$name}.py";

        if (Storage::disk('local')->exists($jobPath)) {
            Storage::disk('local')->delete($jobPath);
        }

        return redirect()->route('payload.index')
            ->with('success', 'Job deleted');
    }

    /**
     * Run a job
     */
    public function run(Request $request, $name)
    {
        // Execute the Python job file
        $jobPath = storage_path("app/jobs/{$name}.py");

        if (!file_exists($jobPath)) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        // Execute job (implementation depends on your Python execution setup)
        // This might call a Python process, queue a job, etc.

        return response()->json([
            'status' => 'running',
            'job' => $name,
            'message' => 'Job execution started'
        ]);
    }

    /**
     * Upload a new navigation script
     */
    public function upload(Request $request)
    {
        $request->validate([
            'payload_file' => 'required|file|mimes:py|max:10240', // Max 10MB
        ]);

        $file = $request->file('payload_file');
        $filename = $file->getClientOriginalName();

        // Read file content for validation
        $content = file_get_contents($file->getRealPath());

        // Validation checks
        $errors = $this->validateNavigationScript($content, $filename);

        if (!empty($errors)) {
            return back()->withErrors(['upload' => implode(' ', $errors)]);
        }

        // Save to scripts/ directory
        try {
            Storage::disk('local')->put("scripts/{$filename}", $content);

            return redirect()->route('payload.index')
                ->with('success', "Script '{$filename}' uploaded successfully and is now available in the dropdown");
        } catch (\Exception $e) {
            return back()->withErrors(['upload' => 'Upload failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Validate uploaded navigation script
     */
    private function validateNavigationScript($content, $filename)
    {
        $errors = [];

        // Check 1: Must have CONFIG dictionary
        if (!preg_match('/CONFIG\s*=\s*\{/', $content)) {
            $errors[] = "Invalid script: Missing CONFIG dictionary.";
        }

        // Check 2: Must have navigate() function
        if (!preg_match('/def\s+navigate\s*\([^)]*\)\s*->\s*dict:/', $content)) {
            $errors[] = "Invalid script: Missing navigate(context: dict) -> dict function.";
        }

        // Check 3: Should not have NotImplementedError (indicates template, not working script)
        if (preg_match('/raise\s+NotImplementedError/', $content)) {
            $errors[] = "This appears to be a template or placeholder, not a working script. " .
                       "Please implement the navigation logic before uploading. " .
                       "Working scripts should not raise NotImplementedError.";
        }

        // Check 4: Should have status = "READY" in CONFIG
        if (preg_match('/"status"\s*:\s*"AWAITING_DEVELOPER"/', $content)) {
            $errors[] = "Script status is AWAITING_DEVELOPER. " .
                       "Please implement the navigation logic and change status to 'READY' before uploading.";
        }

        // Check 5: Filename should end with .py
        if (!str_ends_with($filename, '.py')) {
            $errors[] = "Script filename must end with .py";
        }

        // Check 6: Should not be a job file (those go in jobs/, not scripts/)
        if (preg_match('/USER JOB:|Based on:/', $content)) {
            $errors[] = "This appears to be a user job file, not a base script. " .
                       "Job files are created by the dashboard and should not be uploaded manually.";
        }

        return $errors;
    }

    /**
     * Get list of base scripts
     */
    private function getBaseScripts()
    {
        $scriptFiles = Storage::disk('local')->files('scripts');
        $scripts = array_filter($scriptFiles, function($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'py';
        });

        return array_values(array_map('basename', $scripts));
    }
}
