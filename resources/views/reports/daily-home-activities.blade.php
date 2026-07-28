<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Aktivitas Rumah {{ $isParent ? $student->name : $schoolClass->name }}</title>
    <style>
        @page { margin: 14mm 10mm 15mm; }
        * { box-sizing: border-box; }
        body { color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 8px; line-height: 1.35; }
        h1, h2, h3, p { margin: 0; }
        .header { border-bottom: 2px solid #7c3aed; margin-bottom: 9px; padding-bottom: 7px; text-align: center; }
        .header h1 { font-size: 16px; }
        .header h2 { color: #6d28d9; font-size: 11px; margin-top: 2px; }
        .header p { color: #667085; margin-top: 2px; }
        .identity, .summary, .data, .notes { border-collapse: collapse; width: 100%; }
        .identity { margin-bottom: 9px; }
        .identity td { border: 1px solid #d7deea; padding: 4px 6px; width: 25%; }
        .identity strong { color: #475467; display: block; font-size: 7px; margin-bottom: 1px; }
        .summary { margin-bottom: 10px; table-layout: fixed; }
        .summary td { background: #f5f3ff; border: 1px solid #ddd6fe; padding: 5px; text-align: center; }
        .summary span { color: #667085; display: block; font-size: 7px; }
        .summary strong { color: #6d28d9; display: block; font-size: 12px; }
        .section { margin-top: 11px; page-break-inside: avoid; }
        .section h3 { color: #6d28d9; font-size: 10px; margin-bottom: 4px; }
        .data { table-layout: fixed; }
        .data th, .data td, .notes th, .notes td { border: 1px solid #d7deea; padding: 4px; text-align: center; vertical-align: middle; }
        .data th, .notes th { background: #f2f4f7; color: #344054; font-size: 7px; }
        .data .student, .notes .student, .notes .note { text-align: left; }
        .done { color: #047857; font-weight: bold; }
        .pending { color: #98a2b3; }
        .submitted { color: #047857; font-weight: bold; }
        .not-submitted { color: #b54708; font-weight: bold; }
        .muted { color: #667085; }
        .footer { bottom: -10mm; color: #667085; font-size: 7px; left: 0; position: fixed; right: 0; text-align: center; }
    </style>
</head>
<body>
    @php
        $submitted = collect($rows)->filter(fn (array $row): bool => filled($row['submitted_at']))->count();
        $totalChecks = collect($rows)->sum(fn (array $row): int => collect($row['checks'])->filter()->count());
        $possibleChecks = count($rows) * collect($template)->sum(fn (array $group): int => count($group['items']));
    @endphp

    <div class="footer">{{ $settings->school_name }} - Aktivitas Rumah Harian</div>

    <header class="header">
        <h1>{{ $settings->school_name }}</h1>
        <h2>{{ $isParent ? 'Laporan Aktivitas Rumah Siswa' : 'Rekap Aktivitas Rumah Kelas' }}</h2>
        <p>{{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }}</p>
    </header>

    <table class="identity">
        <tr>
            @if ($isParent)
                <td><strong>NAMA SISWA</strong>{{ $student->name }}</td>
                <td><strong>NIS</strong>{{ $student->nis }}</td>
                <td><strong>KELAS</strong>{{ $schoolClass?->name ?? 'Belum ada kelas' }}</td>
                <td><strong>TANGGAL CETAK</strong>{{ now()->translatedFormat('d F Y H:i') }}</td>
            @else
                <td><strong>KELAS</strong>{{ $schoolClass->name }}</td>
                <td><strong>JUMLAH SISWA</strong>{{ count($rows) }} siswa aktif</td>
                <td><strong>SUDAH MENGISI</strong>{{ $submitted }} siswa</td>
                <td><strong>TANGGAL CETAK</strong>{{ now()->translatedFormat('d F Y H:i') }}</td>
            @endif
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td><span>Sudah Mengisi</span><strong>{{ $submitted }}</strong></td>
            <td><span>Belum Mengisi</span><strong>{{ count($rows) - $submitted }}</strong></td>
            <td><span>Aktivitas Dilakukan</span><strong>{{ $totalChecks }}/{{ $possibleChecks }}</strong></td>
        </tr>
    </table>

    @foreach ($template as $group)
        <section class="section">
            <h3>{{ $group['category'] }}</h3>
            <table class="data">
                <thead>
                    <tr>
                        <th style="width: 24px;">No.</th>
                        <th style="width: 105px;">Siswa</th>
                        <th style="width: 62px;">Status Isi</th>
                        @foreach ($group['items'] as $item)
                            <th>{{ $item['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="student"><strong>{{ $row['student']->name }}</strong><br><span class="muted">{{ $row['student']->nis }}</span></td>
                            <td class="{{ $row['submitted_at'] ? 'submitted' : 'not-submitted' }}">
                                {{ $row['submitted_at'] ? 'Diisi '.$row['submitted_at']->format('H:i') : 'Belum diisi' }}
                            </td>
                            @foreach ($group['items'] as $item)
                                <td class="{{ $row['checks'][$item['key']] ?? false ? 'done' : 'pending' }}">
                                    {{ $row['checks'][$item['key']] ?? false ? 'Ya' : '-' }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endforeach

    <section class="section">
        <h3>Catatan Orang Tua</h3>
        <table class="notes">
            <thead><tr><th style="width: 30px;">No.</th><th style="width: 155px;">Siswa</th><th>Catatan</th></tr></thead>
            <tbody>
                @forelse (collect($rows)->filter(fn (array $row): bool => filled($row['note'])) as $row)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="student">{{ $row['student']->name }}</td>
                        <td class="note">{{ $row['note'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="muted">Belum ada catatan untuk tanggal ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</body>
</html>
