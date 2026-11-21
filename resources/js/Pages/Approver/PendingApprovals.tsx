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
    requestor: string;
    department: string;
    priority: string;
    passenger_count: number;
}

interface Props {
    pending_requests: Trip[];
}

export default function ApproverPendingApprovals({ pending_requests }: Props) {
    const columns = [
        { key: 'trip_number', label: 'No. Trip' },
        { key: 'destination', label: 'Tujuan' },
        { key: 'purpose', label: 'Keperluan' },
        { key: 'departure_time', label: 'Berangkat' },
        { key: 'requestor', label: 'Pemohon' },
        { key: 'department', label: 'Departemen' },
        { key: 'passenger_count', label: 'Penumpang' },
        {
            key: 'priority',
            label: 'Prioritas',
            render: (row: Trip) => <StatusBadge status={row.priority} />,
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
        <SectionPage title="Pending Approvals">
            <DataTable
                data={pending_requests}
                columns={columns}
                searchPlaceholder="Cari permintaan (no. trip/tujuan/pemohon)... (tekan /)"
                emptyMessage="Tidak ada permintaan yang menunggu persetujuan"
            />
        </SectionPage>
    );
}
