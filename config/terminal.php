<?php

return [
    /*
    | Seconds a terminal command may run before it is killed.
    | The terminal password itself lives (hashed) in the settings table.
    */
    'timeout' => 60,

    /*
    | Run commands through bash. When false (or when proc_open is disabled, as on many
    | shared hosts) only "php artisan ..." commands work, run inside the PHP process.
    */
    'shell' => true,

    /*
    | Artisan commands that never finish, refused in that artisan-only mode.
    */
    'blocked' => ['serve', 'tinker', 'queue:work', 'queue:listen', 'schedule:work', 'pail', 'db'],
];
