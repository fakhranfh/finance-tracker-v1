<?php

/**
 * PostToolUse hook: validates Blade views against .ai/rules/views.md
 * "Every page must be multilingual (English + Indonesian)" rule — every
 * page must use translation helpers instead of hardcoded user-facing text,
 * and must have corresponding en/id language resources.
 *
 * Reads the Claude Code hook payload (tool_name/tool_input JSON) from STDIN,
 * static-checks the edited file if it is a *.blade.php file, and prints a
 * hook-JSON response with any violations found.
 */

$stdin = stream_get_contents(STDIN);
$payload = json_decode($stdin, true) ?? [];

$filePath = $payload['tool_input']['file_path']
    ?? $payload['tool_response']['filePath']
    ?? null;

if (! $filePath || ! is_string($filePath) || ! str_ends_with($filePath, '.blade.php') || ! is_file($filePath)) {
    exit(0);
}

$code = file_get_contents($filePath);

$violations = validateMultilingualUsage($code, $filePath);

if (empty($violations)) {
    exit(0);
}

$list = implode("\n", array_map(fn ($v) => "- {$v}", $violations));
$message = "Multilingual check (.ai/rules/views.md) found issues in {$filePath}:\n{$list}";

echo json_encode([
    'systemMessage' => $message,
    'hookSpecificOutput' => [
        'hookEventName' => 'PostToolUse',
        'additionalContext' => $message,
    ],
]);

exit(0);

/**
 * @return array<int, string>
 */
function validateMultilingualUsage(string $code, string $filePath): array
{
    $violations = [];

    // Strip Blade comments and PHP/HTML comments so we don't flag text inside them.
    $stripped = preg_replace('/\{\{--.*?--\}\}/s', '', $code);
    $stripped = preg_replace('/<!--.*?-->/s', '', $stripped);

    $usesTranslation = preg_match('/@lang\(|__\(|trans\(|trans_choice\(|Lang::get\(|@choice\(/', $stripped);

    // Look for hardcoded, human-readable text inside common text-bearing tags
    // that is not wrapped by a translation helper.
    if (preg_match_all('/>([^<>{}\n]{3,})</', $stripped, $matches)) {
        foreach ($matches[1] as $text) {
            $trimmed = trim($text);
            if ($trimmed === '' || ! preg_match('/[A-Za-z]{2,}/', $trimmed)) {
                continue;
            }
            // Skip if it's just a Blade/Vue directive or interpolation leftover.
            if (preg_match('/^\s*[@{$]/', $trimmed)) {
                continue;
            }
            $violations[] = "Hardcoded text \"{$trimmed}\" found; wrap user-facing strings with __() / @lang() so the page supports English and Indonesian, per .ai/rules/views.md.";
            break; // one example is enough to flag the file
        }
    }

    if (! $usesTranslation && ! empty($violations)) {
        // Already covered by the hardcoded-text violation above.
    } elseif (! $usesTranslation && preg_match('/<h1|<h2|<h3|<p\b|<label|<button|<title>/i', $stripped)) {
        $violations[] = 'View renders text content but does not use any translation helper (__(), @lang(), trans()); every page must support English and Indonesian, per .ai/rules/views.md.';
    }

    if (! empty($violations)) {
        $langEn = 'lang/en';
        $langId = 'lang/id';
        $hasEn = is_dir(findProjectRoot($filePath).'/'.$langEn) || is_dir(findProjectRoot($filePath).'/resources/lang/en');
        $hasId = is_dir(findProjectRoot($filePath).'/'.$langId) || is_dir(findProjectRoot($filePath).'/resources/lang/id');
        if (! $hasEn || ! $hasId) {
            $violations[] = 'Missing lang/en and/or lang/id translation directories; the app locale must default to English with an Indonesian translation available, per .ai/rules/views.md.';
        }
    }

    return array_unique($violations);
}

function findProjectRoot(string $filePath): string
{
    $dir = dirname($filePath);
    while ($dir !== dirname($dir)) {
        if (is_file($dir.'/artisan')) {
            return $dir;
        }
        $dir = dirname($dir);
    }

    return getcwd();
}
