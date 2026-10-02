<?php

// Stop hook: runs graphify, Larastan, Pest and Playwright one after another, then shows a
// single desktop notification once all of them are done (Claude Code runs
// separate hooks in parallel, so this wrapper is what makes "all finished" known).
$input = stream_get_contents(STDIN);

require __DIR__.'/lib/fingerprint.php';

$steps = ['run-graphify-on-change.php', 'run-larastan-on-change.php', 'run-pest-on-change.php', 'run-playwright-on-change.php'];

$messages = [];
$failures = [];

foreach ($steps as $step) {
    $process = proc_open(
        [PHP_BINARY, __DIR__.'/'.$step],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
    );

    fwrite($pipes[0], $input);
    fclose($pipes[0]);

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($process);

    $message = json_decode($stdout, true)['systemMessage'] ?? null;

    if ($message !== null) {
        $messages[] = $message;
    }

    if ($code !== 0) {
        $failures[] = trim($stderr);
        $messages[] = 'FAILED: '.strtok(trim($stderr), "\n");
    }
}

if ($messages === []) {
    exit(0);
}

$summary = implode(' | ', $messages);
$title = $failures === [] ? 'Claude Code: checks passed' : 'Claude Code: checks failed';

desktop_toast($title, $summary);

if ($failures !== []) {
    fwrite(STDERR, implode("\n\n", $failures)."\n");
    exit(2);
}

echo json_encode(['systemMessage' => $summary]);
exit(0);
