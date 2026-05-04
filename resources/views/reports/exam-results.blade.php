<!DOCTYPE html>
<html>
<head>
    <title>Laporan Hasil Ujian</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 5px 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { bg-color: #f2f2f2; }
        .footer { margin-top: 50px; text-align: right; }
        .signature { display: inline-block; width: 200px; text-align: center; }
        .metadata { margin-bottom: 20px; }
        .metadata table { border: none; }
        .metadata td { border: none; padding: 2px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN HASIL UJIAN</h1>
        <p>{{ $school->name ?? 'Makassar Ujian Platform' }}</p>
    </div>

    <div class="metadata">
        <table>
            <tr>
                <td width="120">Judul Ujian</td>
                <td width="10">:</td>
                <td><strong>{{ $exam->title }}</strong></td>
            </tr>
            <tr>
                <td>Mata Pelajaran</td>
                <td>:</td>
                <td>{{ $exam->subject->name ?? '-' }}</td>
            </tr>
            <tr>
                <td>Kelas</td>
                <td>:</td>
                <td>{{ $exam->gradeLevel->name ?? '-' }}</td>
            </tr>
            <tr>
                <td>Tanggal Cetak</td>
                <td>:</td>
                <td>{{ $date }}</td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th width="30">No</th>
                <th>Nama Siswa</th>
                <th>Nilai</th>
                <th>Status</th>
                <th>Mulai</th>
                <th>Selesai</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attempts as $index => $attempt)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $attempt->user->name }}</td>
                <td>{{ $attempt->score ?? 0 }}</td>
                <td>{{ ucfirst($attempt->status) }}</td>
                <td>{{ $attempt->started_at->format('H:i') }}</td>
                <td>{{ $attempt->completed_at ? $attempt->completed_at->format('H:i') : '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <div class="signature">
            <p>Makassar, {{ $date }}</p>
            <p>Kepala Sekolah / Panitia Ujian</p>
            <br><br><br>
            <p><strong>( ____________________ )</strong></p>
        </div>
    </div>
</body>
</html>
