<?php

// Stop hook: announce the Stop checks only when at least one of them will run.
$input = json_decode(stream_get_contents(STDIN), true) ?: [];

if (! empty($input['stop_hook_active'])) {
    exit(0);
}

chdir(dirname(__DIR__, 2));

require __DIR__.'/lib/fingerprint.php';

foreach (['graphify', 'larastan', 'pest', 'playwright'] as $name) {
    $fingerprint = relevant_fingerprint(stop_check_pattern($name));

    if ($fingerprint !== null && ! fingerprint_is_unchanged($name, $fingerprint)) {
        hook_message('Stop started: running graphify, Larastan, Pest and Playwright...');
        exit(0);
    }
}
