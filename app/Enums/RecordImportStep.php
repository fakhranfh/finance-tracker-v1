<?php

namespace App\Enums;

/** The step an unfinished record import is waiting at. */
enum RecordImportStep: string
{
    case Columns = 'columns';
    case Values = 'values';
}
