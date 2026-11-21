import SectionPage from '@/Components/SectionPage';
import DataTable from '@/Components/DataTable';
import StatusBadge from '@/Components/UI/StatusBadge';
import { Link } from '@inertiajs/react';

interface Task {
    id: number;
    trip_number: string;
    destination: string;
    purpose: string;
    departure_time: string;
    status: string;
    priority: string;
    vehicle: string;
    requestor: string;
}

interface Props {
    tasks: Task[];
}

export default function DriverMyTasks({ tasks }: Props) {
    const columns = [
        { key: 'trip_number', label: 'No. Trip' },
        { key: 'destination', label: 'Tujuan' },
        { key: 'purpose', label: 'Keperluan' },
        { key: 'departure_time', label: 'Berangkat' },
        { key: 'vehicle', label: 'Kendaraan' },
        { key: 'requestor', label: 'Pemohon' },
        {
            key: 'priority',
            label: 'Prioritas',
            render: (row: Task) => <StatusBadge status={row.priority} />,
        },
        {
            key: 'status',
            label: 'Status',
            render: (row: Task) => <StatusBadge status={row.status} pulse={row.status === 'ON_TRIP'} />,
        },
        {
            key: 'id',
            label: 'Action',
            render: (row: Task) => (
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
        <SectionPage
            title="My Tasks"
            eyebrow="Tugas Saya"
            description="Daftar perjalanan yang ditugaskan kepada Anda beserta status dan kendaraannya."
        >
            <DataTable
                data={tasks}
                columns={columns}
                emptyMessage="Tidak ada tugas yang ditugaskan"
                emptyDescription="Tugas baru akan muncul di sini saat dispatcher menugaskan Anda."
                searchPlaceholder="Cari tugas (no. trip/tujuan/pemohon)... (tekan /)"
            />
        </SectionPage>
    );
}
