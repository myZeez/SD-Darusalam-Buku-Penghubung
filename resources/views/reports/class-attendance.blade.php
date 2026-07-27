<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Presensi {{ $schoolClass->name }}</title>
    <style>
        @page { margin: 18mm 14mm 16mm; }
        * { box-sizing: border-box; }
        body { color: #111827; font-family: DejaVu Sans, sans-serif; font-size: 9px; line-height: 1.4; }
        h1, h2, p { margin: 0; }
        .header { border-bottom: 2px solid #111827; margin-bottom: 10px; padding-bottom: 7px; text-align: center; }
        .header h1 { font-size: 16px; }
        .header h2 { font-size: 11px; margin-top: 2px; }
        .header p { color: #4b5563; margin-top: 2px; }
        .identity { border: 1px solid #d1d5db; border-collapse: collapse; margin-bottom: 9px; width: 100%; }
        .identity td { padding: 4px 6px; width: 33.333%; }
        .identity strong { display: inline-block; min-width: 76px; }
        .summary { border: 1px solid #d1d5db; border-collapse: collapse; margin-bottom: 10px; table-layout: fixed; width: 100%; }
        .summary td { border-right: 1px solid #d1d5db; padding: 5px; text-align: center; }
        .summary td:last-child { border-right: 0; }
        .summary span { color: #6b7280; display: block; font-size: 7px; }
        .summary strong { display: block; font-size: 12px; }
        .data { border-collapse: collapse; width: 100%; }
        .data th, .data td { border: 1px solid #d1d5db; padding: 4px 5px; text-align: left; vertical-align: top; }
        .data th { background: #f3f4f6; font-size: 8px; }
        .center { text-align: center !important; }
        .status { font-family: DejaVu Sans Mono, monospace; font-weight: bold; }
        .muted { color: #6b7280; }
        .signatures { border-collapse: collapse; margin-top: 22px; page-break-inside: avoid; table-layout: fixed; width: 100%; }
        .signatures td { padding: 0 16px; text-align: center; width: 50%; }
        .signature-space { height: 42px; }
        .signature-name { border-bottom: 1px solid #111827; display: inline-block; font-weight: bold; min-width: 160px; padding-bottom: 2px; }
        .footer { bottom: -10mm; color: #6b7280; font-size: 7px; left: 0; position: fixed; right: 0; text-align: center; }
    </style>
</head>
<body>
    @php
        $statusLabels = [
            'present' => 'H',
            'sick' => 'S',
            'permission' => 'I',
            'absent' => 'A',
        ];
        $sourceLabels = [
            'arrival' => 'Piket',
            'parent_submission' => 'Orang Tua',
            'teacher' => 'Wali Kelas',
            'manual' => 'Manual',
        ];
    @endphp

    <div class="footer">{{ $settings->school_name }} - Presensi {{ $schoolClass->name }}</div>

    <header class="header">
        <h1>{{ $settings->school_name }}</h1>
        <h2>Rekap Presensi Kelas Harian</h2>
        <p>{{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}</p>
    </header>

    <table class="identity">
        <tr>
            <td><strong>Kelas</strong> {{ $schoolClass->name }}</td>
            <td><strong>Wali Kelas</strong> {{ $schoolClass->teacher?->user?->name ?? '-' }}</td>
            <td><strong>Ruang</strong> {{ $schoolClass->room ?: '-' }}</td>
            <td><strong>Jumlah Siswa</strong> {{ $schoolClass->students->count() }}</td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td><span>Hadir</span><strong>{{ $summary['present'] }}</strong></td>
            <td><span>Sakit</span><strong>{{ $summary['sick'] }}</strong></td>
            <td><span>Izin</span><strong>{{ $summary['permission'] }}</strong></td>
            <td><span>Alpa</span><strong>{{ $summary['absent'] }}</strong></td>
            <td><span>Belum Dicatat</span><strong>{{ $schoolClass->students->count() - $records->count() }}</strong></td>
            <td><span>Terlambat</span><strong>{{ $records->where('is_late', true)->count() }}</strong></td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th class="center" style="width: 28px;">No.</th>
                <th style="width: 92px;">NIS</th>
                <th>Nama Siswa</th>
                <th class="center" style="width: 42px;">H/S/I/A</th>
                <th style="width: 64px;">Jam Datang</th>
                <th style="width: 68px;">Ketepatan</th>
                <th style="width: 72px;">Sumber</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($schoolClass->students as $student)
                @php($record = $records->get($student->id))
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $student->nis }}</td>
                    <td>{{ $student->name }}</td>
                    <td class="center status">{{ $record ? ($statusLabels[$record->status] ?? '-') : '-' }}</td>
                    <td>{{ $record?->arrival?->arrival_time ? substr($record->arrival->arrival_time, 0, 5) : '-' }}</td>
                    <td>
                        @if ($record?->is_late)
                            Terlambat
                        @elseif ($record?->arrival)
                            Tepat waktu
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $record ? ($sourceLabels[$record->source] ?? $record->source) : '-' }}</td>
                    <td>{{ $record?->notes ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td colspan="2">
                Wali Kelas
                <div class="signature-space"></div>
                <span class="signature-name">{{ $schoolClass->teacher?->user?->name ?? 'Belum ditentukan' }}</span>
                <div class="muted">NIP {{ $schoolClass->teacher?->nip ?: '-' }}</div>
            </td>
        </tr>
    </table>
</body>
</html>
