<?php

return [

    /*
    | Refresh an account's balance snapshot once this many records
    | have been added since the last snapshot.
    */
    'snapshot_threshold' => (int) env('BALANCE_SNAPSHOT_THRESHOLD', 100),

];
