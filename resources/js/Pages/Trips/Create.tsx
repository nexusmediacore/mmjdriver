import { useState, useMemo } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { ArrowLeftIcon, PlusIcon, TrashIcon, UserGroupIcon, BriefcaseIcon, UsersIcon, BuildingOffice2Icon, ShieldCheckIcon, ExclamationTriangleIcon, CheckCircleIcon, CubeIcon } from '@heroicons/react/24/outline';
import { Link } from '@inertiajs/react';
import PageHeader from '@/Components/UI/PageHeader';
import Panel from '@/Components/UI/Panel';
import PrimaryButton from '@/Components/PrimaryButton';

interface Passenger {
    name: string;
    phone: string;
    department: string;
    position: string;
}

type RequestType = 'BUSINESS_TRIP' | 'GUEST_PICKUP' | 'CARGO_PICKUP';

interface FormData {
    request_type: RequestType;
    department_id: string;
    driver_id: string;
    destination: string;
    destination_address: string;
    guest_pickup_location: string;
    guest_institution: string;
    cargo_pickup_location: string;
    cargo_description: string;
    purpose: string;
    trip_date: string;
    departure_time: string;
    passenger_count: number;
    priority: 'NORMAL' | 'URGENT' | 'VERY_URGENT';
    requested_priority: 'NORMAL' | 'URGENT' | 'VERY_URGENT';
    urgency_notes: string;
    requires_loading_assistance: boolean;
    loading_support_driver_ids: number[];
    bod_link: string;
    notes: string;
    passengers: Passenger[];
}

interface Props {
    departments: { id: number; name: string; code: string }[];
    employees: { id: number; name: string; email: string }[];
    drivers: { id: number; name: string; status: string; willing_to_load: boolean }[];
}

/**
 * Departemen yang trip-nya otomatis disetujui tanpa alur approval.
 * Harus sama dengan Department::CODE_BOD di backend.
 */
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

