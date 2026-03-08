<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Database\Models\AutodProcessors;

/**
 * Class PageCastRecorderController
 * @package App\Http\Controllers
 * 
 * Manages PageCast navigation recording sessions and script generation
 */
class PageCastRecorderController extends Controller
{
    /**
     * Submenu configuration
     */
    protected $submenu = [
        'title' => 'PageCast Recorder',
        'items' => [
            'Record' => [
                'link' => '/emulation/recorder',
                'active' => false
            ],
            'Sessions' => [
                'link' => '/emulation/recorder/sessions',
                'active' => false
            ],
            'Scripts' => [
                'link' => '/emulation/recorder/scripts',
                'active' => false
            ]
        ]
    ];

    /**
     * Show recorder interface
     */
    public function index(Request $request)
    {
        $this->submenu['items']['Record']['active'] = true;
        
        // Get list of processors for target selection
        $processors = AutodProcessors::where('enabled', 'Y')
            ->where('instance_id', 8) // Lambda only
            ->orderBy('name')
            ->get();

        $activeSessions = $this->getActiveSessions();
        $recentSessions = $this->getRecentSessions(5);

        return view('emulation/recorder/index', [
            'submenu' => $this->submenu,
            'processors' => $processors,
            'activeSessions' => $activeSessions,
            'recentSessions' => $recentSessions
        ]);
    }

    /**
     * Show sessions list
     */
    public function sessions(Request $request)
    {
        $this->submenu['items']['Sessions']['active'] = true;
        
        $sessions = $this->getAllSessions();

        return view('emulation/recorder/sessions', [
            'submenu' => $this->submenu,
            'sessions' => $sessions
        ]);
    }

    /**
     * Show generated scripts
     */
    public function scripts(Request $request)
    {
        $this->submenu['items']['Scripts']['active'] = true;
        
        $scripts = $this->getGeneratedScripts();

        return view('emulation/recorder/scripts', [
            'submenu' => $this->submenu,
            'scripts' => $scripts
        ]);
    }

    /**
     * Start a new recording session
     */
    public function startSession(Request $request): JsonResponse
    {
        $request->validate([
            'session_name' => 'required|string|max:255',
            'target_url' => 'required|url',
            'processor_id' => 'nullable|integer',
            'description' => 'nullable|string'
        ]);

        $sessionId = Str::uuid()->toString();
        $sessionData = [
            'id' => $sessionId,
            'name' => $request->session_name,
            'target_url' => $request->target_url,
            'processor_id' => $request->processor_id,
            'description' => $request->description,
            'created_at' => now()->toIso8601String(),
            'created_by' => $request->user()->id ?? null,
            'status' => 'recording',
            'actions' => [],
            'tokens' => [],
            'credentials' => [],
            'metadata' => [
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip()
            ]
        ];

        // Store session in storage/app/recorder/sessions/
        Storage::disk('local')->put(
            "recorder/sessions/{$sessionId}.json",
            json_encode($sessionData, JSON_PRETTY_PRINT)
        );

        Log::info("PageCast recording session started", [
            'session_id' => $sessionId,
            'name' => $request->session_name,
            'user_id' => $request->user()->id ?? null
        ]);

        return Response::json([
            'success' => true,
            'session_id' => $sessionId,
            'message' => 'Recording session started successfully'
        ]);
    }

