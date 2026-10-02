<?php

// Stop hook: refresh the graphify knowledge graph only when project files changed.
$input = json_decode(stream_get_contents(STDIN), true) ?: [];

if (! empty($input['stop_hook_active'])) {
    exit(0);
}

$root = dirname(__DIR__, 2);
chdir($root);

require __DIR__.'/lib/fingerprint.php';

$relevant = stop_check_pattern('graphify');
$fingerprint = relevant_fingerprint($relevant);

if ($fingerprint === null || fingerprint_is_unchanged('graphify', $fingerprint)) {
    exit(0);
}

exec('graphify update . 2>&1', $output, $code);

if ($code !== 0) {
    fwrite(STDERR, "graphify update failed:\n".implode("\n", array_slice($output, -20))."\n");
} else {
    remember_fingerprint('graphify', $fingerprint);
    hook_message('graphify graph updated.');
}

exit(0);
