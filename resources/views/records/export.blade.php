<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('records.export_title') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        h1 { font-size: 14px; margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #f0f0f0; }
        td.amount { text-align: right; white-space: nowrap; }
    </style>
</head>
<body>
    <h1>{{ __('records.export_title') }}</h1>
    @if (! empty($filterLines))
        <div style="margin: 0 0 14px; padding: 6px 8px; border: 1px solid #ccc; background: #fafafa;">
            @foreach ($filterLines as $line)
                <div style="margin: 0 0 2px;">{{ $line }}</div>
            @endforeach
        </div>
    @endif
    <table>
        <thead>
            <tr>
                @foreach ($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $record)
                <tr>
                    <td>{{ $record->recorded_at->timezone($timezone)->format($dateFormat) }}</td>
                    <td>{{ $record->type->label() }}</td>
                    <td>{{ $record->account->name }}</td>
                    <td>{{ $record->toAccount?->name }}</td>
                    <td class="amount">Rp {{ number_format((float) $record->amount, 2, ',', '.') }}</td>
                    <td>{{ $record->note }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
