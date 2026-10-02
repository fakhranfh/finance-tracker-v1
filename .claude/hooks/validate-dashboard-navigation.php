<?php

/**
 * PostToolUse hook: enforces .ai/rules/dashboard-navigation.md's "Keep
 * sidebar and topbar items in their own config files" rule.
 *
 * Reads the Claude Code hook payload (tool_name/tool_input JSON) from STDIN
 * and static-checks the edited file:
 * - the sidebar/topbar Blade view must not define nav/icon items inline
 *   (no 'label'/'route'/'icon' array keys in the markup);
 * - the sidebar/topbar backing component class must source its data from
 *   config('sidebar.*') / config('topbar.*') rather than inlining it.
 */

$stdin = stream_get_contents(STDIN);
$payload = json_decode($stdin, true) ?? [];

$filePath = $payload['tool_input']['file_path']
    ?? $payload['tool_response']['filePath']
    ?? null;

$normalized = $filePath ? str_replace('\\', '/', $filePath) : null;

$bladeTargets = [
    'resources/views/components/organisms/sidebar.blade.php' => 'sidebar',
    'resources/views/components/organisms/topbar.blade.php' => 'topbar',
];

$classTargets = [
    'app/View/Components/Organisms/Sidebar.php' => 'sidebar',
    'app/View/Components/Organisms/Topbar.php' => 'topbar',
];

$violations = [];

foreach ($bladeTargets as $suffix => $configKey) {
    if (! $normalized || ! str_ends_with($normalized, $suffix) || ! is_file($filePath)) {
        continue;
    }

    $code = file_get_contents($filePath);

    if (preg_match('/\'(?:label|route|icon)\'\s*=>/', $code) === 1) {
        $violations[] = "Found an inline nav/icon item definition in {$filePath}; move it to config/{$configKey}.php and read it from the backing component class instead.";
    }
}

foreach ($classTargets as $suffix => $configKey) {
    if (! $normalized || ! str_ends_with($normalized, $suffix) || ! is_file($filePath)) {
        continue;
    }

    $code = file_get_contents($filePath);

    if (preg_match("/config\\(\\s*['\"]{$configKey}\\./", $code) !== 1) {
        $violations[] = "Expected {$filePath} to read its items via config('{$configKey}.*') instead of defining them inline.";
    }
}

if (empty($violations)) {
    exit(0);
}

$list = implode("\n", array_map(fn ($v) => "- {$v}", $violations));
$message = "Dashboard navigation check (.ai/rules/dashboard-navigation.md) found issues in {$filePath}:\n{$list}";

echo json_encode([
    'decision' => 'block',
    'reason' => $message,
    'systemMessage' => $message,
    'hookSpecificOutput' => [
        'hookEventName' => 'PostToolUse',
        'additionalContext' => $message,
    ],
]);

exit(0);
