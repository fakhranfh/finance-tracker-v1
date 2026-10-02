<?php

// Stop hook: run Pest when runtime-affecting files changed (.ai/rules/testing.md).
$input = json_decode(stream_get_contents(STDIN), true) ?: [];

if (! empty($input['stop_hook_active'])) {
    exit(0);
}

$root = dirname(__DIR__, 2);
chdir($root);

require __DIR__.'/lib/fingerprint.php';

$relevant = stop_check_pattern('pest');
$fingerprint = relevant_fingerprint($relevant);

if ($fingerprint === null || fingerprint_is_unchanged('pest', $fingerprint)) {
    exit(0);
}

$tests = affected_tests('tests/Feature', '*Test.php');

if ($tests === []) {
    remember_fingerprint('pest', $fingerprint);
    exit(0);
}

exec('php vendor/bin/pest '.implode(' ', array_map('escapeshellarg', $tests)).' 2>&1', $output, $code);

if ($code !== 0) {
    fwrite(STDERR, "Pest failed. Fix the code or test, then re-run `vendor/bin/pest <file>`.\n\n".implode("\n", array_slice($output, -60))."\n");
    exit(2);
}

remember_fingerprint('pest', $fingerprint);
hook_message('Pest passed: '.(preg_match('/"tests":(\d+)/', implode("
", $output), $m) ? $m[1].' tests' : 'OK'));

exit(0);
