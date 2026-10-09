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
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RecordsExport implements FromGenerator, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithMapping, WithStyles
{
    /**
     * @param  LazyCollection<int, Record>  $records
     * @param  array<int, string>  $headings
     * @param  array<int, string>  $filterLines
     */
    public function __construct(
        private readonly LazyCollection $records,
        private readonly array $headings,
        private readonly string $timezone,
        private readonly string $dateFormat,
        private readonly array $filterLines = [],
    ) {}

    public function generator(): Generator
    {
        yield from $this->records;
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function headings(): array
    {
        $filters = array_map(fn (string $line) => [$line], $this->filterLines);

        return [[__('records.export_title')], ...$filters, [], $this->headings];
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
        return ['E' => '"Rp" #,##0.00'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        $headingRow = count($this->filterLines) + 3;
        $sheet->mergeCells('A1:F1');

        foreach (array_keys($this->filterLines) as $i) {
            $sheet->mergeCells('A'.($i + 2).':F'.($i + 2));
        }

        if ($this->filterLines !== []) {
            $sheet->getStyle('A2:F'.(count($this->filterLines) + 1))
                ->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
        }

        $sheet->getStyle('A'.$headingRow.':'.$sheet->getHighestColumn().$sheet->getHighestRow())
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            $headingRow => ['font' => ['bold' => true]],
        ];
    }
}
