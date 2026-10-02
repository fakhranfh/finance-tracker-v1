<?php

/**
 * PostToolUse hook: enforces .ai/rules/general.md's "app/Http/Controllers is
 * for pages only" rule.
 *
 * Reads the Claude Code hook payload (tool_name/tool_input JSON) from STDIN,
 * and when the edited file is a controller under app/Http/Controllers/**
 * that is NOT under app/Http/Controllers/Api/**, blocks it if it injects a
 * Service, type-hints a FormRequest, or calls a model/DB method directly —
 * all of which belong in an API controller instead.
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

if (! str_contains($normalized, '/app/Http/Controllers/') || str_contains($normalized, '/app/Http/Controllers/Api/')) {
    exit(0);
}

if (str_ends_with($normalized, '/app/Http/Controllers/Controller.php')) {
    exit(0);
}

$code = file_get_contents($filePath);
$violations = [];

foreach (allParamTypes($code) as $type) {
    if (str_ends_with($type, 'Service')) {
        $violations[] = "Depends on `{$type}`; a page controller must not call a Service (business logic) — move this action to a controller under app/Http/Controllers/Api and have the page's JS call it over REST.";
    }

    if (str_ends_with($type, 'Request') && $type !== 'Request') {
        $violations[] = "Type-hints `{$type}` (a Form Request); validation belongs in an API controller under app/Http/Controllers/Api, not a page controller.";
    }
}

foreach (directModelCalls($code) as [$class, $method]) {
    $violations[] = "Calls `{$class}::{$method}(...)` directly; a page controller must not touch the database — fetch this data client-side from an API controller under app/Http/Controllers/Api.";
}

$violations = array_unique($violations);

if (empty($violations)) {
    exit(0);
}

$list = implode("\n", array_map(fn ($v) => "- {$v}", $violations));
$message = "Page-only controller check (.ai/rules/general.md) found issues in {$filePath}:\n{$list}";

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

/**
 * Collects param types from the constructor and every method, e.g. injected
 * Services and type-hinted Form Requests.
 *
 * @return array<int, string>
 */
function allParamTypes(string $code): array
{
    preg_match_all('/function\s+\w+\s*\(([^)]*)\)/s', $code, $methods);

    $types = [];
    foreach ($methods[1] as $params) {
        preg_match_all('/(?:public|protected|private)?\s*(?:readonly\s+)?(?:\?)?([A-Z][A-Za-z0-9_\\\\]*)\s+\$\w+/', $params, $matches);
        foreach ($matches[1] as $type) {
            $types[] = basename(str_replace('\\', '/', $type));
        }
    }

    return $types;
}

/**
 * Finds `ClassName::method(` calls where ClassName looks like an Eloquent
 * model or the DB facade, ignoring `self::`, `static::`, `parent::`.
 *
 * @return array<int, array{0: string, 1: string}>
 */
function directModelCalls(string $code): array
{
    $queryMethods = ['query', 'where', 'whereHas', 'create', 'find', 'findOrFail', 'update', 'delete', 'destroy', 'with', 'all', 'firstOrFail', 'first', 'table'];

    preg_match_all('/\b([A-Z][A-Za-z0-9_]*)::('.implode('|', $queryMethods).')\s*\(/', $code, $matches, PREG_SET_ORDER);

    $calls = [];
    foreach ($matches as $match) {
        $class = $match[1];
        if (in_array($class, ['self', 'static', 'parent'], true)) {
            continue;
        }
        $calls[] = [$class, $match[2]];
    }

    return $calls;
}
