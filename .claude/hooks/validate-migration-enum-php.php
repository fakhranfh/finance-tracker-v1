<?php

/**
 * PostToolUse hook: validates that every `->enum(...)` column in a migration
 * has a matching PHP backed enum in app/Enums/, per .ai/rules/migrations.md.
 *
 * Reads the Claude Code hook payload (tool_name/tool_input JSON) from STDIN,
 * static-checks the edited file if it is a migration, and prints a hook-JSON
 * response listing enum columns with no matching app/Enums/*.php class.
 */

$stdin = stream_get_contents(STDIN);
$payload = json_decode($stdin, true) ?? [];

$filePath = $payload['tool_input']['file_path']
    ?? $payload['tool_response']['filePath']
    ?? null;

if (! $filePath || ! is_string($filePath) || ! preg_match('#database[/\\\\]migrations[/\\\\].*\.php$#', $filePath) || ! is_file($filePath)) {
    exit(0);
}

$code = file_get_contents($filePath);

$columns = findEnumColumns($code);

if (empty($columns)) {
    exit(0);
}

$projectRoot = findProjectRoot($filePath);
$enumsDir = $projectRoot ? $projectRoot.'/app/Enums' : null;
$existingEnums = $enumsDir && is_dir($enumsDir) ? glob($enumsDir.'/*.php') : [];
$existingBasenames = array_map(fn ($p) => strtolower(basename($p, '.php')), $existingEnums ?: []);

$missing = [];

foreach ($columns as $column) {
    $candidate = strtolower(str_replace('_', '', $column)).'enum';
    $found = false;

    foreach ($existingBasenames as $basename) {
        if (str_replace('_', '', strtolower($basename)) === $candidate
            || str_contains(strtolower($basename), strtolower(str_replace('_', '', $column)))) {
            $found = true;
            break;
        }
    }

    if (! $found) {
        $suggested = studlyCase($column).'Enum';
        $missing[] = "Column `{$column}` has no matching enum class in app/Enums/ (e.g. app/Enums/{$suggested}.php).";
    }
}

if (empty($missing)) {
    exit(0);
}

$list = implode("\n", array_map(fn ($v) => "- {$v}", $missing));
$message = "Enum column check (.ai/rules/migrations.md) found issues in {$filePath}:\n{$list}\nCreate a native PHP backed enum in app/Enums/ for each enum column, with cases matching the migration's values.";

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
function findEnumColumns(string $code): array
{
    preg_match_all('/->\s*enum\s*\(\s*[\'"]([a-zA-Z0-9_]+)[\'"]/', $code, $matches);

    return array_unique($matches[1] ?? []);
}

function studlyCase(string $value): string
{
    return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $value)));
}

function findProjectRoot(string $filePath): ?string
{
    $dir = dirname($filePath);

    while ($dir !== dirname($dir)) {
        if (is_file($dir.'/artisan')) {
            return $dir;
        }
        $dir = dirname($dir);
    }

    return null;
}
