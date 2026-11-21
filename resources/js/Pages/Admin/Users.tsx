import { Head } from '@inertiajs/react';
import SectionPage from '@/Components/SectionPage';
import DataTableWithActions from '@/Components/DataTableWithActions';
import StatusBadge from '@/Components/UI/StatusBadge';
import Panel from '@/Components/UI/Panel';
import StatCard from '@/Components/UI/StatCard';
import { UserGroupIcon, CheckCircleIcon, XCircleIcon, PencilIcon, TrashIcon } from '@heroicons/react/24/outline';

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    role_id: number;
    role_display_name?: string;
    department: string;
    department_id: number | null;
    phone: string;
    is_active: boolean;
}

interface Props {
    users: User[];
    roles: { id: number; name: string; display_name: string }[];
    departments?: { id: number; name: string; code: string }[];
}

export default function AdminUsers({ users, roles = [], departments = [] }: Props) {
    const activeCount = users.filter((u) => u.is_active).length;
    const inactiveCount = users.length - activeCount;

    const columns = [
        {
            key: 'name',
            label: 'Nama',
            render: (row: User) => (
                <div className="flex items-center gap-2.5">
                    <span className="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-[#EFF6FF] text-[0.65rem] font-bold text-[#2563EB]">
                        {row.name?.charAt(0)?.toUpperCase() ?? '?'}
                    </span>
                    <span className="font-medium text-[#0F172A]">{row.name}</span>
                </div>
            ),
        },
        { key: 'email', label: 'Email', render: (row: User) => <span className="text-[#64748B]">{row.email}</span> },
        { key: 'phone', label: 'Telepon', render: (row: User) => <span className="text-[#64748B]">{row.phone || '-'}</span> },
        {
            key: 'role',
            label: 'Role',
            render: (row: User) => (
                <span
                    className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${
                        row.role === 'DRIVER'
                            ? 'bg-[#DCFCE7] text-[#16A34A]'
                            : 'bg-[#DBEAFE] text-[#2563EB]'
                    }`}
                >
                    {row.role_display_name ?? row.role ?? '-'}
                </span>
            ),
        },
        { key: 'department', label: 'Departemen', render: (row: User) => row.department || '-' },
        {
            key: 'is_active',
            label: 'Status',
            render: (row: User) => <StatusBadge status={row.is_active ? 'AVAILABLE' : 'UNAVAILABLE'} />,
        },
    ];

    const formFields: any[] = [
        { name: 'name', label: 'Nama', type: 'text', required: true, placeholder: 'Nama lengkap pengguna' },
        { name: 'email', label: 'Email', type: 'email', required: true, placeholder: 'nama@perusahaan.com' },
        // Password dibuat oleh FormModal (includePassword) agar tidak duplikat
        {
            name: 'role_id',
            label: 'Role',
            type: 'select',
            required: true,
            placeholder: 'Pilih role...',
            options: roles.map((r: any) => ({
                value: String(r.id),
                label: r.name,
            })),
            hint: 'Pilih role "DRIVER" bila akun ini akan didaftarkan sebagai driver.',
        },
        {
            name: 'department_id',
            label: 'Departemen',
            type: 'select',
            options: departments.map((d: any) => ({ value: String(d.id), label: d.name })),
            hint: 'Opsional — menentukan departemen pemohon perjalanan.',
        },
        {
            name: 'phone',
            label: 'Telepon',
            type: 'text',
            placeholder: '08xxxxxxxxxx',
            hint: 'Nomor handphone (maks. 20 digit) — juga dipakai untuk notifikasi WhatsApp driver.',
        },
    ];

    return (
        <SectionPage
            title="User Management"
            eyebrow="Administrasi"
            description="Kelola akun pengguna, role, dan status aktivasi sistem."
        >
            <div className="mb-6 grid grid-cols-1 gap-5 sm:grid-cols-3">
                <StatCard tone="blue" className="mmx-d1" title="Total Pengguna" value={users.length} href={route('admin.users')} linkLabel="Kelola Pengguna" icon={<UserGroupIcon className="h-5 w-5" />} />
                <StatCard tone="green" className="mmx-d2" title="Aktif" value={activeCount} href={route('admin.users')} linkLabel="Kelola Pengguna" icon={<CheckCircleIcon className="h-5 w-5" />} />
                <StatCard tone="slate" className="mmx-d3" title="Nonaktif" value={inactiveCount} href={route('admin.users')} linkLabel="Kelola Pengguna" icon={<XCircleIcon className="h-5 w-5" />} />
            </div>

            <Panel
                delay="mmx-d3"
                title="Daftar Pengguna"
                description={`${users.length} akun terdaftar`}
                icon={<UserGroupIcon className="h-4 w-4" />}
            >
                <DataTableWithActions<User>
                    data={users}
                    columns={columns}
                    searchPlaceholder="Cari pengguna (nama/email/role/departemen)... (tekan /)"
                    emptyMessage="Tidak ada pengguna"
                    resourceName="Pengguna"
                    formFields={formFields}
                    storeRoute={route('admin.users.store')}
                    updateRoute={(row) => route('admin.users.update', row.id)}
                    deleteRoute={(row) => route('admin.users.destroy', row.id)}
                    includePassword={true}
                    renderActions={(row, { edit, remove }) => (
                        <div className="flex items-center gap-2">
                            <button
                                type="button"
                                onClick={edit}
                                className="inline-flex items-center gap-1 rounded-lg border border-[#E2E8F0] bg-white px-2.5 py-1.5 text-xs font-semibold text-[#334155] transition hover:border-[#BFDBFE] hover:bg-[#EFF6FF] hover:text-[#2563EB]"
                            >
                                <PencilIcon className="h-3.5 w-3.5" />
                                Edit
                            </button>
                            <button
                                type="button"
                                onClick={remove}
                                aria-label="Hapus pengguna"
                                className="inline-flex items-center justify-center rounded-lg border border-[#E2E8F0] bg-white p-1.5 text-[#64748B] transition hover:border-[#FECACA] hover:bg-[#FEF2F2] hover:text-[#DC2626]"
                            >
                                <TrashIcon className="h-3.5 w-3.5" />
                            </button>
                        </div>
                    )}
                />
            </Panel>
        </SectionPage>
    );
}
