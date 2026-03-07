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

        // Format tokens and credentials as Python dict entries
        $tokensFormatted = $this->formatPythonDict($tokens);
        $credentialsFormatted = $this->formatPythonDict($credentials);

        // Use heredoc to generate content directly (no template file needed)
        $content = <<<PYTHON
"""
USER JOB: {$jobName}
Based on: {$baseScript}
Created: {$jobDate}
Status: READY
"""

CONFIG = {
    "job_name": "{$jobName}",
    "job_date": "{$jobDate}",
    "target_url": "{$targetUrl}",
    "base_script": "{$baseScript}",
    "tokens": {
{$tokensFormatted}
    },
    "credentials": {
{$credentialsFormatted}
    },
    "status": "READY"
}

# Import the base navigation logic
import sys
import os
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..', 'scripts'))
from {$baseScriptModule} import navigate as base_navigate

def navigate(context: dict) -> dict:
    """Execute base script with user configuration"""
    # Inject user's configuration into context
    context["tokens"].update(CONFIG["tokens"])
    context["target_url"] = CONFIG["target_url"]
    if CONFIG["credentials"]:
        context["credentials"] = CONFIG["credentials"]

    # Call the base navigation script
    return base_navigate(context)
PYTHON;

        return $content;
    }

    /**
     * Generate a developer handoff job file
     */
    private function generateDeveloperHandoffJob($jobName, $jobDate, $targetUrl, $tokens, $credentials)
    {
        // Format tokens and credentials as Python dict entries
        $tokenList = empty($tokens) ? 'none specified' : implode(', ', array_keys($tokens));
        $tokensFormatted = $this->formatPythonDict($tokens);
        $credentialsFormatted = $this->formatPythonDict($credentials);

        // Use heredoc to generate content directly (no template file needed)
        $content = <<<PYTHON
"""
USER-CREATED JOB CONFIGURATION
Job Name: {$jobName}
Created: {$jobDate}
Status: AWAITING_DEVELOPER

Instructions for Developer:
This configuration was created by a user who needs automation for this portal.
Please implement the navigation logic in the navigate() function below.

User Requirements:
- Target: {$targetUrl}
- Tokens needed: {$tokenList}
- Credentials: Saved and encrypted in CONFIG below

Once implemented, change status to "READY" and test the job.
"""

CONFIG = {
    "job_name": "{$jobName}",
    "job_date": "{$jobDate}",
    "target_url": "{$targetUrl}",
    "tokens": {
{$tokensFormatted}
    },
    "credentials": {
{$credentialsFormatted}
    },
    "status": "AWAITING_DEVELOPER",
    "developer_notes": "This job needs navigation script implementation"
}

def navigate(context: dict) -> dict:
    """
    PLACEHOLDER: Developer needs to implement navigation logic

    Expected behavior:
    1. Login to {$targetUrl}
    2. Navigate to the appropriate section
    3. Use tokens from CONFIG to filter/configure
    4. Download files or capture screenshots
    5. Return results

    Available in context:
    - helper: BrowserHelper (helper.go, helper.click, helper.type_text, etc.)
    - tokens: User's token values (merged from CONFIG)
    - credentials: User's username/password
    - logger: JobLogger (logger.info, logger.success, logger.error)
    - target_url: The URL to navigate to

    Example implementation:

        helper = context["helper"]
        tokens = context["tokens"]
        creds = context["credentials"]
        logger = context["logger"]

        # Navigate to login
        helper.go(context["target_url"])
        helper.wait_for_page_load()

        # Login
        helper.type_text('//input[@id="username"]', creds["username"])
        helper.type_text('//input[@id="password"]', creds["password"])
        helper.click('//button[@type="submit"]')
        helper.wait_for_page_load()
        logger.success("Login successful")

        # Use tokens to complete task
        # ... your navigation logic here ...

        # Take screenshot
        screenshot = helper.screenshot()
        logger.info("Screenshot captured", screenshot)

        return {"screenshot": screenshot}
    """
    raise NotImplementedError(
        f"Job '{CONFIG['job_name']}' needs developer implementation. "
        f"See docstring above for user requirements and example code."
    )
PYTHON;

        return $content;
    }

    /**
     * Format PHP array as Python dictionary entries (without outer braces)
     */
    private function formatPythonDict($array)
    {
        // Ensure it's an array and not empty
        if (!is_array($array) || empty($array)) {
            return '';  // Return empty string (braces come from heredoc)
        }

        $pairs = [];
        foreach ($array as $key => $value) {
            // Escape quotes and backslashes for Python string
            $escapedValue = addslashes($value);
            // 8 spaces indentation (2 levels deep: 4 for dict + 4 for CONFIG)
            $pairs[] = "        \"{$key}\": \"{$escapedValue}\"";
        }

        // Return just the key-value pairs (no outer braces)
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