    /**
     * Save recorded actions to session
     */
    public function saveActions(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string',
            'actions' => 'required|array'
        ]);

        $sessionPath = "recorder/sessions/{$request->session_id}.json";
        
        if (!Storage::disk('local')->exists($sessionPath)) {
            return Response::json([
                'success' => false,
                'message' => 'Session not found'
            ], 404);
        }

        $session = json_decode(Storage::disk('local')->get($sessionPath), true);
        
        // Append new actions
        $session['actions'] = array_merge($session['actions'] ?? [], $request->actions);
        $session['updated_at'] = now()->toIso8601String();
        $session['action_count'] = count($session['actions']);

        Storage::disk('local')->put($sessionPath, json_encode($session, JSON_PRETTY_PRINT));

        return Response::json([
            'success' => true,
            'action_count' => $session['action_count'],
            'message' => 'Actions saved successfully'
        ]);
    }

    /**
     * Save tokens for the session
     */
    public function saveTokens(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string',
            'tokens' => 'required|array'
        ]);

        $sessionPath = "recorder/sessions/{$request->session_id}.json";
        
        if (!Storage::disk('local')->exists($sessionPath)) {
            return Response::json([
                'success' => false,
                'message' => 'Session not found'
            ], 404);
        }

        $session = json_decode(Storage::disk('local')->get($sessionPath), true);
        $session['tokens'] = $request->tokens;
        $session['updated_at'] = now()->toIso8601String();

        Storage::disk('local')->put($sessionPath, json_encode($session, JSON_PRETTY_PRINT));

        Log::info("Tokens saved for session", [
            'session_id' => $request->session_id,
            'token_count' => count($request->tokens)
        ]);

        return Response::json([
            'success' => true,
            'message' => 'Tokens saved successfully'
        ]);
    }

    /**
     * Save credentials for the session (encrypted)
     */
    public function saveCredentials(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'security' => 'nullable|string'
        ]);

        $sessionPath = "recorder/sessions/{$request->session_id}.json";
        
        if (!Storage::disk('local')->exists($sessionPath)) {
            return Response::json([
                'success' => false,
                'message' => 'Session not found'
            ], 404);
        }

        $session = json_decode(Storage::disk('local')->get($sessionPath), true);
        
        // Encrypt sensitive credentials
        $session['credentials'] = [
            'username' => $request->username,
            'password' => $request->password ? encrypt($request->password) : null,
            'security' => $request->security ? encrypt($request->security) : null
        ];
        $session['updated_at'] = now()->toIso8601String();

        Storage::disk('local')->put($sessionPath, json_encode($session, JSON_PRETTY_PRINT));

        return Response::json([
            'success' => true,
            'message' => 'Credentials saved successfully'
        ]);
    }

    /**
     * Stop recording session
     */
    public function stopSession(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string'
        ]);

        $sessionPath = "recorder/sessions/{$request->session_id}.json";
        
        if (!Storage::disk('local')->exists($sessionPath)) {
            return Response::json([
                'success' => false,
                'message' => 'Session not found'
            ], 404);
        }

        $session = json_decode(Storage::disk('local')->get($sessionPath), true);
        $session['status'] = 'stopped';
        $session['stopped_at'] = now()->toIso8601String();
        $session['duration_seconds'] = strtotime($session['stopped_at']) - strtotime($session['created_at']);

        Storage::disk('local')->put($sessionPath, json_encode($session, JSON_PRETTY_PRINT));

        Log::info("Recording session stopped", [
            'session_id' => $request->session_id,
            'action_count' => count($session['actions'] ?? []),
            'duration' => $session['duration_seconds']
        ]);

        return Response::json([
            'success' => true,
            'message' => 'Recording session stopped',
            'session' => $session
        ]);
    }

    /**
     * Generate Python navigation script from recorded session
     */
    public function generateScript(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string',
            'script_name' => 'required|string|max:255'
        ]);

        $sessionPath = "recorder/sessions/{$request->session_id}.json";
        
        if (!Storage::disk('local')->exists($sessionPath)) {
            return Response::json([
                'success' => false,
                'message' => 'Session not found'
            ], 404);
        }

        $session = json_decode(Storage::disk('local')->get($sessionPath), true);
        
        try {
            // Generate Python script content
            $scriptContent = $this->buildPythonScript($session, $request->script_name);
            
            // Save to storage/app/scripts/
            $scriptFilename = Str::slug($request->script_name) . '.py';
            Storage::disk('local')->put("scripts/{$scriptFilename}", $scriptContent);

            // Update session
            $session['status'] = 'completed';
            $session['generated_script'] = $scriptFilename;
            $session['completed_at'] = now()->toIso8601String();
            Storage::disk('local')->put($sessionPath, json_encode($session, JSON_PRETTY_PRINT));

            // Create processor entry if processor_id is set
            if (isset($session['processor_id']) && $session['processor_id']) {
                $this->createProcessorEntry($session, $scriptFilename);
            }

            Log::info("Navigation script generated", [
                'session_id' => $request->session_id,
                'script_name' => $scriptFilename,
                'action_count' => count($session['actions'])
            ]);

            return Response::json([
                'success' => true,
                'script_name' => $scriptFilename,
                'script_path' => "scripts/{$scriptFilename}",
                'message' => 'Navigation script generated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to generate script", [
                'session_id' => $request->session_id,
                'error' => $e->getMessage()
            ]);

            return Response::json([
                'success' => false,
                'message' => 'Failed to generate script: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get session data
     */
    public function getSession(Request $request, $sessionId): JsonResponse
    {
        $sessionPath = "recorder/sessions/{$sessionId}.json";
        
        if (!Storage::disk('local')->exists($sessionPath)) {
            return Response::json([
                'success' => false,
                'message' => 'Session not found'
            ], 404);
        }

        $session = json_decode(Storage::disk('local')->get($sessionPath), true);

        return Response::json([
            'success' => true,
            'session' => $session
        ]);
    }

    /**
     * Delete a recording session
     */
    public function deleteSession(Request $request, $sessionId): JsonResponse
    {
        $sessionPath = "recorder/sessions/{$sessionId}.json";
        
        if (!Storage::disk('local')->exists($sessionPath)) {
            return Response::json([
                'success' => false,
                'message' => 'Session not found'
            ], 404);
        }

        Storage::disk('local')->delete($sessionPath);

        Log::info("Recording session deleted", ['session_id' => $sessionId]);

        return Response::json([
            'success' => true,
            'message' => 'Session deleted successfully'
        ]);
    }

    /**
     * Get active recording sessions
     */
    private function getActiveSessions()
    {
        $sessions = [];
        $files = Storage::disk('local')->files('recorder/sessions');

        foreach ($files as $file) {
            $session = json_decode(Storage::disk('local')->get($file), true);
            if ($session['status'] === 'recording') {
                $sessions[] = $session;
            }
        }

        return $sessions;
    }

    /**
     * Get recent sessions
     */
    private function getRecentSessions($limit = 10)
    {
        $sessions = [];
        $files = Storage::disk('local')->files('recorder/sessions');

        foreach ($files as $file) {
            $session = json_decode(Storage::disk('local')->get($file), true);
            $sessions[] = $session;
        }

        // Sort by created_at descending
        usort($sessions, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return array_slice($sessions, 0, $limit);
    }

    /**
     * Get all sessions
     */
    private function getAllSessions()
    {
        $sessions = [];
        $files = Storage::disk('local')->files('recorder/sessions');

        foreach ($files as $file) {
            $session = json_decode(Storage::disk('local')->get($file), true);
            $sessions[] = $session;
        }

        // Sort by created_at descending
        usort($sessions, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return $sessions;
    }

    /**
     * Get generated scripts
     */
    private function getGeneratedScripts()
    {
        $scripts = [];
        $files = Storage::disk('local')->files('scripts');

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'py') {
                $content = Storage::disk('local')->get($file);
                $scripts[] = [
                    'filename' => basename($file),
                    'path' => $file,
                    'size' => Storage::disk('local')->size($file),
                    'modified' => Storage::disk('local')->lastModified($file),
                    'preview' => substr($content, 0, 500)
                ];
            }
        }

        // Sort by modified descending
        usort($scripts, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });

        return $scripts;
    }

    /**
     * Build Python navigation script from session data
     */
    private function buildPythonScript($session, $scriptName): string
    {
        $actions = $session['actions'] ?? [];
        $tokens = $session['tokens'] ?? [];
        $targetUrl = $session['target_url'] ?? '';
        $description = $session['description'] ?? 'Recorded navigation script';
        
        $script = $this->getPythonScriptTemplate();
        
        // Replace placeholders
        $script = str_replace('{SCRIPT_NAME}', $scriptName, $script);
        $script = str_replace('{DESCRIPTION}', $description, $script);
        $script = str_replace('{TARGET_URL}', $targetUrl, $script);
        $script = str_replace('{GENERATED_DATE}', date('Y-m-d H:i:s'), $script);
        $script = str_replace('{TOKENS_DICT}', $this->formatPythonDict($tokens), $script);
        $script = str_replace('{NAVIGATION_LOGIC}', $this->generateNavigationLogic($actions, $tokens), $script);
        
        return $script;
    }

    /**
     * Get Python script template
     */
    private function getPythonScriptTemplate(): string
    {
        $lines = [
            '"""',
            '{SCRIPT_NAME}',
            '{DESCRIPTION}',
            '',
            'Generated by PageCast Recorder on {GENERATED_DATE}',
            'Status: READY',
            '"""',
            '',
            'CONFIG = {',
            '    "script_name": "{SCRIPT_NAME}",',
            '    "target_url": "{TARGET_URL}",',
            '    "tokens": {TOKENS_DICT},',
            '    "status": "READY"',
            '}',
            '',
            'def navigate(context: dict) -> dict:',
            '    """',
            '    Navigate to {TARGET_URL} and perform recorded actions.',
            '    ',
            '    Args:',
            '        context: Contains helper, tokens, credentials, logger, target_url',
            '    ',
            '    Returns:',
            '        dict: Results including screenshots, data extracted',
            '    """',
            '    helper = context["helper"]',
            '    tokens = context["tokens"]',
            '    credentials = context.get("credentials", {})',
            '    logger = context["logger"]',
            '    ',
            '    # Initialize results',
            '    results = {',
            '        "status": "in_progress",',
            '        "screenshots": [],',
            '        "data": {}',
            '    }',
            '    ',
            '    try:',
            '{NAVIGATION_LOGIC}',
            '        ',
            '        # Mark as completed',
            '        results["status"] = "completed"',
            '        logger.info("Navigation completed successfully")',
            '        ',
            '    except Exception as e:',
            '        logger.error(f"Navigation failed: {str(e)}")',
            '        results["status"] = "failed"',
            '        results["error"] = str(e)',
            '        raise',
            '    ',
            '    return results',
        ];
        
        return implode("\n", $lines);
    }

    /**
     * Generate Python navigation logic from recorded actions
     */
    private function generateNavigationLogic($actions, $tokens): string
    {
        $logic = "        # Navigate to target URL\n";
        $logic .= "        helper.go(context[\"target_url\"])\n";
        $logic .= "        helper.wait_for_page_load()\n";
        $logic .= "        logger.info(\"Page loaded successfully\")\n";
        $logic .= "        \n";
        $logic .= "        # Take initial screenshot\n";
        $logic .= "        screenshot = helper.screenshot(\"initial\")\n";
        $logic .= "        results[\"screenshots\"].append(screenshot)\n";
        $logic .= "        \n";

        foreach ($actions as $index => $action) {
            $type = $action['type'] ?? '';
            $xpath = $action['element']['xpath'] ?? '';
            $value = $action['value'] ?? '';
            
            $logic .= "        # Step " . ($index + 1) . ": {$type}\n";

            switch ($type) {
                case 'click':
                case 'click_button':
                case 'click_link':
                    $logic .= "        helper.click('{$xpath}')\n";
                    $logic .= "        helper.wait_for_page_load()\n";
                    break;

                case 'type_text':
                    // Check if value matches a token
                    $tokenKey = $this->findTokenKey($value, $tokens);
                    if ($tokenKey) {
                        $logic .= "        helper.type_text('{$xpath}', tokens['{$tokenKey}'], clear_first=True)\n";
                    } else {
                        $escapedValue = addslashes($value);
                        $logic .= "        helper.type_text('{$xpath}', '{$escapedValue}', clear_first=True)\n";
                    }
                    break;

                case 'select_option':
                    $logic .= "        helper.select_option('{$xpath}', '{$value}')\n";
                    break;

                case 'check':
                    $checked = $value ? 'True' : 'False';
                    $logic .= "        helper.set_checkbox('{$xpath}', {$checked})\n";
                    break;

                case 'submit_form':
                    $logic .= "        helper.submit_form('{$xpath}')\n";
                    $logic .= "        helper.wait_for_page_load()\n";
                    break;

                case 'navigate':
                    $logic .= "        helper.go('{$value}')\n";
                    $logic .= "        helper.wait_for_page_load()\n";
                    break;
            }

            $logic .= "        \n";
        }

        $logic .= "        # Take final screenshot\n";
        $logic .= "        screenshot = helper.screenshot(\"final\")\n";
        $logic .= "        results[\"screenshots\"].append(screenshot)\n";

        return $logic;
    }

    /**
     * Format Python dictionary from PHP array
     */
    private function formatPythonDict($array): string
    {
        if (empty($array)) {
            return '{}';
        }

        $items = [];
        foreach ($array as $key => $value) {
            $items[] = "        \"{$key}\": \"{$value}\"";
        }

        return "{\n" . implode(",\n", $items) . "\n    }";
    }

    /**
     * Find token key from value
     */
    private function findTokenKey($value, $tokens)
    {
        foreach ($tokens as $key => $tokenValue) {
            if ($value === $tokenValue || $value === "{{{$key}}}") {
                return $key;
            }
        }
        return null;
    }

    /**
     * Create processor entry for generated script
     */
    private function createProcessorEntry($session, $scriptFilename)
    {
        // This would integrate with your autod_processors table
        // Implementation depends on your exact database structure
        try {
            $processor = AutodProcessors::find($session['processor_id']);
            if ($processor) {
                $processor->emulator_file = $scriptFilename;
                $processor->updated_date = now();
                $processor->save();
            }
        } catch (\Exception $e) {
            Log::warning("Failed to update processor entry", [
                'processor_id' => $session['processor_id'],
                'error' => $e->getMessage()
            ]);
        }
    }
}