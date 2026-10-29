<?php

return [
    'title' => 'Terminal',
    'subtitle' => 'Run bash commands from the browser.',

    'unlock' => [
        'heading' => 'Enter the terminal password',
        'password' => 'Terminal password',
        'submit' => 'Unlock',
    ],

    'forgot' => [
        'link' => 'Forgot password?',
        'sent' => 'A reset link was sent to :email. It is valid for 60 minutes.',
    ],

    'mail' => [
        'subject' => 'Reset your terminal password',
        'intro' => 'You asked to reset the terminal password. Use the button below to choose a new one.',
        'action' => 'Reset terminal password',
        'expires' => 'This link expires in :minutes minutes.',
        'ignore' => 'If you did not ask for this, you can ignore this email.',
    ],

    'password' => [
        'setup_heading' => 'Set a terminal password',
        'setup_hint' => 'No terminal password is set yet. Choose one to protect the terminal.',
        'reset_heading' => 'Reset terminal password',
        'change_heading' => 'Change terminal password',
        'current' => 'Current password',
        'new' => 'New password',
        'confirmation' => 'Confirm new password',
        'save' => 'Save password',
        'saved' => 'Terminal password saved.',
    ],

    'console' => [
        'command' => 'Command',
        'placeholder' => 'php artisan list',
        'run' => 'Run',
        'lock' => 'Lock',
        'welcome' => 'Type a bash command, e.g. "php artisan migrate:status" or "git status". Type "clear" to empty the screen.',
        'exit_code' => 'exit code :code',
    ],

    'errors' => [
        'wrong_password' => 'Wrong terminal password.',
        'not_configured' => 'No terminal password is set yet.',
        'locked' => 'The terminal is locked.',
        'timeout' => 'The command took longer than :seconds seconds and was stopped.',
        'artisan_only' => 'This server cannot start a shell, so only "php artisan ..." commands work here.',
        'blocked' => 'This command cannot run here because it never finishes.',
        'invalid_token' => 'This reset link is invalid or has expired.',
        'failed' => 'Something went wrong.',
    ],
];
