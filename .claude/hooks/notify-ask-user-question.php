<?php

// PreToolUse hook: show a desktop toast when Claude asks the user a question (AskUserQuestion).
$input = json_decode(stream_get_contents(STDIN), true) ?: [];

require __DIR__.'/lib/fingerprint.php';

$question = $input['tool_input']['questions'][0]['question'] ?? 'Claude needs your input.';

desktop_toast('Claude Code: question for you', $question);

exit(0);