export default function TripsCreate({ departments, employees, drivers }: Props) {
    const auth = usePage().props.auth.user as any;
    const { data, setData, post, processing, errors, reset } = useForm<FormData>({
        request_type: 'BUSINESS_TRIP',
        department_id: '',
        driver_id: '',
        destination: '',
        destination_address: '',
        guest_pickup_location: '',
        guest_institution: '',
        cargo_pickup_location: '',
        cargo_description: '',
        purpose: '',
        trip_date: new Date().toISOString().split('T')[0],
        departure_time: '',
        passenger_count: 1,
        priority: 'NORMAL',
        requested_priority: 'NORMAL',
        urgency_notes: '',
        requires_loading_assistance: false,
        loading_support_driver_ids: [],
        bod_link: '',
        notes: '',
        passengers: [
            { name: auth?.name || '', phone: auth?.phone || '', department: auth?.department?.name || '', position: '' },
        ],
    });

    const isGuestPickup = data.request_type === 'GUEST_PICKUP';
    const isCargoPickup = data.request_type === 'CARGO_PICKUP';
    const isSuperAdmin = auth?.role?.name === 'SUPERADMIN';
    const isBodDepartment = useMemo(
        () => departments.some(
            (d) => d.code === AUTO_APPROVED_DEPARTMENT_CODE && String(d.id) === String(data.department_id)
        ),
        [departments, data.department_id]
    );
    const activeType = useMemo(
        () => requestTypes.find((t) => t.value === data.request_type) ?? requestTypes[0],
        [data.request_type]
    );

    const switchType = (type: RequestType) => {
        setData((prev) => ({
            ...prev,
            request_type: type,
            // Reset fields that belong to the other type
            guest_pickup_location: type === 'GUEST_PICKUP' ? prev.guest_pickup_location : '',
            guest_institution: type === 'GUEST_PICKUP' ? prev.guest_institution : '',
            cargo_pickup_location: type === 'CARGO_PICKUP' ? prev.cargo_pickup_location : '',
            cargo_description: type === 'CARGO_PICKUP' ? prev.cargo_description : '',
            passengers: type === 'GUEST_PICKUP' ? [{ ...prev.passengers[0] }] : prev.passengers,
            // Muat barang selalu butuh bantuan loading (driver ikut memuat).
            requires_loading_assistance: type === 'CARGO_PICKUP' ? true : (type === 'BUSINESS_TRIP' ? false : prev.requires_loading_assistance),
            loading_support_driver_ids: type === 'CARGO_PICKUP' ? prev.loading_support_driver_ids : [],
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
        // Keep passenger_count in sync with the actual passengers entered
        setData('passenger_count', Math.max(1, data.passengers.filter((p) => p.name.trim()).length));

        // Untuk Admin Departemen, prioritas final tidak dikirim (tetap Normal);
        // yang dikirim adalah usulan + alasan urgensinya.
        post(route('trips.store'), {
            onSuccess: () => reset(),
            onError: () => {},
        });
    };

    return (
        <AuthenticatedLayout header="Buat Permintaan">
            <Head title="Buat Permintaan" />

            <div className="mmj-page-in py-8 sm:py-10">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    <PageHeader
                        eyebrow="Perjalanan"
                        title="Buat Permintaan Baru"
                        description="Pilih jenis permintaan terlebih dahulu, lalu lengkapi data yang diperlukan."
                    />

                    <form onSubmit={handleSubmit} className="space-y-5">
                        {/* ===== Step 1: Request type selector ===== */}
                        <Panel
                            delay="mmx-d1"
                            title="Jenis Permintaan"
                            description="Pilih salah satu jenis permintaan"
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

                        {isSuperAdmin && (
                            <Panel
                                delay="mmx-d1"
                                title="Departemen Pemohon"
                                description="Pilih departemen yang terkait dengan permintaan ini."
                                icon={<BuildingOffice2Icon className="h-4 w-4" />}
                            >
                                <div>
                                    <label className="mmx-label">
                                        Departemen <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        value={data.department_id}
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
                                        <div className="mt-4">
                                            <label className="mmx-label">
                                                Driver <span className="text-red-500">*</span>
                                            </label>
                                            <select
                                                value={data.driver_id}
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
                                            <p className="mt-1.5 text-xs text-[#94A3B8]">
                                                Trip langsung dikirim ke WhatsApp driver yang dipilih —
                                                driver tidak perlu melakukan konfirmasi assignment lagi.
                                            </p>
                                        </div>
                                    )}

                                    {isBodDepartment && (
                                        <div className="mt-4">
                                            <label className="mmx-label">Tautan Referensi (opsional)</label>
                                            <input
                                                type="url"
                                                inputMode="url"
                                                value={data.bod_link}
                                                onChange={(e) => setData('bod_link', e.target.value)}
                                                className={inputCls(errors.bod_link)}
                                                placeholder="https://maps.app.goo.gl/... atau tautan agenda/dokumen"
                                                pattern="https?://.+"
                                            />
                                            {errors.bod_link && (
                                                <p className="mt-1 text-xs text-red-600">{errors.bod_link}</p>
                                            )}
                                            <p className="mt-1.5 text-xs text-[#94A3B8]">
                                                Contoh: titik jemput Google Maps, agenda, atau dokumen eksternal. Hanya tautan
                                                http:// atau https:// yang diterima. Tautan ikut terkirim pada pesan WhatsApp driver.
                                            </p>
                                        </div>
                                    )}

                                    {isBodDepartment && (
                                        <div className="mt-3 flex items-start gap-2.5 rounded-lg border border-[#BBF7D0] bg-[#F0FDF4] px-3.5 py-3">
                                            <CheckCircleIcon className="mt-0.5 h-5 w-5 flex-shrink-0 text-[#16A34A]" />
                                            <div>
                                                <p className="text-sm font-semibold text-[#166534]">
                                                    Trip Board of Directors otomatis disetujui &amp; dikirim ke driver
                                                </p>
                                                <p className="mt-0.5 text-xs text-[#15803D]">
                                                    Informasi perjalanan tidak perlu diisi. Begitu disimpan, trip langsung
                                                    berstatus Disetujui dan trip request otomatis dikirim ke WhatsApp driver
                                                    yang dipilih — tanpa perlu penugasan manual.
                                                </p>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </Panel>
                        )}

                        {/* ===== Step 2: Guest pickup specific ===== */}
                        {isGuestPickup && !isBodDepartment && (
                            <Panel
                                delay="mmx-d2"
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
                                delay="mmx-d2"
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

                        {/* ===== Driver Pendukung Loading (muat barang / bantuan loading) ===== */}
                        {(isCargoPickup || data.requires_loading_assistance) && !isBodDepartment && (
                            <Panel
                                delay="mmx-d2"
                                title="Driver Pendukung Loading (opsional)"
                                description="Pilih satu atau beberapa driver yang ikut membantu muat barang"
                                icon={<UsersIcon className="h-4 w-4" />}
                                actions={
                                    <span className="rounded-full bg-[#F0FDF4] px-2.5 py-1 text-[0.7rem] font-semibold text-[#16A34A]">
                                        {data.loading_support_driver_ids.length} dipilih
                                    </span>
                                }
                            >
                                <div className="space-y-3">
                                    <p className="text-xs text-[#64748B]">
                                        Hanya driver berstatus <span className="font-semibold">Standby</span> yang bersedia membantu
                                        loading yang dapat dipilih. Driver utama tetap ditugaskan oleh dispatcher.
                                    </p>
                                    <div className="max-h-56 space-y-1 overflow-y-auto rounded-xl border border-[#E2E8F0] p-2">
                                        {drivers.filter((d) => d.status === 'STANDBY' && d.willing_to_load).length === 0 ? (
                                            <p className="px-2 py-3 text-sm text-[#94A3B8]">
                                                Tidak ada driver Standby yang bersedia membantu loading saat ini.
                                            </p>
                                        ) : (
                                            drivers
                                                .filter((d) => d.status === 'STANDBY' && d.willing_to_load)
                                                .map((driver) => {
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
                                                            <span className="rounded-full bg-[#F0FDF4] px-2 py-0.5 text-[0.65rem] font-semibold text-[#16A34A]">
                                                                Standby
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

                        {/* ===== Step 3: Trip information (dilewati untuk BOD) ===== */}
                        {!isBodDepartment && <Panel
                            delay="mmx-d2"
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
                                            value={data.trip_date}
                                            onChange={(e) => setData('trip_date', e.target.value)}
                                            className={inputCls()}
                                        />
                                    </div>
                                    <div>
                                        <label className="mmx-label">Jam Berangkat <span className="text-red-500">*</span></label>
                                        <input
                                            type="datetime-local"
                                            value={data.departure_time}
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

                                    {/* Prioritas: hanya SuperAdmin yang menetapkan.
                                        Admin departemen hanya mengusulkan + memberi alasan. */}
                                    {isSuperAdmin ? (
                                        <div className={isGuestPickup ? 'sm:col-span-2' : ''}>
                                            <label className="mmx-label">Prioritas <span className="text-red-500">*</span></label>
                                            <select
                                                value={data.priority}
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
                                            <label className="mmx-label">Prioritas</label>
                                            <div className="relative">
                                                <select
                                                    value="NORMAL"
                                                    disabled
                                                    className="mt-1 block w-full cursor-not-allowed rounded-lg border border-[#E2E8F0] bg-[#F1F5F9] px-3 py-2 text-sm text-[#64748B]"
                                                >
                                                    <option value="NORMAL">Normal (ditentukan SuperAdmin)</option>
                                                </select>
                                                <span className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-[#DBEAFE] px-2 py-0.5 text-[0.65rem] font-semibold text-[#2563EB]">
                                                    SuperAdmin
                                                </span>
                                            </div>
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
                                delay="mmx-d3"
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
                                        <div key={index} className="rounded-xl border border-[#E2E8F0] bg-[#F8FAFC]/60 p-4">
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

                        {/* ===== Usulan Prioritas & Alasan (hanya untuk Admin Departemen) ===== */}
                        {!isSuperAdmin && !isBodDepartment && (
                            <Panel
                                delay="mmx-d4"
                                title="Usulan Prioritas"
                                description="Prioritas final ditetapkan oleh SuperAdmin"
                                icon={<ShieldCheckIcon className="h-4 w-4" />}
                            >
                                <div className="space-y-4">
                                    <div>
                                        <label className="mmx-label">Usulkan Prioritas</label>
                                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                            {([
                                                { value: 'NORMAL', label: 'Normal', desc: 'Perjalanan sesuai jadwal biasa' },
                                                { value: 'URGENT', label: 'Urgent', desc: 'Perlu penanganan segera' },
                                                { value: 'VERY_URGENT', label: 'Very Urgent', desc: 'Sangat mendesak / darurat' },
                                            ] as const).map((opt) => {
                                                const active = data.requested_priority === opt.value;
                                                const tone = opt.value === 'VERY_URGENT'
                                                    ? { color: '#DC2626', bg: '#FEF2F2', border: '#FECACA' }
                                                    : opt.value === 'URGENT'
                                                        ? { color: '#B45309', bg: '#FFFBEB', border: '#FDE68A' }
                                                        : { color: '#475569', bg: '#F8FAFC', border: '#E2E8F0' };

                                                return (
                                                    <button
                                                        key={opt.value}
                                                        type="button"
                                                        onClick={() => setData('requested_priority', opt.value)}
                                                        aria-pressed={active}
                                                        className={`rounded-xl border-2 p-3 text-left transition-all duration-200 ${
                                                            active ? 'shadow-md' : 'border-[#E2E8F0] bg-white hover:border-[#CBD5E1]'
                                                        }`}
                                                        style={active ? { borderColor: tone.color, backgroundColor: tone.bg } : undefined}
                                                    >
                                                        <p className="text-sm font-semibold" style={{ color: active ? tone.color : '#0F172A' }}>
                                                            {opt.label}
                                                        </p>
                                                        <p className="mt-0.5 text-xs text-[#64748B]">{opt.desc}</p>
                                                    </button>
                                                );
                                            })}
                                        </div>
                                        {errors.requested_priority && <p className="mt-1 text-xs text-red-600">{errors.requested_priority}</p>}
                                    </div>

                                    {data.requested_priority !== 'NORMAL' && (
                                        <div className="animate-slide-up rounded-xl border border-[#FDE68A] bg-[#FFFBEB] p-4">
                                            <label className="mmx-label flex items-center gap-1.5 text-[#B45309]">
                                                <ExclamationTriangleIcon className="h-4 w-4" />
                                                Alasan Urgensi <span className="text-red-500">*</span>
                                            </label>
                                            <textarea
                                                rows={3}
                                                value={data.urgency_notes}
                                                onChange={(e) => setData('urgency_notes', e.target.value)}
                                                className={inputCls(errors.urgency_notes)}
                                                placeholder="Jelaskan kenapa perjalanan ini harus Urgent / Very Urgent (mis. klien mendadak, jadwal meeting, kondisi darurat, dll)"
                                            />
                                            {errors.urgency_notes && <p className="mt-1 text-xs text-red-600">{errors.urgency_notes}</p>}
                                            <p className="mt-1.5 text-xs text-[#B45309]">
                                                SuperAdmin akan meninjau alasan ini sebelum menetapkan prioritas final.
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </Panel>
                        )}

                        {/* ===== Actions ===== */}
                        <div className="flex justify-end gap-3">
                            <Link
                                href={route('trips.index')}
                                className="rounded-lg border border-[#E2E8F0] bg-white px-4 py-2 text-sm font-medium text-[#64748B] transition hover:border-[#CBD5E1] hover:text-[#0F172A]"
                            >
                                Batal
                            </Link>
                            <PrimaryButton disabled={processing} type="submit">
                                {processing
                                    ? 'Menyimpan...'
                                    : isBodDepartment
                                        ? 'Simpan & Kirim ke Driver'
                                        : 'Simpan Permintaan'}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
