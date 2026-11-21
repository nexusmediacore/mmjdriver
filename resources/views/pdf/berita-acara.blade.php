<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Berita Acara {{ $ba->ba_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h1 { text-align: center; font-size: 18px; margin-bottom: 30px; }
        .header { text-align: center; margin-bottom: 40px; }
        .header h2 { margin: 0; }
        .header p { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        td { padding: 8px; border: 1px solid #ddd; vertical-align: top; }
        td:first-child { font-weight: bold; width: 25%; background-color: #f5f5f5; }
        .signatures { margin-top: 60px; }
        .signature-block { display: inline-block; width: 45%; text-align: center; margin: 0 2%; vertical-align: top; }
        .signature-line { border-bottom: 1px solid #000; width: 200px; margin: 40px auto 5px; }
        .footer { margin-top: 40px; font-size: 12px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h2>BERITA ACARA</h2>
        <p>No: {{ $ba->ba_number }}</p>
        <p>Tgl: {{ $ba->created_at->format('d-m-Y H:i') }}</p>
    </div>

    <table>
        <tr>
            <td>No. Perjalanan</td>
            <td>{{ $trip->trip_number }}</td>
        </tr>
        <tr>
            <td>Pemohon</td>
            <td>{{ $ba->requestor_name }}</td>
        </tr>
        <tr>
            <td>Departemen</td>
            <td>{{ $ba->department_name }}</td>
        </tr>
        <tr>
            <td>Driver</td>
            <td>{{ $trip->driver?->user?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td>Kendaraan</td>
            <td>{{ $trip->vehicle?->plate_number ?? '-' }} - {{ $trip->vehicle?->brand }} {{ $trip->vehicle?->model }}</td>
        </tr>
        <tr>
            <td>Tujuan</td>
            <td>{{ $ba->destination }}</td>
        </tr>
        <tr>
            <td>Keperluan</td>
            <td>{{ $ba->purpose }}</td>
        </tr>
        <tr>
            <td>Waktu Berangkat</td>
            <td>{{ $ba->departure_time?->format('d-m-Y H:i') ?? '-' }}</td>
        </tr>
        <tr>
            <td>Waktu Kembali</td>
            <td>{{ $ba->return_time?->format('d-m-Y H:i') ?? '-' }}</td>
        </tr>
        <tr>
            <td>Jumlah Penumpang</td>
            <td>{{ $trip->passenger_count }}</td>
        </tr>
        <tr>
            <td>Catatan</td>
            <td>{{ $ba->notes ?? '-' }}</td>
        </tr>
    </table>

    <div class="signatures">
        <div class="signature-block">
            <p>Mengetahui,</p>
            <p>Pemohon / Pengajur</p>
            <div class="signature-line"></div>
            <p>{{ $ba->requestor_name }}</p>
        </div>
        <div class="signature-block">
            <p>Mengkonfirmasi,</p>
            <p>Driver</p>
            <div class="signature-line"></div>
            <p>{{ $trip->driver?->user?->name ?? '-' }}</p>
        </div>
    </div>

    <div class="footer">
        <p>Dicetak pada {{ now()->format('d-m-Y H:i:s') }}</p>
    </div>
</body>
</html>