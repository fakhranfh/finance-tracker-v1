<?php

namespace App\Imports;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Reads the first sheet of an xlsx, xls or csv file into plain rows.
 * Date cells come back as "Y-m-d H:i:s" strings, empty cells as null.
 */
class SpreadsheetReader
{
    /**
     * `rows` is keyed by the row number in the sheet, with fully empty rows left out.
     *
     * @return array{header: array<int, string>, rows: array<int, array<int, string|int|float|null>>}
     */
    public function read(string $path, int $maxRows): array
    {
        $sheet = IOFactory::load($path)->getActiveSheet();

        if ($sheet->getHighestDataRow() > $maxRows + 1) {
            throw new TooManyRowsException($maxRows);
        }

        $columnCount = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        $header = [];
        $rows = [];

        foreach ($sheet->getRowIterator() as $row) {
            $cells = [];
            $iterator = $row->getCellIterator('A', $sheet->getHighestDataColumn());
            $iterator->setIterateOnlyExistingCells(false);

            foreach ($iterator as $cell) {
                $cells[] = $this->value($cell);
            }

            if ($row->getRowIndex() === 1) {
                $header = array_map(
                    fn (string|int|float|null $name, int $index) => $name === null ? 'Column '.($index + 1) : (string) $name,
                    $cells,
                    array_keys($cells),
                );

                continue;
            }

            if (array_filter($cells, fn ($value) => $value !== null) !== []) {
                $rows[$row->getRowIndex()] = array_pad($cells, $columnCount, null);
            }
        }

        return ['header' => $header, 'rows' => $rows];
    }

    private function value(Cell $cell): string|int|float|null
    {
        $value = $cell->getCalculatedValue();

        if ($value instanceof RichText) {
            $value = $value->getPlainText();
        }

        if ((is_int($value) || is_float($value)) && Date::isDateTime($cell)) {
            return Date::excelToDateTimeObject($value)->format('Y-m-d H:i:s');
        }

        if (is_string($value)) {
            $value = trim($value);

            return $value === '' ? null : $value;
        }

        return is_int($value) || is_float($value) ? $value : null;
    }
}
