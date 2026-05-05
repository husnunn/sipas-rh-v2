<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 1.5rem; color: #111; }
        h1 { font-size: 1.25rem; margin-bottom: 0.25rem; }
        .meta { font-size: 0.85rem; color: #444; margin-bottom: 1rem; }
        table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
        th, td { border: 1px solid #ccc; padding: 0.35rem 0.5rem; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        tr:nth-child(even) { background: #fafafa; }
        @media print {
            body { margin: 0.5rem; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="meta">Zona waktu: {{ $school_timezone }} — dicetak {{ now()->timezone($school_timezone)->format('d M Y, H:i') }}</p>

    <p class="no-print" style="margin-bottom:1rem;">
        <button type="button" onclick="window.print()">Cetak / simpan PDF</button>
    </p>

    <table>
        <thead>
            <tr>
                <th>Sumber</th>
                <th>Waktu</th>
                <th>Siswa</th>
                <th>NIS</th>
                <th>Kelas</th>
                <th>Mapel</th>
                <th>Tahun ajaran</th>
                <th>Sem.</th>
                <th>Tipe</th>
                <th>Status</th>
                <th>Lokasi</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['feed_source_label'] ?? '—' }}</td>
                    <td>{{ $row['attendance_time'] ? \Illuminate\Support\Carbon::parse($row['attendance_time'])->timezone($school_timezone)->format('d M Y H:i') : '—' }}</td>
                    <td>{{ $row['user']['name'] ?? '—' }}</td>
                    <td>{{ $row['nis'] ?? '—' }}</td>
                    <td>{{ $row['class']['name'] ?? '—' }}</td>
                    <td>{{ $row['subject']['name'] ?? '—' }}</td>
                    <td>{{ $row['school_year']['name'] ?? '—' }}</td>
                    <td>{{ $row['schedule_semester'] ?? '—' }}</td>
                    <td>{{ $row['attendance_type_label'] ?? $row['attendance_type'] ?? '—' }}</td>
                    <td>{{ $row['status'] ?? '—' }}</td>
                    <td>{{ $row['site']['name'] ?? '—' }}</td>
                    <td>{{ $row['reason_detail'] ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12">Tidak ada data untuk filter ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
