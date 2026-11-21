import SectionPage from '@/Components/SectionPage';
import DataTable from '@/Components/DataTable';
import StatusBadge from '@/Components/UI/StatusBadge';
import { Link } from '@inertiajs/react';

interface Trip {
    id: number;
    trip_number: string;
    destination: string;
    purpose: string;
    departure_time: string;
    status: string;
    priority: string;
    vehicle: string;
}

interface Props {
    trips: Trip[];
}

export default function DriverTripHistory({ trips }: Props) {
    const columns = [
        { key: 'trip_number', label: 'No. Trip' },
        { key: 'destination', label: 'Tujuan' },
        { key: 'purpose', label: 'Keperluan' },
        { key: 'departure_time', label: 'Berangkat' },
        { key: 'vehicle', label: 'Kendarawan' },
        {
            key: 'priority',
            label: 'Prioritas',
            render: (row: Trip) => <StatusBadge status={row.priority} />,
        },
        {
            key: 'status',
            label: 'Status',
            render: (row: Trip) => <StatusBadge status={row.status} />,
        },
        {
            key: 'id',
            label: 'Action',
            render: (row: Trip) => (
                <Link
                    href={route('trips.show', row.id)}
                    className="text-[#2563EB] hover:text-[#1D4ED8]"
                >
                    Detail
                </Link>
            ),
        },
    ];

    return (
        <SectionPage title="Trip History">
            <DataTable
                data={trips}
                columns={columns}
                searchPlaceholder="Cari riwayat (no. trip/tujuan)... (tekan /)"
                emptyMessage="Tidak ada riwayat perjalanan"
            />
        </SectionPage>
    );
}
