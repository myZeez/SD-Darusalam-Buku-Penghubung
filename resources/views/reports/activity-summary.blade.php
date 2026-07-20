<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Siswa</title>
    <style>
        @page { margin: 18mm 15mm 16mm; }
        * { box-sizing: border-box; }
        body { color: #111827; font-family: DejaVu Sans, sans-serif; font-size: 9px; line-height: 1.45; }
        h1, h2, h3, p { margin: 0; }
        .page-break { page-break-before: always; }
        .document-header { border-bottom: 2px solid #111827; margin-bottom: 12px; padding-bottom: 8px; text-align: center; }
        .document-header h1 { font-size: 16px; letter-spacing: 0; }
        .document-header h2 { font-size: 11px; margin-top: 2px; }
        .document-header p { color: #4b5563; margin-top: 2px; }
        .identity { border: 1px solid #d1d5db; border-collapse: collapse; margin-bottom: 10px; width: 100%; }
        .identity td { padding: 5px 7px; vertical-align: top; width: 50%; }
        .identity strong { display: inline-block; min-width: 78px; }
        .summary { border: 1px solid #d1d5db; border-collapse: collapse; margin-bottom: 14px; table-layout: fixed; width: 100%; }
        .summary td { border-right: 1px solid #d1d5db; padding: 6px; text-align: center; }
        .summary td:last-child { border-right: 0; }
        .summary span { color: #6b7280; display: block; font-size: 7px; }
        .summary strong { display: block; font-size: 12px; margin-top: 1px; }
        .aspects { border: 1px solid #d1d5db; border-collapse: collapse; table-layout: fixed; width: 100%; }
        .aspects td { border-right: 1px solid #d1d5db; padding: 5px; vertical-align: top; width: 25%; }
        .aspects td:last-child { border-right: 0; }
        .aspects strong { display: block; font-size: 8px; }
        .aspects span { color: #4b5563; display: block; font-size: 7px; margin-top: 2px; }
        .aspects em { color: #92400e; display: block; font-size: 7px; font-style: normal; font-weight: bold; margin-top: 4px; }
        .section { margin-top: 14px; }
        .section-title { border-bottom: 1px solid #9ca3af; font-size: 11px; margin-bottom: 6px; padding-bottom: 3px; }
        table.data { border-collapse: collapse; width: 100%; }
        table.data th, table.data td { border: 1px solid #d1d5db; padding: 4px 5px; text-align: left; vertical-align: top; }
        table.data th { background: #f3f4f6; font-size: 8px; }
        .empty { border: 1px solid #d1d5db; color: #6b7280; padding: 8px; text-align: center; }
        .activity { border: 1px solid #d1d5db; margin-bottom: 7px; page-break-inside: avoid; }
        .activity-header { background: #f3f4f6; border-bottom: 1px solid #d1d5db; padding: 5px 7px; }
        .activity-header strong { font-size: 9px; }
        .activity-header span { color: #4b5563; float: right; }
        .activity-body { padding: 6px 7px; }
        .category { margin-bottom: 6px; page-break-inside: avoid; }
        .category:last-child { margin-bottom: 0; }
        .category-title { color: #92400e; font-size: 8px; font-weight: bold; margin-bottom: 2px; }
        .activity-items { border-collapse: collapse; width: 100%; }
        .activity-items td { border-top: 1px solid #e5e7eb; padding: 3px 2px; vertical-align: top; }
        .activity-items tr:first-child td { border-top: 0; }
        .activity-items .mark { font-family: DejaVu Sans Mono, monospace; width: 26px; }
        .activity-items .label { font-weight: bold; width: 31%; }
        .activity-note { background: #f9fafb; margin-top: 5px; padding: 5px 6px; }
        .approval { page-break-before: always; padding-top: 12px; text-align: center; }
        .approval h2 { font-size: 14px; }
        .approval p { color: #4b5563; margin-top: 4px; }
        .signatures { border-collapse: collapse; margin-top: 42px; page-break-inside: avoid; table-layout: fixed; width: 100%; }
        .signatures td { padding: 0 16px; text-align: center; vertical-align: top; width: 50%; }
        .signature-space { height: 70px; }
        .signature-name { border-bottom: 1px solid #111827; display: inline-block; font-weight: bold; min-width: 150px; padding-bottom: 2px; }
        .signature-detail { color: #4b5563; font-size: 8px; margin-top: 2px; }
        .footer { bottom: -10mm; color: #6b7280; font-size: 7px; left: 0; position: fixed; right: 0; text-align: center; }
    </style>
</head>
<body>
    @php
        $attendanceLabels = [
            'present' => 'Hadir',
            'sick' => 'Sakit',
            'permission' => 'Izin',
            'absent' => 'Alpa',
        ];
    @endphp

    <div class="footer">
        {{ $settings->school_name }} - Laporan periode {{ \Carbon\Carbon::parse($report['from'])->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($report['to'])->format('d/m/Y') }}
    </div>

    @forelse ($report['reports'] as $studentReport)
        @php
            $student = $studentReport['student'];
            $teacher = $student->class?->teacher;
            $teacherName = $teacher?->user?->name ?? 'Belum ditentukan';
            $parentName = $student->parent?->user?->name
                ?? $student->parent?->father_name
                ?? $student->parent?->mother_name
                ?? 'Belum ditentukan';
        @endphp

        <main @class(['page-break' => ! $loop->first])>
            <header class="document-header">
                <h1>{{ $settings->school_name }}</h1>
                <h2>Laporan Perkembangan dan Aktivitas Siswa</h2>
                <p>Periode {{ \Carbon\Carbon::parse($report['from'])->format('d/m/Y') }} sampai {{ \Carbon\Carbon::parse($report['to'])->format('d/m/Y') }}</p>
            </header>

            <table class="identity">
                <tr>
                    <td><strong>Nama Siswa</strong> {{ $student->name }}</td>
                    <td><strong>Kelas</strong> {{ $student->class?->name ?? 'Belum ditentukan' }}</td>
                </tr>
                <tr>
                    <td><strong>NIS</strong> {{ $student->nis }}</td>
                    <td><strong>Tahun Ajaran</strong> {{ $student->class?->academic_year ?? $settings->academic_year }}</td>
                </tr>
                <tr>
                    <td><strong>Guru Kelas</strong> {{ $teacherName }}</td>
                    <td><strong>Orang Tua</strong> {{ $parentName }}</td>
                </tr>
            </table>

            <table class="summary">
                <tr>
                    <td><span>Data Presensi</span><strong>{{ $studentReport['summary']['attendance'] }}</strong></td>
                    <td><span>Hadir</span><strong>{{ $studentReport['summary']['present'] }}</strong></td>
                    <td><span>Tepat Waktu</span><strong>{{ $studentReport['summary']['on_time'] }}</strong></td>
                    <td><span>Terlambat</span><strong>{{ $studentReport['summary']['late'] }}</strong></td>
                    <td><span>Laporan Sekolah</span><strong>{{ $studentReport['summary']['school_activities'] }}</strong></td>
                    <td><span>Laporan Rumah</span><strong>{{ $studentReport['summary']['home_activities'] }}</strong></td>
                </tr>
            </table>

            <section class="section">
                <h2 class="section-title">Empat Aspek Perkembangan</h2>
                <table class="aspects">
                    <tr>
                        @foreach ($studentReport['summary']['development_aspects'] as $aspect)
                            <td>
                                <strong>{{ $aspect['label'] }}</strong>
                                <span>{{ $aspect['description'] }}</span>
                                <em>{{ $aspect['observations'] ? $aspect['observations'].' catatan teramati' : 'Belum ada catatan' }}</em>
                            </td>
                        @endforeach
                    </tr>
                </table>
            </section>

            <section class="section">
                <h2 class="section-title">Presensi dan Kedatangan</h2>
                @if ($studentReport['attendance_records']->isEmpty())
                    <div class="empty">Belum ada presensi pada periode ini.</div>
                @else
                    <table class="data">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Presensi</th>
                                <th>Waktu Datang</th>
                                <th>Ketepatan</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($studentReport['attendance_records'] as $attendance)
                                <tr>
                                    <td>{{ $attendance->attendance_date->format('d/m/Y') }}</td>
                                    <td>{{ $attendanceLabels[$attendance->status] ?? $attendance->status }}</td>
                                    <td>{{ $attendance->arrival?->arrival_time ? substr($attendance->arrival->arrival_time, 0, 5) : '-' }}</td>
                                    <td>
                                        @if ($attendance->is_late)
                                            Terlambat
                                        @elseif ($attendance->arrival)
                                            Tepat waktu
                                        @else
                                            Tidak tercatat
                                        @endif
                                    </td>
                                    <td>{{ $attendance->notes ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>

            <section class="section">
                <h2 class="section-title">Aktivitas Sekolah</h2>
                @forelse ($studentReport['school_activities'] as $activity)
                    <div class="activity">
                        <div class="activity-header">
                            <strong>{{ $activity->activity_date->format('d/m/Y') }}</strong>
                            <span>{{ $attendanceLabels[$activity->attendance] ?? $activity->attendance }} - {{ $activity->teacher?->user?->name ?? $teacherName }}</span>
                        </div>
                        <div class="activity-body">
                            @foreach ($activity->resolvedActivityGroups() as $group)
                                <div class="category">
                                    <div class="category-title">{{ $group['category'] }}</div>
                                    <table class="activity-items">
                                        @foreach ($group['items'] as $item)
                                            <tr>
                                                <td class="mark">{{ $item['type'] === 'checklist' ? ($item['checked'] ? '[x]' : '[ ]') : '[T]' }}</td>
                                                <td class="label">{{ $item['label'] }}</td>
                                                <td>
                                                    @if ($item['type'] === 'checklist')
                                                        {{ $item['checked'] ? 'Sudah dilakukan' : 'Belum dilakukan' }}
                                                    @else
                                                        {{ filled($item['text']) ? $item['text'] : 'Belum ada catatan' }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </div>
                            @endforeach
                            @if ($activity->note)
                                <div class="activity-note"><strong>Catatan guru:</strong> {{ $activity->note }}</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="empty">Belum ada aktivitas sekolah pada periode ini.</div>
                @endforelse
            </section>

            <section class="section">
                <h2 class="section-title">Aktivitas Rumah</h2>
                @forelse ($studentReport['home_activities'] as $activity)
                    <div class="activity">
                        <div class="activity-header">
                            <strong>{{ $activity->activity_date->format('d/m/Y') }}</strong>
                            <span>{{ $activity->parent?->user?->name ?? $parentName }}</span>
                        </div>
                        <div class="activity-body">
                            @foreach ($activity->resolvedActivityGroups() as $group)
                                <div class="category">
                                    <div class="category-title">{{ $group['category'] }}</div>
                                    <table class="activity-items">
                                        @foreach ($group['items'] as $item)
                                            <tr>
                                                <td class="mark">{{ $item['type'] === 'checklist' ? ($item['checked'] ? '[x]' : '[ ]') : '[T]' }}</td>
                                                <td class="label">{{ $item['label'] }}</td>
                                                <td>
                                                    @if ($item['type'] === 'checklist')
                                                        {{ $item['checked'] ? 'Sudah dilakukan' : 'Belum dilakukan' }}
                                                    @else
                                                        {{ filled($item['text']) ? $item['text'] : 'Belum ada catatan' }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </div>
                            @endforeach
                            @if ($activity->note)
                                <div class="activity-note"><strong>Catatan orang tua:</strong> {{ $activity->note }}</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="empty">Belum ada aktivitas rumah pada periode ini.</div>
                @endforelse
            </section>

            <section class="approval">
                <h2>Lembar Pengesahan</h2>
                <p>
                    {{ $student->name }} - {{ $student->class?->name ?? 'Belum ada kelas' }}<br>
                    Periode {{ \Carbon\Carbon::parse($report['from'])->format('d/m/Y') }} sampai {{ \Carbon\Carbon::parse($report['to'])->format('d/m/Y') }}
                </p>

                <table class="signatures">
                    <tr>
                        <td>
                            Mengetahui,<br>Guru Kelas
                            <div class="signature-space"></div>
                            <span class="signature-name">{{ $teacherName }}</span>
                            <div class="signature-detail">NIP {{ $teacher?->nip ?: '-' }}</div>
                        </td>
                        <td>
                            Mengetahui,<br>Orang Tua/Wali
                            <div class="signature-space"></div>
                            <span class="signature-name">{{ $parentName }}</span>
                        </td>
                    </tr>
                </table>
            </section>
        </main>
    @empty
        <header class="document-header">
            <h1>{{ $settings->school_name }}</h1>
            <h2>Laporan Perkembangan dan Aktivitas Siswa</h2>
        </header>
        <div class="empty">Tidak ada siswa yang dapat ditampilkan pada laporan ini.</div>
    @endforelse
</body>
</html>
