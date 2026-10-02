<?php

// Stop hook: run Playwright when runtime-affecting files changed (.ai/rules/e2e-testing.md).
$input = json_decode(stream_get_contents(STDIN), true) ?: [];

if (! empty($input['stop_hook_active'])) {
    exit(0);
}

$root = dirname(__DIR__, 2);
chdir($root);

require __DIR__.'/lib/fingerprint.php';

$relevant = stop_check_pattern('playwright');
$fingerprint = relevant_fingerprint($relevant);

if ($fingerprint === null || fingerprint_is_unchanged('playwright', $fingerprint)) {
    exit(0);
}

$specs = affected_tests('tests/playwright', '*.spec.ts');

if ($specs === []) {
    remember_fingerprint('playwright', $fingerprint);
    exit(0);
}

$base = getenv('PLAYWRIGHT_BASE_URL') ?: 'http://127.0.0.1:8000';
$host = parse_url($base, PHP_URL_HOST);
$port = parse_url($base, PHP_URL_PORT) ?: 80;
$conn = @fsockopen($host, $port, $errno, $errstr, 2);

if (! $conn) {
    fwrite(STDERR, "Playwright skipped: no server at {$base}. Start `php artisan serve` and run `npx playwright test`.\n");
    hook_message("Playwright skipped: no server at {$base}.");
    exit(0);
}
fclose($conn);

exec('npx playwright test '.implode(' ', array_map('escapeshellarg', $specs)).' 2>&1', $output, $code);

if ($code !== 0) {
    fwrite(STDERR, "Playwright failed. Fix the code or spec, then re-run `npx playwright test <spec>`.\n\n".implode("\n", array_slice($output, -60))."\n");
    exit(2);
}

remember_fingerprint('playwright', $fingerprint);
$report = preg_replace('/\e\[[0-9;]*m/', '', implode("
", $output));
$passed = preg_match('/(\d+) passed/', $report, $m) ? $m[1] : null;
hook_message($passed === null ? 'Playwright passed.' : "Playwright passed: {$passed} tests");

exit(0);
