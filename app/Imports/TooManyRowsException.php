<?php

namespace App\Imports;

use RuntimeException;

class TooManyRowsException extends RuntimeException
{
    public function __construct(public readonly int $maxRows)
    {
        parent::__construct("The file has more than {$maxRows} rows.");
    }
}
