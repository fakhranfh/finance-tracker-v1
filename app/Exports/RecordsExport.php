<?php

namespace App\Exports;

use App\Models\Record;
use Generator;
use Illuminate\Support\LazyCollection;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RecordsExport implements FromGenerator, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithMapping, WithStyles
{
    /**
     * @param  LazyCollection<int, Record>  $records
     * @param  array<int, string>  $headings
     */
    public function __construct(
        private readonly LazyCollection $records,
        private readonly array $headings,
        private readonly string $timezone,
        private readonly string $dateFormat,
    ) {}

    public function generator(): Generator
    {
        yield from $this->records;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return $this->headings;
    }

    /**
     * @param  Record  $row
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        return [
            $row->recorded_at->timezone($this->timezone)->format($this->dateFormat),
            $row->type->label(),
            $row->account->name,
            $row->toAccount->name ?? '',
            (float) $row->amount,
            $row->note ?? '',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return ['E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle($sheet->calculateWorksheetDimension())
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        return [1 => ['font' => ['bold' => true]]];
    }
}
