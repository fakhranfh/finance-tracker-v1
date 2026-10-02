<?php

/**
 * PostToolUse hook: runs Larastan (PHPStan) on the PHP file just written/edited
 * under app/. Exits 2 with the errors on STDERR so Claude fixes them.
 */

$payload = json_decode(stream_get_contents(STDIN), true) ?? [];

$filePath = $payload['tool_input']['file_path'] ?? null;

if (! is_string($filePath) || ! str_ends_with($filePath, '.php') || str_ends_with($filePath, '.blade.php') || ! is_file($filePath)) {
    exit(0);
}

$root = dirname(__DIR__, 2);
$relative = ltrim(str_replace(str_replace('\\', '/', $root), '', str_replace('\\', '/', $filePath)), '/');

if (! str_starts_with($relative, 'app/')) {
    exit(0);
}

chdir($root);

exec('php vendor/bin/phpstan analyse --no-progress --error-format=raw --memory-limit=2G '.escapeshellarg($relative).' 2>&1', $output, $code);

if ($code !== 0) {
    fwrite(STDERR, "Larastan found errors in {$relative}. Fix them:\n\n".implode("\n", array_slice($output, -40))."\n");
    exit(2);
}

exit(0);
