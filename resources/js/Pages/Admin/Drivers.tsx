import { useEffect, useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import DataTableWithActions from '@/Components/DataTableWithActions';
import StatusBadge from '@/Components/UI/StatusBadge';
import { PencilIcon, TrashIcon, KeyIcon, WrenchIcon, ArrowPathIcon } from '@heroicons/react/24/outline';

interface Driver {
    id: number;
    name: string;
    license_number: string;
    phone: string;
    vehicle_plate: string;
    vehicle_type: string;
    status: string;
    willing_to_load: boolean;
    allowed_vehicle_ids: number[];
}

interface Props {
    drivers: Driver[];
    availableUsers?: { id: number; name: string; email: string }[];
}

export default function AdminDrivers({ drivers, availableUsers: initialAvailableUsers = [] }: Props) {
    // Daftar akun Driver siap didaftarkan diambil LANGSUNG dari server
    // (bukan dari props Inertia) agar tidak pernah basi akibat cache.
    const [availableUsers, setAvailableUsers] = useState(initialAvailableUsers);
    const [loadingUsers, setLoadingUsers] = useState(true);

    useEffect(() => {
        let active = true;

        // Aman dari route Ziggy yang belum ter-register: pakai path literal
        // sebagai cadangan agar halaman tidak error.
        const resolveUrl = () => {
            try {
                return route('admin.drivers.available-users') as string;
            } catch {
                return '/admin/drivers/available-users';
            }
        };

        const url = resolveUrl();

        // Batas waktu agar spinner tidak berputar selamanya bila request macet.
        const timeout = window.setTimeout(() => {
            if (active) setLoadingUsers(false);
        }, 10000);

        fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })            .then((res) => (res.ok ? res.json() : Promise.reject(new Error(String(res.status)))))
            .then((data) => {
                if (!active) return;
                setAvailableUsers(Array.isArray(data?.users) ? data.users : []);
            })
            .catch(() => {
                if (!active) return;
                // Cadangan: props Inertia tetap dipakai bila API gagal.
                setAvailableUsers(initialAvailableUsers);
            })
            .finally(() => {
                if (!active) return;
                window.clearTimeout(timeout);
                setLoadingUsers(false);
            });

        return () => {
            active = false;
            window.clearTimeout(timeout);
        };
    }, [initialAvailableUsers]);

    // Muat ulang halaman penuh — selalu membawa data terbaru dari server.
    const refreshAvailableUsers = () => window.location.reload();

    const columns = [
        { key: 'name', label: 'Nama Driver' },
        { key: 'license_number', label: 'Nomor Lisensi' },
        { key: 'vehicle_type', label: 'Tipe Kendaraan' },
        { key: 'phone', label: 'Telepon' },
        {
            key: 'willing_to_load',
            label: 'Siap Muat',
            render: (row: Driver) => (row.willing_to_load ? 'Ya' : 'Tidak'),
        },
        {
            key: 'allowed_vehicle_ids',
            label: 'Kendaraan',
            render: (row: Driver) => (
                <span className="inline-flex items-center gap-1 text-sm">
                    <WrenchIcon className="h-4 w-4 text-[#2563EB]" />
                    {row.allowed_vehicle_ids.length > 0
                        ? `${row.allowed_vehicle_ids.length} kendaraan`
                        : 'Belum diatur'}
                </span>
            ),
        },
        {
            key: 'status',
            label: 'Status',
            render: (row: Driver) => <StatusBadge status={row.status ?? 'STANDBY'} variant="driver" />,
        },
    ];

    // Form saat TAMBAH driver: data lengkap
    const formFields: any[] = [
        {
            name: 'user_id',
            label: 'Pengguna (akun ber-role Driver)',
            type: 'select',
            required: true,
            placeholder: availableUsers.length
                ? 'Pilih akun driver...'
                : 'Belum ada akun Driver yang tersedia',
            hint: availableUsers.length
                ? `${availableUsers.length} akun ber-role Driver belum terdaftar sebagai driver`
                : 'Buat akun dengan role DRIVER di menu User Management terlebih dahulu',
            options: availableUsers.map((u) => ({ value: String(u.id), label: `${u.name} — ${u.email}` })),
        },
        { name: 'license_number', label: 'Nomor Lisensi', type: 'text', required: true, placeholder: 'Contoh: SIM-A-12345678' },
        { name: 'phone', label: 'Telepon', type: 'text', placeholder: '08xxxxxxxxxx (dipakai juga untuk notifikasi WhatsApp)' },
        { name: 'vehicle_type', label: 'Tipe Kendaraan', type: 'text', placeholder: 'Contoh: Minibus / Truk / Sedan' },
        {
            name: 'willing_to_load',
            label: 'Bersedia Membantu Loading',
            type: 'select',
            required: true,
            options: [
                { value: '1', label: 'Ya' },
                { value: '0', label: 'Tidak' },
            ],
        },
    ];

    // Form saat EDIT driver: HANYA status.
    // SuperAdmin hanya menentukan status operasional:
    // Sakit, Cuti, atau Standby (bila sudah kembali bekerja).
    const editFormFields: any[] = [
        {
            name: 'status',
            label: 'Status',
            type: 'select',
            required: true,
            options: [
                { value: 'STANDBY', label: 'Standby (Bekerja)' },
                { value: 'SICK', label: 'Sakit' },
                { value: 'LEAVE', label: 'Cuti' },
            ],
        },
    ];


    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-[#0F172A]">Driver Management</h2>}>
            <Head title="Driver Management" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {/* Info akun siap dijadikan driver */}
                    <div className={`mb-4 flex flex-col gap-3 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between ${
                        loadingUsers
                            ? 'border-[#E2E8F0] bg-[#F8FAFC]'
                            : availableUsers.length > 0
                                ? 'border-[#BBF7D0] bg-[#F0FDF4]'
                                : 'border-[#FDE68A] bg-[#FFFBEB]'
                    }`}>
                        <div className="flex items-start gap-3">
                            {loadingUsers ? (
                                <svg className="mt-0.5 h-5 w-5 flex-shrink-0 animate-spin text-[#2563EB]" fill="none" viewBox="0 0 24 24">
                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z" />
                                </svg>
                            ) : (
                                <svg
                                    className={`mt-0.5 h-5 w-5 flex-shrink-0 ${availableUsers.length > 0 ? 'text-[#16A34A]' : 'text-[#B45309]'}`}
                                    fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor"
                                >
                                    {availableUsers.length > 0 ? (
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    ) : (
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.008v.008H12v-.008z" />
                                    )}
                                </svg>
                            )}
                            <div>
                                <p className={`text-sm font-semibold ${loadingUsers ? 'text-[#2563EB]' : availableUsers.length > 0 ? 'text-[#16A34A]' : 'text-[#B45309]'}`}>
                                    {loadingUsers
                                        ? 'Memuat daftar akun Driver...'
                                        : availableUsers.length > 0
                                            ? `${availableUsers.length} akun ber-role Driver siap ditambahkan`
                                            : 'Belum ada akun ber-role Driver yang tersedia'}
                                </p>
                                {!loadingUsers && (availableUsers.length > 0 ? (
                                    <p className="mt-0.5 text-xs text-[#15803D]">
                                        {availableUsers.map((u) => u.name).join(', ')} — klik "Tambah Driver" untuk mendaftarkan.
                                    </p>
                                ) : (
                                    <p className="mt-0.5 text-xs text-[#92400E]">
                                        Buat akun dengan role <span className="font-semibold">DRIVER</span> terlebih dahulu melalui menu{' '}
                                        <a href={route('admin.users')} className="font-semibold underline">User Management</a>, lalu tekan tombol
                                        <span className="font-semibold"> Segarkan</span> di samping.
                                    </p>
                                ))}
                            </div>
                        </div>

                        <button
                            type="button"
                            onClick={refreshAvailableUsers}
                            className="inline-flex shrink-0 items-center gap-2 rounded-lg border border-[#E2E8F0] bg-white px-3.5 py-2 text-xs font-semibold text-[#334155] transition hover:border-[#BFDBFE] hover:bg-[#EFF6FF] hover:text-[#2563EB]"
                        >
                            <ArrowPathIcon className="h-4 w-4" />
                            Segarkan
                        </button>
                    </div>

                    <DataTableWithActions<Driver>
                        data={drivers}
                        columns={columns}
                        searchPlaceholder="Cari driver (nama/lisensi/plat/telepon)... (tekan /)"
                        emptyMessage="Tidak ada driver"
                        resourceName="Driver"
                        formFields={formFields}
                        editFormFields={editFormFields}
                        storeRoute={route('admin.drivers.store')}
                        updateRoute={(row) => route('admin.drivers.update', row?.id)}
                        deleteRoute={(row) => route('admin.drivers.destroy', row.id)}
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
                                <Link
                                    href={route('admin.drivers.allowed-vehicles', row.id)}
                                    className="inline-flex items-center gap-1 rounded-lg border border-[#E2E8F0] bg-white px-2.5 py-1.5 text-xs font-semibold text-[#334155] transition hover:border-[#BFDBFE] hover:bg-[#EFF6FF] hover:text-[#2563EB]"
                                >
                                    <KeyIcon className="h-3.5 w-3.5" />
                                    Atur Kendaraan
                                </Link>
                                <button
                                    type="button"
                                    onClick={remove}
                                    aria-label="Hapus driver"
                                    className="inline-flex items-center justify-center rounded-lg border border-[#E2E8F0] bg-white p-1.5 text-[#64748B] transition hover:border-[#FECACA] hover:bg-[#FEF2F2] hover:text-[#DC2626]"
                                >
                                    <TrashIcon className="h-3.5 w-3.5" />
                                </button>
                            </div>
                        )}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
