import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import DataTableWithActions from '@/Components/DataTableWithActions';
import { PencilSquareIcon, TrashIcon } from '@heroicons/react/24/outline';

interface Department {
    id: number;
    code: string;
    name: string;
    description: string;
    user_count: number;
    user_ids: number[];
}

interface UserOption {
    id: number;
    name: string;
}

interface Props {
    departments: Department[];
    users: UserOption[];
}

const AUTO_APPROVED_DEPARTMENT_CODE = 'BOD';

export default function AdminDepartments({ departments, users }: Props) {
    const columns = [
        { key: 'code', label: 'Kode' },
        { key: 'name', label: 'Nama Departemen' },
        { key: 'description', label: 'Deskripsi' },
        { key: 'user_count', label: 'Jumlah User' },
    ];

    const renderActions = (row: Department, helpers: { edit: () => void; remove: () => void }) => (
        <div className="flex items-center justify-center gap-2">
            <button
                type="button"
                onClick={helpers.edit}
                className="inline-flex items-center justify-center rounded-lg border border-[#BFDBFE] bg-white p-1.5 text-[#2563EB] transition hover:bg-[#EFF6FF]"
                title="Edit"
            >
                <PencilSquareIcon className="h-4 w-4" />
            </button>
            <button
                type="button"
                onClick={helpers.remove}
                className="inline-flex items-center justify-center rounded-lg border border-[#FEE2E2] bg-white p-1.5 text-[#DC2626] transition hover:bg-[#FEF2F2]"
                title="Hapus"
            >
                <TrashIcon className="h-4 w-4" />
            </button>
        </div>
    );

    const userOptions = users.map((u) => ({ value: String(u.id), label: u.name }));

    const isBod = (dept: Department | null) => dept?.code === AUTO_APPROVED_DEPARTMENT_CODE;

    const baseFormFields: any[] = [
        { name: 'code', label: 'Kode', type: 'text', required: true },
        { name: 'name', label: 'Nama Departemen', type: 'text', required: true },
        { name: 'head_name', label: 'Nama Kepala', type: 'text' },
        { name: 'notes', label: 'Catatan', type: 'textarea' },
    ];

    const bodDateTimeFields: any[] = [
        { name: 'effective_date', label: 'Tanggal Berlaku', type: 'date', required: true },
        { name: 'effective_time', label: 'Waktu Berlaku', type: 'text', required: true },
    ];

    const formFields: any[] = [
        ...baseFormFields,
        ...bodDateTimeFields,
    ];

    const editFormFields: any[] = [
        ...baseFormFields,
        ...bodDateTimeFields,
        {
            name: 'user_ids',
            label: 'User Departemen',
            type: 'multicheck',
            options: userOptions,
            hint: 'Centang user yang termasuk departemen ini. User lain yang sebelumnya anggota departemen ini akan dilepas.',
        },
    ];

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-[#0F172A]">Department Management</h2>}>
            <Head title="Department Management" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <DataTableWithActions<Department>
                        data={departments}
                        columns={columns}
                    searchPlaceholder="Cari departemen (kode/nama)... (tekan /)"
                    emptyMessage="Tidak ada departemen"
                    resourceName="Departemen"
                    formFields={formFields}
                    editFormFields={editFormFields}
                    renderActions={renderActions}
                    storeRoute={route('admin.departments.store')}
                    updateRoute={(row) => route('admin.departments.update', row.id)}
                    deleteRoute={(row) => route('admin.departments.destroy', row.id)}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
