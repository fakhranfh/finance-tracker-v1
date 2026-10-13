<?php

namespace App\Repositories\Concerns;

use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Lets a service run several repository calls atomically without touching the DB facade itself.
 */
trait RunsTransactions
{
    /**
     * Run the callback inside a database transaction and return its result.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function transaction(Closure $callback): mixed
    {
        return DB::transaction($callback);
    }
}
