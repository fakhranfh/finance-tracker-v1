<?php

/**
 * PostToolUse hook: enforces .ai/rules/general.md's "one controller file per
 * entity in app/Http/Controllers/Api" rule.
 *
 * Reads the Claude Code hook payload (tool_name/tool_input JSON) from STDIN,
 * and blocks creating a new single-action (__invoke-only) controller inside
 * a subdirectory of app/Http/Controllers/Api — resourceful entities must
 * group index/show/store/update/destroy into one controller class instead.
 * app/Http/Controllers/Api/Auth/** is exempt (predates this rule).
 */
$stdin = stream_get_contents(STDIN);
$payload = json_decode($stdin, true) ?? [];

$filePath = $payload['tool_input']['file_path']
    ?? $payload['tool_response']['filePath']
    ?? null;

if (! $filePath || ! is_string($filePath) || ! str_ends_with($filePath, '.php') || ! is_file($filePath)) {
    exit(0);
}

$normalized = str_replace('\\', '/', $filePath);

if (! preg_match('#/app/Http/Controllers/Api/([A-Za-z0-9_]+)/[A-Za-z0-9_]+\.php$#', $normalized, $m)) {
    exit(0);
}

if ($m[1] === 'Auth') {
    exit(0);
}

$code = file_get_contents($filePath);

if (! preg_match('/function\s+__invoke\s*\(/', $code)) {
    exit(0);
}

$message = "One-controller-per-entity check (.ai/rules/general.md) failed for {$filePath}:\n"
    ."- This is a single-action (`__invoke`) controller inside `app/Http/Controllers/Api/{$m[1]}/`. "
    ."Resourceful entities must use ONE controller file (e.g. `App\\Http\\Controllers\\Api\\{$m[1]}Controller`) with `index`/`show`/`store`/`update`/`destroy` methods, not one invokable class per action. "
    .'See App\Http\Controllers\Api\UserController for the reference shape.';

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
