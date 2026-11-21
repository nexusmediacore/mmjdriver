import SectionPage from '@/Components/SectionPage';
import DataTable from '@/Components/DataTable';
import StatusBadge from '@/Components/UI/StatusBadge';

interface Vehicle {
    id: number;
    plate_number: string;
    brand: string;
    model: string;
    type: string;
    status: string;
    primary_driver: string;
}

interface Props {
    vehicles: Vehicle[];
}

export default function DispatcherVehicleAvailability({ vehicles }: Props) {
    const columns = [
        { key: 'plate_number', label: 'Plat Nomor' },
        { key: 'brand', label: 'Merk' },
        { key: 'model', label: 'Model' },
        { key: 'type', label: 'Jenis' },
        { key: 'primary_driver', label: 'Driver Utama' },
        {
            key: 'status',
            label: 'Status',
            render: (row: Vehicle) => <StatusBadge status={row.status} variant="vehicle" />,
        },
    ];

    return (
        <SectionPage title="Vehicle Availability">
            <DataTable
                data={vehicles}
                columns={columns}
                searchPlaceholder="Cari kendaraan (plat/merk/model)... (tekan /)"
                emptyMessage="Tidak ada kendaraan tersedia"
            />
        </SectionPage>
    );
}
