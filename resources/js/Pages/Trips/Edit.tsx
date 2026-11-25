import { useState, useMemo } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { ArrowLeftIcon, PlusIcon, TrashIcon, UserGroupIcon, BriefcaseIcon, UsersIcon, BuildingOffice2Icon, CubeIcon } from '@heroicons/react/24/outline';
import { Link } from '@inertiajs/react';
import PageHeader from '@/Components/UI/PageHeader';
import Panel from '@/Components/UI/Panel';
import PrimaryButton from '@/Components/PrimaryButton';

interface Passenger {
    id?: number | null;
    name: string;
    phone: string;
    department: string;
    position: string;
}

type RequestType = 'BUSINESS_TRIP' | 'GUEST_PICKUP' | 'CARGO_PICKUP';

interface FormData {
    request_type: RequestType;
    department_id: string | null;
    driver_id: string | null;
    destination: string;
    destination_address: string;
    guest_pickup_location: string;
    guest_institution: string;
    cargo_pickup_location: string;
    cargo_description: string;
    purpose: string;
    trip_date: string;
    departure_time: string | null;
    passenger_count: number;
    priority: 'NORMAL' | 'URGENT' | 'VERY_URGENT' | '' | null;
    requested_priority: 'NORMAL' | 'URGENT' | 'VERY_URGENT' | '' | null;
    urgency_notes: string;
    requires_loading_assistance: boolean;
    loading_support_driver_ids: number[];
    notes: string;
    bod_link: string;
    bod_departure_time: string;
    passengers: Passenger[];
}

interface TripData {
    id: number;
    trip_number: string;
    request_type: string;
    department_id: number | null;
    driver_id: number | null;
    destination: string;
    destination_address: string | null;
    guest_pickup_location: string | null;
    guest_institution: string | null;
    cargo_pickup_location: string | null;
    cargo_description: string | null;
    purpose: string;
    trip_date: string | null;
    departure_time: string | null;
    passenger_count: number;
    priority: string | null;
    requested_priority: string | null;
    urgency_notes: string | null;
    requires_loading_assistance: boolean;
    loading_support_driver_ids: number[];
    bod_link: string | null;
    notes: string | null;
    passengers: { id: number; name: string; phone: string | null; department: string | null; position: string | null }[];
}

interface Props {
    trip: TripData;
    departments: { id: number; name: string; code: string }[];
    employees: { id: number; name: string; email: string }[];
    drivers: { id: number; name: string; status: string; willing_to_load: boolean }[];
    vehicles: { id: number; plate_number: string; brand: string; model: string; type: string; status: string; passenger_capacity: number }[];
}

const AUTO_APPROVED_DEPARTMENT_CODE = 'BOD';

const requestTypes = [
    {
        value: 'BUSINESS_TRIP' as RequestType,
        title: 'Perjalanan Dinas',
        description: 'Karyawan meminta kendaraan untuk keperluan tugas kantor',
        icon: <BriefcaseIcon className="h-5 w-5" />,
        accent: '#2563EB',
        badge: 'Dinas',
    },
    {
        value: 'GUEST_PICKUP' as RequestType,
        title: 'Jemput Tamu',
        description: 'Menjemput tamu/klien dari bandara, stasiun, atau lokasi lain',
        icon: <UsersIcon className="h-5 w-5" />,
        accent: '#F59E0B',
        badge: 'Tamu',
    },
    {
        value: 'CARGO_PICKUP' as RequestType,
        title: 'Muat Barang',
        description: 'Permintaan kendaraan untuk memuat / mengambil barang',
        icon: <CubeIcon className="h-5 w-5" />,
        accent: '#8B5CF6',
        badge: 'Barang',
    },
];

const inputCls = (hasError?: string) =>
    `mt-1 block w-full rounded-lg border px-3 py-2 text-sm transition focus:outline-none focus:ring-4 ${
        hasError
            ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10'
            : 'border-[#E2E8F0] focus:border-[#2563EB] focus:ring-[#2563EB]/10'
    }`;

