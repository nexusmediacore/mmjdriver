<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Perjalanan {{ $dateFrom }} - {{ $dateTo }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; font-size: 12px; }
        h1 { text-align: center; font-size: 18px; margin-bottom: 10px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 30px; }
        .stats { margin-bottom: 30px; }
        .stats table { width: 100%; border-collapse: collapse; }
        .stats td { padding: 8px; border: 1px solid #ddd; }
        .stats td:first-child { font-weight: bold; width: 40%; background-color: #f5f5f5; }
        h2 { font-size: 14px; margin-top: 20px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.data th, table.data td { padding: 6px; border: 1px solid #ddd; text-align: left; }
        table.data th { background-color: #f0f0f0; font-weight: bold; }
        .footer { margin-top: 40px; font-size: 11px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <h1>LAPORAN PERJALANAN DINAS</h1>
    <p class="subtitle">Periode: {{ $dateFrom }} s/d {{ $dateTo }}</p>

    <div class="stats">
        <h2>Statistik</h2>
        <table>
            <tr><td>Total Perjalanan</td><td>{{ $stats['total_trips'] }}</td></tr>
            <tr><td>Perjalanan Selesai</td><td>{{ $stats['completed_trips'] }}</td></tr>
            <tr><td>Perjalanan Dibatalkan</td><td>{{ $stats['cancelled_trips'] }}</td></tr>
            <tr><td>Perjalanan Pending</td><td>{{ $stats['pending_trips'] }}</td></tr>
            <tr><td>Total Penumpang</td><td>{{ $stats['total_passengers'] }}</td></tr>
            <tr><td>Perjalanan Urgent</td><td>{{ $stats['urgent_trips'] }}</td></tr>
        </table>
    </div>

    <h2>Per Departemen</h2>
    <table class="data">
        <tr><th>Departemen</th><th>Jumlah</th></tr>
        @forelse($byDepartment as $dept)
        <tr><td>{{ $dept['name'] }}</td><td>{{ $dept['count'] }}</td></tr>
        @empty
        <tr><td colspan="2">Tidak ada data</td></tr>
        @endforelse
    </table>

    <h2>Per Driver</h2>
    <table class="data">
        <tr><th>Driver</th><th>Jumlah</th></tr>
        @forelse($byDriver as $driver)
        <tr><td>{{ $driver['name'] }}</td><td>{{ $driver['count'] }}</td></tr>
        @empty
        <tr><td colspan="2">Tidak ada data</td></tr>
        @endforelse
    </table>

    <h2>Per Kendaraan</h2>
    <table class="data">
        <tr><th>Kendaraan</th><th>Jumlah</th></tr>
           @forelse($byVehicle as $vehicle)
           <tr><td>{{ $vehicle['name'] }}</td><td>{{ $vehicle['count'] }}</td></tr>
        @empty
        <tr><td colspan="2">Tidak ada data</td></tr>
        @endforelse
    </table>

    <h2>Detail Perjalanan</h2>
    <table class="data">
        <tr><th>No. Perjalanan</th><th>Tujuan</th><th>Tgl</th><th>Driver</th><th>Kendaraan</th><th>Penumpang</th><th>Status</th><th>Prioritas</th></tr>
        @foreach($trips as $trip)
        <tr>
            <td>{{ $trip->trip_number }}</td>
            <td>{{ $trip->destination }}</td>
            <td>{{ $trip->trip_date?->format('d-m-Y') }}</td>
            <td>{{ $trip->driver?->user?->name ?? '-' }}</td>
            <td>{{ $trip->vehicle?->plate_number ?? '-' }}</td>
            <td>{{ $trip->passenger_count }}</td>
            <td>{{ $trip->status }}</td>
            <td>{{ $trip->priority }}</td>
        </tr>
        @endforeach
    </table>

    <div class="footer">
        <p>Dicetak pada {{ now()->format('d-m-Y H:i:s') }}</p>
    </div>
</body>
</html>