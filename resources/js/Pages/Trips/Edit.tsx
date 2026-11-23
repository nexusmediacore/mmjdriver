import { useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { ArrowLeftIcon, PlusIcon, TrashIcon, UserGroupIcon, BriefcaseIcon, UsersIcon, BuildingOffice2Icon, CubeIcon } from '@heroicons/react/24/outline';
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

interface TripData {
    id: number;
    request_type: RequestType;
    department_id: number | null;
    destination: string;
    destination_address: string | null;
    guest_pickup_location: string | null;
    guest_institution: string | null;
    cargo_pickup_location: string | null;
    cargo_description: string | null;
    purpose: string;
    trip_date: string | null;
    departure_time: string;
    passenger_count: number;
    priority: string;
    requested_priority: string | null;
    urgency_notes: string | null;
    requires_loading_assistance: boolean;
    notes: string | null;
    bod_link: string | null;
    passengers: Passenger[];
    loading_support_driver_ids: number[];
}

interface FormData {
    request_type: RequestType;
    department_id: string;
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
    trip: TripData;
    drivers: { id: number; name: string; status: string; willing_to_load: boolean }[];
}

/**
 * Departemen yang trip-nya otomatis disetujui tanpa alur approval.
 * Harus sama dengan Department::CODE_BOD di backend.
 */
const inputCls = (hasError?: string) =>
    `mt-1 block w-full rounded-lg border px-3 py-2 text-sm transition focus:outline-none focus:ring-4 ${
        hasError
            ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10'
            : 'border-[#E2E8F0] focus:border-[#2563EB] focus:ring-[#2563EB]/10'
    }`;

export default function TripsEdit({ trip, drivers }: Props) {
    const auth = usePage().props.auth.user as any;
    const { data, setData, put, processing, errors } = useForm<FormData>({
        request_type: trip.request_type,
        department_id: trip.department_id ? String(trip.department_id) : '',
        destination: trip.destination ?? '',
        destination_address: trip.destination_address ?? '',
        guest_pickup_location: trip.guest_pickup_location ?? '',
        guest_institution: trip.guest_institution ?? '',
        cargo_pickup_location: trip.cargo_pickup_location ?? '',
        cargo_description: trip.cargo_description ?? '',
        purpose: trip.purpose ?? '',
        trip_date: trip.trip_date ?? '',
        departure_time: trip.departure_time ?? '',
        passenger_count: trip.passenger_count ?? 1,
        priority: (trip.priority as FormData['priority']) ?? 'NORMAL',
        requested_priority: (trip.requested_priority as FormData['requested_priority']) ?? 'NORMAL',
        urgency_notes: trip.urgency_notes ?? '',
        requires_loading_assistance: trip.requires_loading_assistance,
        loading_support_driver_ids: trip.loading_support_driver_ids ?? [],
        bod_link: trip.bod_link ?? '',
        notes: trip.notes ?? '',
        passengers: trip.passengers?.length
            ? trip.passengers
            : [{ name: auth?.name || '', phone: auth?.phone || '', department: auth?.department?.name || '', position: '' }],
    });

    const isGuestPickup = data.request_type === 'GUEST_PICKUP';
    const isCargoPickup = data.request_type === 'CARGO_PICKUP';
    const isSuperAdmin = auth?.role?.name === 'SUPERADMIN';

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
        setData('passenger_count', Math.max(1, data.passengers.filter((p) => p.name.trim()).length));

        put(route('trips.update', trip.id), {
            onError: () => {},
        });
    };

    return (
        <AuthenticatedLayout header="Edit Permintaan">
            <Head title="Edit Permintaan" />

            <div className="mmj-page-in py-8 sm:py-10">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    <PageHeader
                        eyebrow="Perjalanan"
                        title="Edit Permintaan Perjalanan"
                        description="Perbarui detail permintaan. Perubahan hanya dapat dilakukan selama status masih Draft."
                    />

                    <form onSubmit={handleSubmit} className="space-y-5">
                        {/* ===== Jenis Permintaan ===== */}
                        <Panel
                            title="Jenis Permintaan"
                            description="Pilih salah satu jenis permintaan"
                            icon={<BriefcaseIcon className="h-4 w-4" />}
                        >
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                {[
                                    { value: 'BUSINESS_TRIP' as RequestType, label: 'Perjalanan Dinas', icon: <BriefcaseIcon className="h-4 w-4" /> },
                                    { value: 'GUEST_PICKUP' as RequestType, label: 'Jemput Tamu', icon: <UsersIcon className="h-4 w-4" /> },
                                    { value: 'CARGO_PICKUP' as RequestType, label: 'Muat Barang', icon: <CubeIcon className="h-4 w-4" /> },
                                ].map((type) => {
                                    const active = data.request_type === type.value;
                                    return (
                                        <button
                                            key={type.value}
                                            type="button"
                                            onClick={() => setData('request_type', type.value)}
                                            className={`flex items-center gap-2 rounded-xl border-2 p-3 text-left text-sm font-semibold transition ${
                                                active
                                                    ? 'border-[#2563EB] bg-[#EFF6FF] text-[#2563EB]'
                                                    : 'border-[#E2E8F0] bg-white text-[#334155] hover:border-[#CBD5E1]'
                                            }`}
                                        >
                                            {type.icon}
                                            {type.label}
                                        </button>
                                    );
                                })}
                            </div>
                        </Panel>

                        {isGuestPickup && (
                            <Panel title="Data Tamu" description="Informasi tamu yang akan dijemput" icon={<UsersIcon className="h-4 w-4" />}>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div className="sm:col-span-2">
                                        <label className="mmx-label">Lokasi Penjemputan <span className="text-red-500">*</span></label>
                                        <input
                                            type="text"
                                            value={data.guest_pickup_location}
                                            onChange={(e) => setData('guest_pickup_location', e.target.value)}
                                            className={inputCls(errors.guest_pickup_location)}
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
                                        />
                                    </div>
                                    <div>
                                        <label className="mmx-label">Tujuan Antar <span className="text-red-500">*</span></label>
                                        <input
                                            type="text"
                                            value={data.destination}
                                            onChange={(e) => setData('destination', e.target.value)}
                                            className={inputCls(errors.destination)}
                                        />
                                    </div>
                                </div>
                            </Panel>
                        )}

                        {isCargoPickup && (
                            <Panel title="Data Barang" description="Informasi barang yang akan dimuat" icon={<CubeIcon className="h-4 w-4" />}>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div className="sm:col-span-2">
                                        <label className="mmx-label">Lokasi Muat Barang <span className="text-red-500">*</span></label>
                                        <input
                                            type="text"
                                            value={data.cargo_pickup_location}
                                            onChange={(e) => setData('cargo_pickup_location', e.target.value)}
                                            className={inputCls(errors.cargo_pickup_location)}
                                        />
                                    </div>
                                    <div className="sm:col-span-2">
                                        <label className="mmx-label">Deskripsi Barang <span className="text-red-500">*</span></label>
                                        <textarea
                                            rows={2}
                                            value={data.cargo_description}
                                            onChange={(e) => setData('cargo_description', e.target.value)}
                                            className={inputCls(errors.cargo_description)}
                                        />
                                    </div>
                                    <div>
                                        <label className="mmx-label">Tujuan Antar <span className="text-red-500">*</span></label>
                                        <input
                                            type="text"
                                            value={data.destination}
                                            onChange={(e) => setData('destination', e.target.value)}
                                            className={inputCls(errors.destination)}
                                        />
                                    </div>
                                </div>
                            </Panel>
                        )}

                        {/* ===== Driver Pendukung Loading ===== */}
                        {(isCargoPickup || data.requires_loading_assistance) && (
                            <Panel
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
                                </div>
                            </Panel>
                        )}

                        {/* ===== Informasi Perjalanan ===== */}
                        <Panel
                            title={isGuestPickup ? 'Waktu & Tujuan' : 'Informasi Perjalanan'}
                            description="Detail perjalanan dinas yang diajukan"
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
                                        </div>
                                    )}

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
                                        </div>
                                    ) : (
                                        <div className="sm:col-span-2">
                                            <label className="mmx-label">Prioritas</label>
                                            <select
                                                value="NORMAL"
                                                disabled
                                                className="mt-1 block w-full cursor-not-allowed rounded-lg border border-[#E2E8F0] bg-[#F1F5F9] px-3 py-2 text-sm text-[#64748B]"
                                            >
                                                <option value="NORMAL">Normal (ditentukan SuperAdmin)</option>
                                            </select>
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
                                    />
                                </div>
                            </div>
                        </Panel>

                        {/* ===== Daftar Penumpang ===== */}
                        {!isGuestPickup && !isCargoPickup && (
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
                                                    />
                                                </div>
                                                <div>
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
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </Panel>
                        )}

                        {/* ===== Aksi ===== */}
                        <div className="flex items-center justify-between gap-3">
                            <Link
                                href={route('trips.show', trip.id)}
                                className="inline-flex items-center gap-2 text-sm font-semibold text-[#64748B] transition hover:text-[#0F172A]"
                            >
                                <ArrowLeftIcon className="h-4 w-4" />
                                Kembali
                            </Link>
                            <PrimaryButton type="submit" disabled={processing} className="mmj-btn mmj-btn-primary">
                                Simpan Perubahan
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
