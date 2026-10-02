<?php

// Stop hook: run Larastan (PHPStan) when PHP files under app/ changed.
$input = json_decode(stream_get_contents(STDIN), true) ?: [];

if (! empty($input['stop_hook_active'])) {
    exit(0);
}

chdir(dirname(__DIR__, 2));

require __DIR__.'/lib/fingerprint.php';

$fingerprint = relevant_fingerprint(stop_check_pattern('larastan'));

if ($fingerprint === null || fingerprint_is_unchanged('larastan', $fingerprint)) {
    exit(0);
}

exec('php vendor/bin/phpstan analyse --no-progress --error-format=raw --memory-limit=2G 2>&1', $output, $code);

if ($code !== 0) {
    fwrite(STDERR, "Larastan found errors. Fix them, then re-run `vendor/bin/phpstan analyse`.\n\n".implode("\n", array_slice($output, -60))."\n");
    exit(2);
}

remember_fingerprint('larastan', $fingerprint);
hook_message('Larastan passed');

exit(0);
