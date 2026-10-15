<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Record import batch size
    |--------------------------------------------------------------------------
    |
    | Rows handled per request while scanning an uploaded file and while
    | importing it, so the import page can show real progress.
    |
    */

    'batch_size' => (int) env('RECORD_IMPORT_BATCH_SIZE', 500),

];