export default function TripsEdit({ trip, departments, employees, drivers, vehicles }: Props) {
    const auth = usePage().props.auth.user as any;
    const isSuperAdmin = auth?.role?.name === 'SUPERADMIN';
    const isBodDepartment = useMemo(
        () => departments.some(
            (d) => d.code === AUTO_APPROVED_DEPARTMENT_CODE && String(d.id) === String(trip.department_id)
        ),
        [departments, trip.department_id]
    );
    const isGuestPickup = trip.request_type === 'GUEST_PICKUP';
    const isCargoPickup = trip.request_type === 'CARGO_PICKUP';

    const { data, setData, put, processing, errors, reset } = useForm<FormData>({
        request_type: (trip.request_type ?? 'BUSINESS_TRIP') as RequestType,
        department_id: trip.department_id?.toString() ?? null,
        driver_id: trip.driver_id?.toString() ?? null,
        destination: trip.destination,
        destination_address: trip.destination_address ?? '',
        guest_pickup_location: trip.guest_pickup_location ?? '',
        guest_institution: trip.guest_institution ?? '',
        cargo_pickup_location: trip.cargo_pickup_location ?? '',
        cargo_description: trip.cargo_description ?? '',
        purpose: trip.purpose,
        trip_date: trip.trip_date ?? new Date().toISOString().split('T')[0],
        departure_time: trip.departure_time ?? null,
        passenger_count: trip.passenger_count,
        priority: (trip.priority as 'NORMAL' | 'URGENT' | 'VERY_URGENT' | null) ?? null,
        requested_priority: (trip.requested_priority as 'NORMAL' | 'URGENT' | 'VERY_URGENT' | null) ?? null,
        urgency_notes: trip.urgency_notes ?? '',
        requires_loading_assistance: trip.requires_loading_assistance,
        loading_support_driver_ids: trip.loading_support_driver_ids ?? [],
        notes: trip.notes ?? '',
        bod_link: trip.bod_link ?? '',
        bod_departure_time: trip.departure_time
            ? trip.departure_time.slice(11, 16)
            : '',
        passengers: trip.passengers.map((p) => ({
            id: p.id ?? null,
            name: p.name,
            phone: p.phone ?? '',
            department: p.department ?? '',
            position: p.position ?? '',
        })),
    });

    const activeType = useMemo(
        () => requestTypes.find((t) => t.value === data.request_type) ?? requestTypes[0],
        [data.request_type]
    );

    const switchType = (type: RequestType) => {
        setData((prev) => ({
            ...prev,
            request_type: type,
            // Jangan reset data field lain di mode edit: trip yang sedang
            // diedit tetap memegang nilai database-nya, hanya jenis
            // permintaan yang berubah.
        }));
    };

    const toggleLoadingSupportDriver = (driverId: number) => {
        setData(
            'loading_support_driver_ids',
            data.loading_support_driver_ids.includes(driverId)
                ? data.loading_support_driver_ids.filter((id) => id !== driverId)
                : [...data.loading_support_driver_ids, driverId]
        );
    };

    const addPassenger = () => {
        setData('passengers', [...data.passengers, { name: '', phone: '', department: '', position: '' }]);
    };

    const removePassenger = (index: number) => {
        setData('passengers', data.passengers.filter((_, i) => i !== index));
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const namedPassengers = data.passengers.filter((p) => p.name.trim());

        // Departemen pemohon (BOD): gabungkan tanggal + jam keberangkatan
        // menjadi departure_time yang disimpan ke database.
        const departureTime = isBodDepartment
            ? (data.bod_departure_time ? `${data.trip_date} ${data.bod_departure_time}:00` : null)
            : data.departure_time;

        // Konversi string ID ke string numerik untuk validasi backend.
        setData((prev) => ({
            ...prev,
            departure_time: departureTime,
            bod_link: data.bod_link,
            department_id: data.department_id !== null && data.department_id !== undefined
                ? (String(Number(data.department_id)) || null)
                : null,
            driver_id: data.driver_id !== null && data.driver_id !== undefined
                ? (String(Number(data.driver_id)) || null)
                : null,
            // Jumlah penumpang selaras dengan daftar penumpang yang diisi.
            // Untuk trip tanpa daftar penumpang (tamu / muat barang), nilai
            // yang ada di database dipertahankan agar tidak berubah jadi 1.
            passenger_count: data.request_type === 'GUEST_PICKUP'
                ? Math.max(1, data.passenger_count)
                : Math.max(1, namedPassengers.length || data.passenger_count),
        }));

        put(route('trips.update', trip.id), {
            onSuccess: () => {
                // Inertia akan redirect ke halaman detail dengan flash
                // "Permintaan perjalanan berhasil diperbarui." dari backend.
            },
            onError: (errors) => {
                console.error('Validation errors:', errors);
                const firstError = Object.values(errors)[0];
                alert(firstError ? `Gagal menyimpan: ${firstError}` : 'Gagal menyimpan perubahan.');
            },
        });
    };

    const isCargoMode = isCargoPickup && !isBodDepartment;

    return (
        <AuthenticatedLayout header="Edit Permintaan">
            <Head title="Edit Permintaan" />

            <div className="mmj-page-in py-8 sm:py-10">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    <PageHeader
                        eyebrow="Perjalanan"
                        title="Edit Permintaan"
                        description={`Mengubah data permintaan perjalanan ${trip.trip_number}.`}
                    />

                    <div className="mb-6 flex items-center gap-2 text-sm text-[#64748B]">
                        <ArrowLeftIcon className="h-4 w-4" />
                        <Link href={route('trips.show', trip.id)} className="text-[#2563EB] hover:text-[#1D4ED8] hover:underline">
                            Kembali ke detail {trip.trip_number}
                        </Link>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-5">
                        {/* ===== Step 1: Request type selector ===== */}
                        <Panel
                            title="Jenis Permintaan"
                            description="Pilih jenis permintaan"
                            icon={
                                isGuestPickup
                                    ? <UsersIcon className="h-4 w-4" />
                                    : <BriefcaseIcon className="h-4 w-4" />
                            }
                            actions={
                                <span
                                    className="rounded-full px-2.5 py-1 text-[0.7rem] font-semibold"
                                    style={{
                                        color: activeType.accent,
                                        backgroundColor: isGuestPickup ? '#FFF7ED' : '#EFF6FF',
                                    }}
                                >
                                    {activeType.badge}
                                </span>
                            }
                        >
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                {requestTypes.map((type) => {
                                    const active = data.request_type === type.value;
                                    return (
                                        <button
                                            key={type.value}
                                            type="button"
                                            onClick={() => switchType(type.value)}
                                            aria-pressed={active}
                                            className={`group relative overflow-hidden rounded-xl border-2 p-4 text-left transition-all duration-300 ${
                                                active
                                                    ? 'border-transparent shadow-lg'
                                                    : 'border-[#E2E8F0] bg-white hover:border-[#CBD5E1] hover:shadow-md'
                                            }`}
                                            style={
                                                active
                                                    ? {
                                                          borderColor: type.accent,
                                                          boxShadow: `0 12px 28px -14px ${type.accent}66`,
                                                      }
                                                    : undefined
                                            }
                                        >
                                            {active && (
                                                <span
                                                    className="absolute -right-8 -top-8 h-24 w-24 rounded-full opacity-15"
                                                    style={{ backgroundColor: type.accent }}
                                                />
                                            )}
                                            <div className="relative flex items-start gap-3">
                                                <span
                                                    className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg transition-transform duration-300 group-hover:scale-105"
                                                    style={{
                                                        color: type.accent,
                                                        backgroundColor: active ? `${type.accent}1A` : '#F1F5F9',
                                                    }}
                                                >
                                                    {type.icon}
                                                </span>
                                                <div className="min-w-0">
                                                    <p className="flex items-center gap-1.5 text-sm font-semibold text-[#0F172A]">
                                                        {type.title}
                                                        {active && (
                                                            <svg className="h-4 w-4" style={{ color: type.accent }} fill="currentColor" viewBox="0 0 20 20">
                                                                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clipRule="evenodd" />
                                                            </svg>
                                                        )}
                                                    </p>
                                                    <p className="mt-1 text-xs leading-relaxed text-[#64748B]">{type.description}</p>
                                                </div>
                                            </div>
                                        </button>
                                    );
                                })}
                            </div>
                        </Panel>

                        {errors.request_type && (
                            <p className="mt-1 text-xs text-red-600">{errors.request_type}</p>
                        )}

                        {isSuperAdmin && (
                            <Panel
                                title="Departemen Pemohon"
                                description="Pilih departemen yang terkait dengan permintaan ini."
                                icon={<BuildingOffice2Icon className="h-4 w-4" />}
                            >
                                <div>
                                    <label className="mmx-label">
                                        Departemen <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        value={data.department_id ?? ''}
                                        onChange={(e) => setData('department_id', e.target.value)}
                                        className={inputCls(errors.department_id)}
                                        required
                                    >
                                        <option value="">Pilih departemen...</option>
                                        {departments.map((department) => (
                                            <option key={department.id} value={department.id}>
                                                {department.name}
                                                {department.code === AUTO_APPROVED_DEPARTMENT_CODE ? ' — otomatis disetujui' : ''}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.department_id && (
                                        <p className="mt-1 text-xs text-red-600">{errors.department_id}</p>
                                    )}

                                    {isBodDepartment && (
                                        <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <div>
                                                <label className="mmx-label">
                                                    Tanggal Keberangkatan <span className="text-red-500">*</span>
                                                </label>
                                                <input
                                                    type="date"
                                                    value={data.trip_date ?? ''}
                                                    onChange={(e) => setData('trip_date', e.target.value)}
                                                    className={inputCls(errors.trip_date)}
                                                    required
                                                />
                                                {errors.trip_date && (
                                                    <p className="mt-1 text-xs text-red-600">{errors.trip_date}</p>
                                                )}
                                            </div>
                                            <div>
                                                <label className="mmx-label">
                                                    Jam Keberangkatan <span className="text-red-500">*</span>
                                                </label>
                                                <input
                                                    type="time"
                                                    value={data.bod_departure_time}
                                                    onChange={(e) => setData('bod_departure_time', e.target.value)}
                                                    className={inputCls(errors.departure_time)}
                                                    required
                                                />
                                                {errors.departure_time && (
                                                    <p className="mt-1 text-xs text-red-600">{errors.departure_time}</p>
                                                )}
                                            </div>
                                        </div>
                                    )}

                                    {isBodDepartment && (
                                        <div className="mt-4">
                                            <label className="mmx-label">
                                                Driver <span className="text-red-500">*</span>
                                            </label>
                                            <select
                                                value={data.driver_id ?? ''}
                                                onChange={(e) => setData('driver_id', e.target.value)}
                                                className={inputCls(errors.driver_id)}
                                                required
                                            >
                                                <option value="">Pilih driver...</option>
                                                {drivers.map((driver) => (
                                                    <option key={driver.id} value={driver.id}>
                                                        {driver.name}
                                                        {driver.status === 'SICK' || driver.status === 'LEAVE' || driver.status === 'OFF_DUTY' || driver.status === 'UNAVAILABLE' ? ' (tidak tersedia)' : ''}
                                                    </option>
                                                ))}
                                            </select>
                                            {errors.driver_id && (
                                                <p className="mt-1 text-xs text-red-600">{errors.driver_id}</p>
                                            )}
                                        </div>
                                    )}
                                </div>
                            </Panel>
                        )}

                        {/* ===== Step 2: Guest pickup specific ===== */}
                        {isGuestPickup && !isBodDepartment && (
                            <Panel
                                title="Data Tamu"
                                description="Informasi tamu yang akan dijemput"
                                icon={<UsersIcon className="h-4 w-4" />}
                            >
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div className="sm:col-span-2">
                                        <label className="mmx-label">Lokasi Penjemputan <span className="text-red-500">*</span></label>
                                        <input
                                            type="text"
                                            value={data.guest_pickup_location}
                                            onChange={(e) => setData('guest_pickup_location', e.target.value)}
                                            className={inputCls(errors.guest_pickup_location)}
                                            placeholder="Contoh: Bandara Adisucipto, Gate 2 / Stasiun Yogyakarta"
                                        />
                                        {errors.guest_pickup_location && <p className="mt-1 text-xs text-red-600">{errors.guest_pickup_location}</p>}
                                    </div>
                                    <div>
                                        <label className="mmx-label">Instansi / Instalasi Tamu <span className="text-red-500">*</span></label>
                                        <input
                                            type="text"
                                            value={data.guest_institution}
                                            onChange={(e) => setData('guest_institution', e.target.value)}
                                            className={inputCls(errors.guest_institution)}
                                            placeholder="Contoh: PT Maju Jaya / RS Harapan"
                                        />
                                        {errors.guest_institution && <p className="mt-1 text-xs text-red-600">{errors.guest_institution}</p>}
                                    </div>
                                    <div>
                                        <label className="mmx-label">Jumlah Tamu <span className="text-red-500">*</span></label>
                                        <input
                                            type="number"
                                            min="1"
                                            max="50"
                                            value={data.passenger_count}
                                            onChange={(e) => setData('passenger_count', parseInt(e.target.value) || 1)}
                                            className={inputCls(errors.passenger_count)}
                                        />
                                        {errors.passenger_count && <p className="mt-1 text-xs text-red-600">{errors.passenger_count}</p>}
                                    </div>
                                    <div className="sm:col-span-2">
                                        <label className="mmx-label">Nama Tamu Utama <span className="text-red-500">*</span></label>
                                        <input
                                            type="text"
                                            value={data.passengers[0]?.name ?? ''}
                                            onChange={(e) => {
                                                const newPassengers = [...data.passengers];
                                                newPassengers[0] = { ...newPassengers[0], name: e.target.value };
                                                setData('passengers', newPassengers);
                                            }}
                                            className={inputCls(errors['passengers.0.name'])}
                                            placeholder="Nama lengkap tamu"
                                        />
                                        {errors['passengers.0.name'] && <p className="mt-1 text-xs text-red-600">{errors['passengers.0.name']}</p>}
                                    </div>
                                    <div>
                                        <label className="mmx-label">Telepon Tamu</label>
                                        <input
                                            type="text"
                                            value={data.passengers[0]?.phone ?? ''}
                                            onChange={(e) => {
                                                const newPassengers = [...data.passengers];
                                                newPassengers[0] = { ...newPassengers[0], phone: e.target.value };
                                                setData('passengers', newPassengers);
                                            }}
                                            className={inputCls()}
                                            placeholder="08xxxxxxxxxx"
                                        />
                                    </div>
                                    <div>
                                        <label className="mmx-label">Tujuan Antar <span className="text-red-500">*</span></label>
                                        <input
                                            type="text"
                                            value={data.destination}
                                            onChange={(e) => setData('destination', e.target.value)}
                                            className={inputCls(errors.destination)}
                                            placeholder="Contoh: Kantor Pusat MMJ / Hotel"
                                        />
                                        {errors.destination && <p className="mt-1 text-xs text-red-600">{errors.destination}</p>}
                                    </div>
                                </div>
                            </Panel>
                        )}

                        {/* ===== Step 2b: Cargo pickup specific ===== */}
                        {isCargoPickup && !isBodDepartment && (
                            <Panel
                                title="Data Barang"
                                description="Informasi barang yang akan dimuat"
                                icon={<CubeIcon className="h-4 w-4" />}
                            >
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div className="sm:col-span-2">
                                        <label className="mmx-label">Lokasi Muat Barang <span className="text-red-500">*</span></label>
                                        <input
                                            type="text"
                                            value={data.cargo_pickup_location}
                                            onChange={(e) => setData('cargo_pickup_location', e.target.value)}
                                            className={inputCls(errors.cargo_pickup_location)}
                                            placeholder="Contoh: Gudang PT Maju Jaya, Jl. Industri No. 10"
                                        />
                                        {errors.cargo_pickup_location && <p className="mt-1 text-xs text-red-600">{errors.cargo_pickup_location}</p>}
                                    </div>
                                    <div className="sm:col-span-2">
                                        <label className="mmx-label">Deskripsi Barang <span className="text-red-500">*</span></label>
                                        <textarea
                                            rows={2}
                                            value={data.cargo_description}
                                            onChange={(e) => setData('cargo_description', e.target.value)}
                                            className={inputCls(errors.cargo_description)}
                                            placeholder="Contoh: 5 dus dokumen, 1 mesin printer (perkiraan 2 karung)"
                                        />
                                        {errors.cargo_description && <p className="mt-1 text-xs text-red-600">{errors.cargo_description}</p>}
                                    </div>
                                    <div>
                                        <label className="mmx-label">Tujuan Antar <span className="text-red-500">*</span></label>
                                        <input
                                            type="text"
                                            value={data.destination}
                                            onChange={(e) => setData('destination', e.target.value)}
                                            className={inputCls(errors.destination)}
                                            placeholder="Contoh: Kantor Pusat MMJ / Gudang Tujuan"
                                        />
                                        {errors.destination && <p className="mt-1 text-xs text-red-600">{errors.destination}</p>}
                                    </div>
                                </div>
                            </Panel>
                        )}

                        {/* ===== Catatan Muat Barang ===== */}
                        {isCargoMode && (
                            <Panel
                                title="Catatan (opsional)"
                                description="Instruksi pemuatan, kondisi barang, lokasi penyimpanan, atau keterangan tambahan"
                                icon={<CubeIcon className="h-4 w-4" />}
                            >
                                <textarea
                                    rows={3}
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    className={inputCls(errors.notes)}
                                    placeholder="Contoh: muat pagi, barang pecah belah, simpan di gudang B"
                                />
                                {errors.notes && <p className="mt-1 text-xs text-red-600">{errors.notes}</p>}
                            </Panel>
                        )}

                        {/* ===== Supir yang Ikut Muat (muat barang) ===== */}
                        {(isCargoPickup || data.requires_loading_assistance) && !isBodDepartment && (
                            <Panel
                                title="Supir yang Ikut Muat (opsional)"
                                description="Pilih satu atau beberapa supir siapa saja yang ikut membantu muat barang"
                                icon={<UsersIcon className="h-4 w-4" />}
                                actions={
                                    <span className="rounded-full bg-[#F0FDF4] px-2.5 py-1 text-[0.7rem] font-semibold text-[#16A34A]">
                                        {data.loading_support_driver_ids.length} dipilih
                                    </span>
                                }
                            >
                                <div className="space-y-3">
                                    <p className="text-xs text-[#64748B]">
                                        Bebas pilih supir siapa saja — status apapun boleh ikut muat.
                                        Persetujuan SuperAdmin diperlukan setelah permintaan disimpan.
                                    </p>
                                    <div className="max-h-56 space-y-1 overflow-y-auto rounded-xl border border-[#E2E8F0] p-2">
                                        {drivers.length === 0 ? (
                                            <p className="px-2 py-3 text-sm text-[#94A3B8]">
                                                Tidak ada driver tersedia saat ini.
                                            </p>
                                        ) : (
                                            drivers.map((driver) => {
                                                const checked = data.loading_support_driver_ids.includes(driver.id);
                                                return (
                                                    <label
                                                        key={driver.id}
                                                        className="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-[#F8FAFC]"
                                                    >
                                                        <input
                                                            type="checkbox"
                                                            checked={checked}
                                                            onChange={() => toggleLoadingSupportDriver(driver.id)}
                                                            className="h-4 w-4 rounded border-[#CBD5E1] text-[#2563EB] focus:ring-[#2563EB]"
                                                        />
                                                        <span className="flex-1">{driver.name}</span>
                                                        <span className="rounded-full bg-[#F1F5F9] px-2 py-0.5 text-[0.65rem] font-semibold text-[#64748B]">
                                                            {driver.status}
                                                        </span>
                                                    </label>
                                                );
                                            })
                                        )}
                                    </div>
                                    {errors.loading_support_driver_ids && (
                                        <p className="text-xs text-red-600">{errors.loading_support_driver_ids}</p>
                                    )}
                                </div>
                            </Panel>
                        )}

                        {/* ===== Step 3: Trip information ===== */}
                        {!isBodDepartment && !isCargoMode && <Panel
                            title={isGuestPickup ? 'Waktu & Tujuan' : 'Informasi Perjalanan'}
                            description={isGuestPickup ? 'Jadwal penjemputan dan antar' : 'Detail perjalanan dinas yang diajukan'}
                            icon={<BuildingOffice2Icon className="h-4 w-4" />}
                        >
                            <div className="space-y-4">
                                {(!isGuestPickup && !isCargoPickup) && (
                                    <>
                                        <div>
                                            <label className="mmx-label">Tujuan <span className="text-red-500">*</span></label>
                                            <input
                                                type="text"
                                                value={data.destination}
                                                onChange={(e) => setData('destination', e.target.value)}
                                                className={inputCls(errors.destination)}
                                                placeholder="Contoh: Jakarta, Bandung"
                                            />
                                            {errors.destination && <p className="mt-1 text-xs text-red-600">{errors.destination}</p>}
                                        </div>

                                        <div>
                                            <label className="mmx-label">Alamat Tujuan</label>
                                            <textarea
                                                rows={2}
                                                value={data.destination_address}
                                                onChange={(e) => setData('destination_address', e.target.value)}
                                                className={inputCls()}
                                                placeholder="Alamat lengkap tujuan"
                                            />
                                        </div>
                                    </>
                                )}

                                <div>
                                    <label className="mmx-label">{isGuestPickup ? 'Keperluan Penjemputan' : 'Keperluan'} <span className="text-red-500">*</span></label>
                                    <textarea
                                        rows={3}
                                        value={data.purpose}
                                        onChange={(e) => setData('purpose', e.target.value)}
                                        className={inputCls(errors.purpose)}
                                        placeholder={isGuestPickup ? 'Contoh: Menjemput klien dari bandara untuk meeting' : 'Contoh: Kunjungan klien / Rapat internal / Pengambilan barang'}
                                    />
                                    {errors.purpose && <p className="mt-1 text-xs text-red-600">{errors.purpose}</p>}
                                </div>

                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <label className="mmx-label">Tanggal</label>
                                        <input
                                            type="date"
                                            value={data.trip_date ?? ''}
                                            onChange={(e) => setData('trip_date', e.target.value)}
                                            className={inputCls(errors.trip_date)}
                                        />
                                        {errors.trip_date && <p className="mt-1 text-xs text-red-600">{errors.trip_date}</p>}
                                    </div>
                                    <div>
                                        <label className="mmx-label">Jam Berangkat <span className="text-red-500">*</span></label>
                                        <input
                                            type="datetime-local"
                                            value={data.departure_time ?? ''}
                                            onChange={(e) => setData('departure_time', e.target.value)}
                                            className={inputCls(errors.departure_time)}
                                        />
                                        {errors.departure_time && <p className="mt-1 text-xs text-red-600">{errors.departure_time}</p>}
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    {(!isGuestPickup && !isCargoPickup) && (
                                        <div>
                                            <label className="mmx-label">Jumlah Penumpang <span className="text-red-500">*</span></label>
                                            <input
                                                type="number"
                                                min="1"
                                                max="50"
                                                value={data.passenger_count}
                                                onChange={(e) => setData('passenger_count', parseInt(e.target.value) || 1)}
                                                className={inputCls(errors.passenger_count)}
                                            />
                                            {errors.passenger_count && <p className="mt-1 text-xs text-red-600">{errors.passenger_count}</p>}
                                        </div>
                                    )}

                                    {isSuperAdmin ? (
                                        <div className={isGuestPickup ? 'sm:col-span-2' : ''}>
                                            <label className="mmx-label">Prioritas <span className="text-red-500">*</span></label>
                                            <select
                                                value={data.priority ?? ''}
                                                onChange={(e) => setData('priority', e.target.value as 'NORMAL' | 'URGENT' | 'VERY_URGENT')}
                                                className={inputCls(errors.priority)}
                                            >
                                                <option value="NORMAL">Normal</option>
                                                <option value="URGENT">Urgent</option>
                                                <option value="VERY_URGENT">Very Urgent</option>
                                            </select>
                                            {errors.priority && <p className="mt-1 text-xs text-red-600">{errors.priority}</p>}
                                        </div>
                                    ) : (
                                        <div className={isGuestPickup ? 'sm:col-span-2' : 'sm:col-span-2'}>
                                            <label className="mmx-label">Prioritas Usulan</label>
                                            <select
                                                value={data.requested_priority ?? ''}
                                                onChange={(e) => setData('requested_priority', e.target.value as 'NORMAL' | 'URGENT' | 'VERY_URGENT')}
                                                className={inputCls()}
                                            >
                                                <option value="NORMAL">Normal</option>
                                                <option value="URGENT">Urgent</option>
                                                <option value="VERY_URGENT">Very Urgent</option>
                                            </select>
                                            <p className="mt-1 text-xs text-[#94A3B8]">
                                                Prioritas final ditetapkan oleh SuperAdmin saat persetujuan.
                                            </p>
                                        </div>
                                    )}

                                    {(!isGuestPickup && !isCargoPickup) && (
                                        <div className="flex items-end pb-1">
                                            <label className="flex cursor-pointer items-center gap-2 text-sm text-[#334155]">
                                                <input
                                                    type="checkbox"
                                                    checked={data.requires_loading_assistance}
                                                    onChange={(e) => setData('requires_loading_assistance', e.target.checked)}
                                                    className="h-4 w-4 rounded border-[#CBD5E1] text-[#2563EB] focus:ring-[#2563EB]"
                                                />
                                                Butuh bantuan loading
                                            </label>
                                        </div>
                                    )}
                                </div>

                                {(!isGuestPickup && !isCargoPickup) && (
                                    <div>
                                        <label className="mmx-label">Alasan Urgensi</label>
                                        <textarea
                                            rows={2}
                                            value={data.urgency_notes}
                                            onChange={(e) => setData('urgency_notes', e.target.value)}
                                            className={inputCls()}
                                            placeholder="Alasan mengapa prioritas Urgent / Very Urgent"
                                        />
                                    </div>
                                )}

                                <div>
                                    <label className="mmx-label">Catatan</label>
                                    <textarea
                                        rows={2}
                                        value={data.notes}
                                        onChange={(e) => setData('notes', e.target.value)}
                                        className={inputCls()}
                                        placeholder="Catatan tambahan (opsional)"
                                    />
                                </div>
                            </div>
                        </Panel>}

                        {/* ===== Step 4: Passengers (business trip only) ===== */}
                        {!isGuestPickup && !isCargoPickup && !isBodDepartment && (
                            <Panel
                                title="Daftar Penumpang"
                                description={`${data.passengers.filter((p) => p.name.trim()).length} penumpang terisi`}
                                icon={<UserGroupIcon className="h-4 w-4" />}
                                actions={
                                    <button
                                        type="button"
                                        onClick={addPassenger}
                                        className="mmj-btn mmj-btn-primary px-3 py-1.5 text-xs"
                                    >
                                        <PlusIcon className="h-4 w-4" />
                                        Tambah
                                    </button>
                                }
                            >
                                <div className="space-y-4">
                                    {data.passengers.map((passenger, index) => (
                                        <div key={passenger.id ?? index} className="rounded-xl border border-[#E2E8F0] bg-[#F8FAFC]/60 p-4">
                                            <div className="mb-3 flex items-center justify-between">
                                                <h4 className="text-sm font-semibold text-[#0F172A]">
                                                    Penumpang {index + 1}
                                                    {index === 0 && (
                                                        <span className="ms-2 rounded-full bg-[#EFF6FF] px-2 py-0.5 text-[0.65rem] font-semibold text-[#2563EB]">Pemohon</span>
                                                    )}
                                                </h4>
                                                {data.passengers.length > 1 && (
                                                    <button
                                                        type="button"
                                                        onClick={() => removePassenger(index)}
                                                        className="rounded-lg p-1.5 text-[#DC2626] transition hover:bg-[#FEF2F2]"
                                                        aria-label="Hapus penumpang"
                                                    >
                                                        <TrashIcon className="h-4 w-4" />
                                                    </button>
                                                )}
                                            </div>

                                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                <div>
                                                    <label className="mmx-label">Nama <span className="text-red-500">*</span></label>
                                                    <input
                                                        type="text"
                                                        value={passenger.name}
                                                        onChange={(e) => {
                                                            const newPassengers = [...data.passengers];
                                                            newPassengers[index] = { ...newPassengers[index], name: e.target.value };
                                                            setData('passengers', newPassengers);
                                                        }}
                                                        className={inputCls()}
                                                        placeholder="Nama lengkap"
                                                    />
                                                </div>
                                                <div>
                                                    <label className="mmx-label">Telepon</label>
                                                    <input
                                                        type="text"
                                                        value={passenger.phone}
                                                        onChange={(e) => {
                                                            const newPassengers = [...data.passengers];
                                                            newPassengers[index] = { ...newPassengers[index], phone: e.target.value };
                                                            setData('passengers', newPassengers);
                                                        }}
                                                        className={inputCls()}
                                                        placeholder="08xxxxxxxxxx"
                                                    />
                                                </div>
                                                <div>
                                                    <label className="mmx-label">Departemen</label>
                                                    <input
                                                        type="text"
                                                        value={passenger.department}
                                                        onChange={(e) => {
                                                            const newPassengers = [...data.passengers];
                                                            newPassengers[index] = { ...newPassengers[index], department: e.target.value };
                                                            setData('passengers', newPassengers);
                                                        }}
                                                        className={inputCls()}
                                                        placeholder="Departemen"
                                                    />
                                                </div>
                                                <div className="sm:col-span-2">
                                                    <label className="mmx-label">Jabatan</label>
                                                    <input
                                                        type="text"
                                                        value={passenger.position}
                                                        onChange={(e) => {
                                                            const newPassengers = [...data.passengers];
                                                            newPassengers[index] = { ...newPassengers[index], position: e.target.value };
                                                            setData('passengers', newPassengers);
                                                        }}
                                                        className={inputCls()}
                                                        placeholder="Jabatan"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </Panel>
                        )}

                        <div className="flex flex-col sm:flex-row sm:justify-end gap-3 pt-6 border-t">
                            <Link
                                href={route('trips.show', trip.id)}
                                className="mmj-btn mmj-btn-secondary"
                            >
                                Batal
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className="mmj-btn mmj-btn-primary"
                            >
                                {processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
