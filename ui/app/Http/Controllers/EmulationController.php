<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmulationController extends Controller
{
    /**
     * Return tokens and metadata for a navigation script
     *
     * This endpoint is called when a user selects a script from the dropdown.
     * It should return:
     * - tokens: array of token names required by the script
     * - job_name: suggested job name (auto-populates the Job Name field)
     * - job_date: date associated with the job (optional)
     * - target_url: target URL (auto-populates Target URL field)
     * - uses_target_url: whether script uses target_url
     * - uses_credentials: whether script needs credentials
     * - suggested_url: suggested URL if script has a default
     * - has_saved_creds: whether saved credentials exist
     */
    public function scriptTokens($name)
    {
        // Look for associated JSON config file
        $jsonPath = "scripts/" . pathinfo($name, PATHINFO_FILENAME) . ".json";

        if (Storage::disk('local')->exists($jsonPath)) {
            $config = json_decode(Storage::disk('local')->get($jsonPath), true);

            // Extract token names from the tokens object
            $tokenNames = array_keys($config['tokens'] ?? []);

            return response()->json([
                'tokens' => $tokenNames,
                'job_name' => $config['job_name'] ?? null,
                'job_date' => $config['job_date'] ?? null,
                'target_url' => $config['target_url'] ?? null,
                'uses_target_url' => isset($config['target_url']),
                'uses_credentials' => isset($config['credentials']),
                'suggested_url' => $config['target_url'] ?? null,
                'has_saved_creds' => isset($config['credentials']) &&
                                      isset($config['credentials']['username']) &&
                                      isset($config['credentials']['password']),
            ]);
        }

        // Fallback: Parse the Python script for token hints
        $scriptPath = "scripts/" . $name;
        if (Storage::disk('local')->exists($scriptPath)) {
            $scriptContent = Storage::disk('local')->get($scriptPath);
            $tokens = $this->parseTokensFromScript($scriptContent);

            return response()->json([
                'tokens' => $tokens,
                'job_name' => pathinfo($name, PATHINFO_FILENAME), // Use filename as fallback
                'job_date' => null,
                'target_url' => null,
                'uses_target_url' => strpos($scriptContent, 'target_url') !== false,
                'uses_credentials' => strpos($scriptContent, 'credentials') !== false,
                'suggested_url' => null,
                'has_saved_creds' => false,
            ]);
        }

        return response()->json([
            'tokens' => [],
            'job_name' => null,
            'job_date' => null,
            'target_url' => null,
            'uses_target_url' => false,
            'uses_credentials' => false,
            'suggested_url' => null,
            'has_saved_creds' => false,
        ]);
    }

    /**
     * Parse token names from Python script
     */
    private function parseTokensFromScript($content)
    {
        $tokens = [];

        // Look for tokens.get("key") or tokens["key"] patterns
        if (preg_match_all('/tokens\.get\(["\']([^"\']+)["\']/i', $content, $matches)) {
            $tokens = array_merge($tokens, $matches[1]);
        }

        if (preg_match_all('/tokens\[["\']([^"\']+)["\']\]/i', $content, $matches)) {
            $tokens = array_merge($tokens, $matches[1]);
        }

        return array_unique($tokens);
    }

    /**
     * Return script content for preview
     */
    public function scriptContent($name)
    {
        $scriptPath = "scripts/" . $name;

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

    // ... other controller methods (index, store, upload, etc.) remain the same
}
