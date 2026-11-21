import SectionPage from '@/Components/SectionPage';
import DataTable from '@/Components/DataTable';
import StatusBadge from '@/Components/UI/StatusBadge';

interface Driver {
    id: number;
    name: string;
    license_number: string;
    phone: string;
    vehicle_plate: string;
    status: string;
    willing_to_load: boolean;
    last_status_change_at: string;
}

interface Props {
    drivers: Driver[];
}

export default function DispatcherDriverAvailability({ drivers }: Props) {
    const columns = [
        { key: 'name', label: 'Nama' },
        { key: 'license_number', label: 'No. SIM' },
        { key: 'phone', label: 'Telepon' },
        { key: 'vehicle_plate', label: 'Plat Kendaraan' },
        {
            key: 'willing_to_load',
            label: 'Bantu Loading',
            render: (row: Driver) => (row.willing_to_load ? 'Ya' : 'Tidak'),
        },
        {
            key: 'status',
            label: 'Status',
            render: (row: Driver) => <StatusBadge status={row.status} variant="driver" pulse={row.status === 'STANDBY' || row.status === 'ON_TRIP'} />,
        },
    ];

    return (
        <SectionPage title="Driver Availability">
            <DataTable
                data={drivers}
                columns={columns}
                searchPlaceholder="Cari driver (nama/lisensi/plat)... (tekan /)"
                emptyMessage="Tidak ada driver tersedia"
            />
        </SectionPage>
    );
}
