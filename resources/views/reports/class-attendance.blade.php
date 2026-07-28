<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Presensi {{ $schoolClass->name }}</title>
    <style>
        @page { margin: 14mm 12mm 15mm; }
        * { box-sizing: border-box; }
        body { color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 8px; line-height: 1.35; }
        h1, h2, p { margin: 0; }
        .header { border-bottom: 2px solid #2563eb; margin-bottom: 9px; padding-bottom: 7px; text-align: center; }
        .header h1 { font-size: 16px; }
        .header h2 { color: #2563eb; font-size: 11px; margin-top: 2px; }
        .header p { color: #667085; margin-top: 2px; }
        .identity { border-collapse: collapse; margin-bottom: 9px; width: 100%; }
        .identity td { border: 1px solid #d7deea; padding: 4px 6px; width: 50%; }
        .identity strong { color: #475467; display: block; font-size: 7px; margin-bottom: 1px; }
        .summary { border-collapse: collapse; margin-bottom: 10px; table-layout: fixed; width: 100%; }
        .summary td { background: #eff6ff; border: 1px solid #cfe0ff; padding: 5px; text-align: center; }
        .summary span { color: #6b7280; display: block; font-size: 7px; }
        .summary strong { color: #1d4ed8; display: block; font-size: 12px; }
        .data { border-collapse: collapse; table-layout: fixed; width: 100%; }
        .data th, .data td { border: 1px solid #d7deea; padding: 4px; text-align: left; vertical-align: top; }
        .data th { background: #f2f4f7; color: #344054; font-size: 7px; }
        .center { text-align: center !important; }
        .status { font-family: DejaVu Sans Mono, monospace; font-weight: bold; }
        .muted { color: #667085; }
        .signatures { border-collapse: collapse; margin-top: 22px; page-break-inside: avoid; table-layout: fixed; width: 100%; }
        .signatures td { padding: 0 16px; text-align: center; width: 50%; }
        .signature-space { height: 42px; }
        .signature-name { border-bottom: 1px solid #172033; display: inline-block; font-weight: bold; min-width: 160px; padding-bottom: 2px; }
        .footer { bottom: -10mm; color: #667085; font-size: 7px; left: 0; position: fixed; right: 0; text-align: center; }
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
            <td><strong>KELAS</strong>{{ $schoolClass->name }}</td>
            <td><strong>WALI KELAS</strong>{{ $schoolClass->teacher?->user?->name ?? 'Belum ditentukan' }}</td>
        </tr>
        <tr>
            <td><strong>RUANG</strong>{{ $schoolClass->room ?: '-' }}</td>
            <td><strong>JUMLAH SISWA</strong>{{ $schoolClass->students->count() }} siswa aktif</td>
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
                <th style="width: 120px;">Siswa</th>
                <th class="center" style="width: 46px;">H/S/I/A</th>
                <th style="width: 122px;">Rincian Kehadiran</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($schoolClass->students as $student)
                @php($record = $records->get($student->id))
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td><strong>{{ $student->name }}</strong><br><span class="muted">{{ $student->nis }}</span></td>
                    <td class="center status">{{ $record ? ($statusLabels[$record->status] ?? '-') : '-' }}</td>
                    <td>
                        <strong>Jam:</strong> {{ $record?->arrival?->arrival_time ? substr($record->arrival->arrival_time, 0, 5) : '-' }}<br>
                        <strong>Status:</strong>
                        @if ($record?->is_late)
                            Terlambat
                        @elseif ($record?->arrival)
                            Tepat waktu
                        @else
                            -
                        @endif
                        <br><strong>Sumber:</strong> {{ $record ? ($sourceLabels[$record->source] ?? $record->source) : '-' }}
                    </td>
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
