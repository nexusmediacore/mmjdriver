import SectionPage from '@/Components/SectionPage';
import DataTable from '@/Components/DataTable';
import StatusBadge from '@/Components/UI/StatusBadge';

interface Trip {
    id: number;
    trip_number: string;
    destination: string;
    departure_time: string;
    status: string;
    driver: string;
    vehicle: string;
}

interface Props {
    trips: Trip[];
}

export default function DispatcherSchedule({ trips }: Props) {
    const columns = [
        { key: 'trip_number', label: 'No. Trip' },
        { key: 'destination', label: 'Tujuan' },
        { key: 'departure_time', label: 'Berangkat' },
        { key: 'driver', label: 'Driver' },
        { key: 'vehicle', label: 'Kendaraan' },
        {
            key: 'status',
            label: 'Status',
            render: (row: Trip) => <StatusBadge status={row.status} />,
        },
    ];

    return (
        <SectionPage title="Schedule (Next 7 Days)">
            <DataTable
                data={trips}
                columns={columns}
                searchPlaceholder="Cari jadwal (no. trip/tujuan)... (tekan /)"
                emptyMessage="Tidak ada jadwal"
            />
        </SectionPage>
    );
}
