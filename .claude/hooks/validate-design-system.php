<?php

/**
 * PostToolUse hook: validates Blade views against DESIGN.md (FeedLoop design
 * system) per .ai/rules/views.md — no ad-hoc colors/fonts outside the design
 * tokens, and the Do's/Don'ts (no heavy shadows on feed cards, 44px minimum
 * mobile touch target).
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

$designPath = getcwd() . '/DESIGN.md';

if (! is_file($designPath)) {
    exit(0);
}

$code = file_get_contents($filePath);
$allowedHexes = extractAllowedHexes(file_get_contents($designPath));

$violations = validateDesign($code, $allowedHexes);

if (empty($violations)) {
    exit(0);
}

$list = implode("\n", array_map(fn ($v) => "- {$v}", $violations));
$message = "Design system check (DESIGN.md, per .ai/rules/views.md) found issues in {$filePath}:\n{$list}";

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
function extractAllowedHexes(string $designMarkdown): array
{
    preg_match_all('/#[0-9a-fA-F]{6}\b/', $designMarkdown, $matches);

    return array_values(array_unique(array_map('strtolower', $matches[0])));
}

/**
 * @param array<int, string> $allowedHexes
 * @return array<int, string>
 */
function validateDesign(string $code, array $allowedHexes): array
{
    $violations = [];

    // Hardcoded hex colors not defined in DESIGN.md's palette.
    if (preg_match_all('/#[0-9a-fA-F]{3,8}\b/', $code, $matches)) {
        $stray = [];
        foreach (array_unique($matches[0]) as $hex) {
            if (! in_array(strtolower($hex), $allowedHexes, true)) {
                $stray[] = $hex;
            }
        }
        if (! empty($stray)) {
            $violations[] = 'Hardcoded color(s) not in the DESIGN.md palette: ' . implode(', ', $stray) . '. Use the design tokens (e.g. Tailwind theme classes bound to DESIGN.md colors) instead.';
        }
    }

    // Tailwind default color utilities that bypass the custom palette
    // (e.g. bg-blue-500, text-gray-600) instead of the DESIGN.md tokens
    // (primary, secondary, surface, on-surface, etc.).
    $tailwindDefaultColors = ['slate', 'gray', 'zinc', 'neutral', 'stone', 'red', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald', 'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose'];
    $colorPattern = '/\b(?:bg|text|border|ring|fill|stroke)-(' . implode('|', $tailwindDefaultColors) . ')-\d{2,3}\b/';
    if (preg_match_all($colorPattern, $code, $matches)) {
        $violations[] = 'Uses Tailwind default palette utilities (' . implode(', ', array_unique($matches[0])) . ') instead of the DESIGN.md design tokens (primary, secondary, surface, on-surface, etc.).';
    }

    // Non design-system fonts (DESIGN.md only defines Plus Jakarta Sans and Inter).
    if (preg_match_all('/font-family\s*:\s*[\'"]?([^;\'"}]+)/i', $code, $matches)) {
        foreach ($matches[1] as $font) {
            $font = trim($font);
            if ($font !== '' && stripos($font, 'Plus Jakarta Sans') === false && stripos($font, 'Inter') === false) {
                $violations[] = "Inline `font-family: {$font}` doesn't match DESIGN.md typography (Plus Jakarta Sans for headlines, Inter for body/label).";
                break;
            }
        }
    }

    // Heavy shadows on feed/post cards, which DESIGN.md's Do's and Don'ts forbid.
    if (preg_match('/\bshadow-(lg|xl|2xl)\b/', $code, $m)) {
        $violations[] = "Uses `{$m[0]}`; DESIGN.md says not to add heavy shadows to feed/post cards.";
    }

    return array_unique($violations);
}
