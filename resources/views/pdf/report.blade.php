<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Perjalanan {{ $dateFrom }} - {{ $dateTo }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; font-size: 11px; }
        h1 { text-align: center; font-size: 16px; margin-bottom: 5px; }
        .subtitle { text-align: center; color: #666; font-size: 11px; margin-bottom: 15px; }
        .header-bar { border-bottom: 2px solid #333; padding-bottom: 5px; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #999; padding: 4px 6px; text-align: left; font-size: 10px; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .stats-table td:first-child { font-weight: bold; width: 50%; background-color: #f9f9f9; }
        .footer { margin-top: 20px; font-size: 9px; color: #666; border-top: 1px solid #ccc; padding-top: 5px; text-align: center; }
    </style>
</head>
<body>
    <div class="header-bar">
        <h1>LAPORAN PERJALANAN DINAS</h1>
        <p class="subtitle">Periode: {{ $dateFrom }} s/d {{ $dateTo }}</p>
    </div>

    <table class="stats-table">
        <tr><td>Total Perjalanan</td><td>{{ $stats['total_trips'] }}</td></tr>
        <tr><td>Perjalanan Selesai</td><td>{{ $stats['completed_trips'] }}</td></tr>
        <tr><td>Perjalanan Dibatalkan</td><td>{{ $stats['cancelled_trips'] }}</td></tr>
        <tr><td>Perjalanan Pending</td><td>{{ $stats['pending_trips'] }}</td></tr>
        <tr><td>Total Penumpang</td><td>{{ $stats['total_passengers'] }}</td></tr>
        <tr><td>Perjalanan Urgent</td><td>{{ $stats['urgent_trips'] }}</td></tr>
    </table>

    <table>
        <tr><th>Departemen</th><th>Jumlah</th></tr>
        @forelse($byDepartment as $dept)
        <tr><td>{{ $dept['name'] }}</td><td>{{ $dept['count'] }}</td></tr>
        @empty
        <tr><td colspan="2">Tidak ada data</td></tr>
        @endforelse
    </table>

    <table>
        <tr><th>Driver</th><th>Jumlah</th></tr>
        @forelse($byDriver as $driver)
        <tr><td>{{ $driver['name'] }}</td><td>{{ $driver['count'] }}</td></tr>
        @empty
        <tr><td colspan="2">Tidak ada data</td></tr>
        @endforelse
    </table>

    <table>
        <tr><th>Kendaraan</th><th>Jumlah</th></tr>
        @forelse($byVehicle as $vehicle)
        <tr><td>{{ $vehicle['name'] }}</td><td>{{ $vehicle['count'] }}</td></tr>
        @empty
        <tr><td colspan="2">Tidak ada data</td></tr>
        @endforelse
    </table>

    <table>
        <tr>
            <th>No. Perjalanan</th>
            <th>Tujuan</th>
            <th>Tgl</th>
            <th>Driver</th>
            <th>Kendaraan</th>
            <th>Penumpang</th>
            <th>Status</th>
            <th>Prioritas</th>
        </tr>
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
        @if($trips->isEmpty())
        <tr><td colspan="8">Tidak ada data</td></tr>
        @endif
    </table>

    <div class="footer">
        <p>Dicetak pada {{ now()->format('d-m-Y H:i:s') }}</p>
    </div>
</body>
</html>
