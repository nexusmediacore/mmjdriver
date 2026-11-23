import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import DataTableWithActions from '@/Components/DataTableWithActions';
import StatusBadge from '@/Components/UI/StatusBadge';
import { TruckIcon, PencilIcon, KeyIcon, TrashIcon } from '@heroicons/react/24/outline';

interface Vehicle {
    id: number;
    plate_number: string;
    brand: string;
    model: string;
    type: string;
    year: number;
    passenger_capacity: number;
    status: string;
    last_service_date: string;
    authorized_driver_ids: number[];
}

interface Props {
    vehicles: Vehicle[];
}

export default function AdminVehicles({ vehicles }: Props) {
    const columns = [
        { key: 'plate_number', label: 'Plat Nomor' },
        { key: 'brand', label: 'Merk' },
        { key: 'model', label: 'Model' },
        { key: 'type', label: 'Tipe' },
        { key: 'year', label: 'Tahun' },
        { key: 'passenger_capacity', label: 'Kapasitas' },
        {
            key: 'status',
            label: 'Status',
            render: (row: Vehicle) => <StatusBadge status={row.status ?? 'AVAILABLE'} variant="vehicle" />,
        },
        { key: 'last_service_date', label: 'Servis Terakhir' },
        {
            key: 'authorized_driver_ids',
            label: 'Driver',
            render: (row: Vehicle) => (
                <span className="inline-flex items-center gap-1 text-sm">
                    <TruckIcon className="h-4 w-4 text-[#2563EB]" />
                    {row.authorized_driver_ids.length > 0
                        ? `${row.authorized_driver_ids.length} driver`
                        : 'Belum diatur'}
                </span>
            ),
        },
        {
            key: 'id',
            label: 'Aksi',
        },
    ];

    const renderActions = (row: Vehicle, helpers: { edit: () => void; remove: () => void }) => (
        <div className="flex items-center gap-2">
            <button
                type="button"
                onClick={helpers.edit}
                aria-label="Edit kendaraan"
                className="inline-flex items-center gap-1 rounded-lg border border-[#E2E8F0] bg-white px-2.5 py-1.5 text-xs font-semibold text-[#334155] transition hover:border-[#BFDBFE] hover:bg-[#EFF6FF] hover:text-[#2563EB]"
            >
                <PencilIcon className="h-3.5 w-3.5" />
                Edit
            </button>
            <Link
                href={route('admin.vehicles.authorized-drivers', row.id)}
                className="inline-flex items-center gap-1 rounded-lg border border-[#E2E8F0] bg-white px-2.5 py-1.5 text-xs font-semibold text-[#334155] transition hover:border-[#BFDBFE] hover:bg-[#EFF6FF] hover:text-[#2563EB]"
            >
                <KeyIcon className="h-3.5 w-3.5" />
                Atur Driver
            </Link>
            <button
                type="button"
                onClick={helpers.remove}
                aria-label="Hapus kendaraan"
                className="inline-flex items-center justify-center rounded-lg border border-[#E2E8F0] bg-white p-1.5 text-[#64748B] transition hover:border-[#FECACA] hover:bg-[#FEF2F2] hover:text-[#DC2626]"
            >
                <TrashIcon className="h-3.5 w-3.5" />
            </button>
        </div>
    );

    const formFields: any[] = [
        { name: 'plate_number', label: 'Plat Nomor', type: 'text', required: true },
        { name: 'brand', label: 'Merk', type: 'text', required: true },
        { name: 'model', label: 'Model', type: 'text' },
        { name: 'type', label: 'Tipe', type: 'text' },
        { name: 'year', label: 'Tahun', type: 'number' },
        { name: 'passenger_capacity', label: 'Kapasitas Penumpang', type: 'number' },
        {
            name: 'status',
            label: 'Status',
            type: 'select',
            required: true,
            options: [
                { value: 'AVAILABLE', label: 'AVAILABLE' },
                { value: 'ASSIGNED', label: 'ASSIGNED' },
                { value: 'ON_TRIP', label: 'ON_TRIP' },
                { value: 'MAINTENANCE', label: 'MAINTENANCE' },
                { value: 'OUT_OF_SERVICE', label: 'OUT_OF_SERVICE' },
            ],
        },
        { name: 'notes', label: 'Catatan', type: 'textarea' },
    ];

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-[#0F172A]">Vehicle Management</h2>}>
            <Head title="Vehicle Management" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <DataTableWithActions<Vehicle>
                        data={vehicles}
                        columns={columns}
                    searchPlaceholder="Cari kendaraan (plat/merk/model/tipe)... (tekan /)"
                        emptyMessage="Tidak ada kendaraan"
                        resourceName="Kendaraan"
                        formFields={formFields}
                        storeRoute={route('admin.vehicles.store')}
                        updateRoute={(row) => route('admin.vehicles.update', row.id)}
                        deleteRoute={(row) => route('admin.vehicles.destroy', row.id)}
                        renderActions={renderActions}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
