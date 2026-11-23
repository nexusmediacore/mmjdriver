import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';
import ConfirmDialog from '@/Components/ConfirmDialog';
import RequestTypeBadge from '@/Components/UI/RequestTypeBadge';

interface Trip {
    id: number;
    trip_number: string;
    destination: string;
    destination_address: string | null;
    request_type: string;
    guest_pickup_location: string | null;
    cargo_pickup_location: string | null;
    cargo_description: string | null;
    guest_institution: string | null;
    purpose: string;
    trip_date: string | null;
    departure_time: string;
    actual_return_time: string | null;
    passenger_count: number;
    priority: string;
    requested_priority: string | null;
    urgency_notes: string | null;
    requires_loading_assistance: boolean;
    notes: string | null;
    status: string;
    created_at: string;
    approved_at: string | null;
    rejection_reason: string | null;
    approval_notes: string | null;
    started_at: string | null;
    completed_at: string | null;
    completion_notes: string | null;
    bod_link: string | null;
    is_bod: boolean;
    completion_submitted_at: string | null;
    completion_submitted_by: string | null;
    completion_approved_at: string | null;
    completion_approved_by: string | null;
    can_submit_completion: boolean;
    assigned_driver_ids: number[];
    assignments: Array<{
        id: number;
        driver_id: number;
        role: string;
        driver_response: string;
        driver_name: string | null;
        vehicle_id: number | null;
    }>;
    requestor: { id: number; name: string; email: string } | null;
    department: { id: number; name: string; code: string } | null;
    driver: { id: number; name: string; license_number: string } | null;
    vehicle: { id: number; plate_number: string; brand: string; model: string } | null;
    passengers: Array<{ id: number; name: string; phone: string | null; department: string | null; position: string | null }>;
    approvals: Array<{ id: number; decision: string; notes: string | null; decided_at: string; approver: { name: string } | null }>;
    assignment: {
        id: number;
        driver_response: string;
        driver_rejection_reason: string | null;
        assigned_at: string | null;
        driver_accepted_at: string | null;
        driver: { user: { name: string } } | null;
        vehicle: { plate_number: string; brand: string; model: string } | null;
    } | null;
    statusLogs: Array<{ id: number; from_status: string | null; to_status: string; created_at: string; changedBy: { name: string } | null }>;
    loadingRequest: { id: number; status: string } | null;
    beritaAcara: { id: number; ba_number: string } | null;
}

interface DriverOption {
    id: number;
    name: string;
    license_number: string;
    phone: string;
    status: string;
    willing_to_load: boolean;
    allowed_vehicle_ids: number[];
}

interface VehicleOption {
    id: number;
    plate_number: string;
    brand: string;
    model: string;
    type: string;
    status: string;
    authorized_driver_ids: number[];
}

interface Props {
    trip: Trip;
    drivers: DriverOption[];
    vehicles: VehicleOption[];
}

function StatusBadge({ status }: { status: string }) {
    const colors: Record<string, string> = {
        DRAFT: 'bg-gray-100 text-gray-800',
        SUBMITTED: 'bg-blue-100 text-blue-800',
        WAITING_APPROVAL: 'bg-yellow-100 text-yellow-800',
        APPROVED: 'bg-indigo-100 text-indigo-800',
        REJECTED: 'bg-red-100 text-red-800',
        WAITING_DRIVER: 'bg-purple-100 text-purple-800',
        DRIVER_ASSIGNED: 'bg-blue-100 text-blue-800',
        DRIVER_ACCEPTED: 'bg-green-100 text-green-800',
        DRIVER_REJECTED: 'bg-red-100 text-red-800',
        READY_TO_DEPART: 'bg-teal-100 text-teal-800',
        ON_TRIP: 'bg-orange-100 text-orange-800',
        COMPLETION_SUBMITTED: 'bg-amber-100 text-amber-800',
        COMPLETED: 'bg-emerald-100 text-emerald-800',
        CANCELLED: 'bg-gray-100 text-gray-800',
    };
    return (
        <span className={`inline-flex rounded-full px-3 py-1 text-sm font-medium ${colors[status] || 'bg-gray-100 text-gray-800'}`}>
            {status}
        </span>
    );
}

function InfoRow({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="border-b border-gray-100 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
            <dt className="text-sm font-medium text-gray-500">{label}</dt>
            <dd className="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{value || '-'}</dd>
        </div>
    );
}

export default function TripsShow({ trip, drivers, vehicles }: Props) {
    const auth = usePage().props.auth.user;
    const role = auth?.role?.name;
    const [approveNotes, setApproveNotes] = useState('');
    const [approvePriority, setApprovePriority] = useState<'NORMAL' | 'URGENT' | 'VERY_URGENT'>('NORMAL');
    const [rejectReason, setRejectReason] = useState('');
    const [startNotes, setStartNotes] = useState('');
    const [completeNotes, setCompleteNotes] = useState('');
    const [showApproveModal, setShowApproveModal] = useState(false);
    const [showRejectModal, setShowRejectModal] = useState(false);
    const [showStartModal, setShowStartModal] = useState(false);
    const [showCompleteModal, setShowCompleteModal] = useState(false);
    const [showAssignModal, setShowAssignModal] = useState(false);
    const [selectedDriverId, setSelectedDriverId] = useState<number | null>(null);
    const [selectedVehicleId, setSelectedVehicleId] = useState<number | null>(null);
    // Driver pendukung loading (feature multi-driver Purchasing)
    const [additionalDriverIds, setAdditionalDriverIds] = useState<number[]>([]);

    const formatDateTime = (value: string | null) => {
        if (!value) return null;
        // Nilai waktu dari backend adalah waktu lokal (WIB) tanpa penanda
        // zona. Normalisasi spasi ke 'T' agar selalu diparse sebagai waktu
        // LOKAL browser — bukan UTC — sehingga jam tidak bergeser.
        const normalized = value.includes('T') ? value : value.replace(' ', 'T');
        return new Date(normalized).toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' });
    };

    const isDraft = trip.status === 'DRAFT';
    const canSubmit = isDraft && (trip.requestor?.id === auth?.id || ['SUPERADMIN', 'ADMIN'].includes(role || ''));
    const canApprove = trip.status === 'WAITING_APPROVAL' && ['SUPERADMIN', 'ADMIN', 'APPROVER'].includes(role || '');
    const isAssignedDriver = trip.assignment?.driver?.user?.name === auth?.name;
    const canDriverRespond = trip.status === 'DRIVER_ASSIGNED' && (role === 'DRIVER' || isAssignedDriver);
    const canStart = ['DRIVER_ACCEPTED', 'READY_TO_DEPART'].includes(trip.status) && (role === 'DRIVER' || isAssignedDriver);
    const canComplete = trip.can_submit_completion;
    // Multi-driver hanya untuk trip yang membutuhkan bantuan loading.
    const canAssignMultipleDrivers = Boolean(trip.requires_loading_assistance);
    const toggleAdditionalDriver = (driverId: number) => {
        setAdditionalDriverIds((prev) =>
            prev.includes(driverId) ? prev.filter((id) => id !== driverId) : [...prev, driverId]
        );
    };
    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Detail Perjalanan</h2>}>
            <Head title={`Detail - ${trip.trip_number}`} />

            <div className="py-12">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                        <div className="flex flex-wrap items-center gap-3">
                            <Link
                                href={route('trips.index')}
                                className="inline-flex items-center gap-1 text-sm text-gray-600 hover:text-gray-900"
                            >
                                <ArrowLeftIcon className="h-4 w-4" />
                                Kembali
                            </Link>
                            <h1 className="text-2xl font-bold text-gray-900">{trip.trip_number}</h1>
                            <RequestTypeBadge type={trip.request_type} size="md" />
                            <StatusBadge status={trip.status} />
                        </div>
                        {trip.priority !== 'NORMAL' && (
                            <span className={`inline-flex rounded px-3 py-1 text-sm font-medium ${
                                trip.priority === 'VERY_URGENT' ? 'bg-red-100 text-red-800' : 'bg-orange-100 text-orange-800'
                            }`}>
                                {trip.priority}
                            </span>
                        )}
                    </div>

                    {trip.request_type === 'GUEST_PICKUP' && (
                        <div className="mb-6 rounded-lg border border-[#FDE68A] bg-[#FFFBEB] p-6 shadow">
                            <h3 className="mb-4 flex items-center gap-2 text-lg font-semibold text-[#B45309]">
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                </svg>
                                Detail Penjemputan Tamu
                            </h3>
                            <dl>
                                <InfoRow label="Lokasi Penjemputan" value={trip.guest_pickup_location} />
                                <InfoRow label="Instansi / Instalasi Tamu" value={trip.guest_institution} />
                                <InfoRow label="Tujuan Antar" value={trip.destination} />
                            </dl>
                        </div>
                    )}

                    {trip.request_type === 'CARGO_PICKUP' && (
                        <div className="mb-6 rounded-lg border border-[#DDD6FE] bg-[#F5F0FF] p-6 shadow">
                            <h3 className="mb-4 flex items-center gap-2 text-lg font-semibold text-[#6D28D9]">
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                                </svg>
                                Detail Muat Barang
                            </h3>
                            <dl>
                                <InfoRow label="Lokasi Muat Barang" value={trip.cargo_pickup_location} />
                                <InfoRow label="Deskripsi Barang" value={trip.cargo_description} />
                                <InfoRow label="Tujuan Antar" value={trip.destination} />
                            </dl>
                        </div>
                    )}

                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        <div className="space-y-6 lg:col-span-2">
                            <div className="rounded-lg bg-white p-6 shadow">
                                <h3 className="mb-4 text-lg font-semibold text-gray-900">
                                    {trip.request_type === 'GUEST_PICKUP' ? 'Waktu Penjemputan' : 'Informasi Perjalanan'}
                                </h3>
                                <dl>
                                    {trip.request_type !== 'GUEST_PICKUP' && (
                                        <>
                                            <InfoRow label="Tujuan" value={trip.destination} />
                                            <InfoRow label="Alamat Tujuan" value={trip.destination_address} />
                                        </>
                                    )}
                                    <InfoRow label="Keperluan" value={trip.purpose} />
                                    <InfoRow label="Tanggal Perjalanan" value={trip.trip_date ? new Date(`${trip.trip_date}T00:00:00`).toLocaleDateString('id-ID', { dateStyle: 'full' }) : null} />
                                    <InfoRow label="Jam Berangkat" value={formatDateTime(trip.departure_time)} />
                                    {trip.actual_return_time ? (
                                        <InfoRow
                                            label="Jam Selesai"
                                            value={
                                                <span className="inline-flex items-center gap-1.5">
                                                    <span className="inline-block h-1.5 w-1.5 rounded-full bg-[#16A34A]" />
                                                    {formatDateTime(trip.actual_return_time)}
                                                </span>
                                            }
                                        />
                                    ) : (
                                        <InfoRow label="Jam Selesai" value={<span className="text-[#94A3B8]">Belum selesai — akan tercatat saat driver menyelesaikan perjalanan</span>} />
                                    )}
                                    <InfoRow label="Jumlah Penumpang" value={trip.passenger_count} />
                                    {trip.request_type !== 'GUEST_PICKUP' && (
                                        <InfoRow label="Bantuan Loading" value={trip.requires_loading_assistance ? 'Ya' : 'Tidak'} />
                                    )}
                                    {trip.bod_link && (
                                        <InfoRow
                                        label="Tautan Referensi"
                                        value={
                                            <a
                                                href={trip.bod_link}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="inline-flex max-w-full items-center gap-1 break-all font-medium text-[#2563EB] underline hover:text-[#1D4ED8]"
                                            >
                                                {trip.bod_link}
                                            </a>
                                        }
                                        />
                                    )}
                                    <InfoRow label="Catatan" value={trip.notes} />
                                    {trip.urgency_notes && (
                                        <div className="mt-4 rounded-lg border border-[#FDE68A] bg-[#FFFBEB] p-3">
                                            <p className="text-xs font-semibold uppercase tracking-wide text-[#B45309]">
                                                Usulan Prioritas
                                                {trip.requested_priority ? `: ${trip.requested_priority}` : ''}
                                            </p>
                                            <p className="mt-1 text-sm text-[#92400E]">{trip.urgency_notes}</p>
                                            <p className="mt-1.5 text-xs text-[#B45309]">
                                                Prioritas final {trip.priority === 'NORMAL' ? 'belum' : `(${trip.priority})`} ditetapkan oleh SuperAdmin.
                                            </p>
                                        </div>
                                    )}
                                </dl>
                            </div>

                            <div className="rounded-lg bg-white p-6 shadow">
                                <h3 className="mb-4 text-lg font-semibold text-gray-900">Pemohon & Departemen</h3>
                                <dl>
                                    <InfoRow label="Pemohon" value={trip.requestor?.name} />
                                    <InfoRow label="Departemen" value={trip.department?.name} />
                                    <InfoRow label="Dibuat Pada" value={formatDateTime(trip.created_at)} />
                                </dl>
                            </div>

                            <div className="rounded-lg bg-white p-6 shadow">
                                <h3 className="mb-4 text-lg font-semibold text-gray-900">Daftar Penumpang</h3>
                                {trip.passengers.length > 0 ? (
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-sm">
                                            <thead>
                                                <tr className="border-b border-gray-200 text-left text-gray-500">
                                                    <th className="pb-2">Nama</th>
                                                    <th className="pb-2">Telepon</th>
                                                    <th className="pb-2">Departemen</th>
                                                    <th className="pb-2">Jabatan</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-gray-100">
                                                {trip.passengers.map((passenger) => (
                                                    <tr key={passenger.id}>
                                                        <td className="py-2 font-medium text-gray-900">{passenger.name}</td>
                                                        <td className="py-2 text-gray-600">{passenger.phone || '-'}</td>
                                                        <td className="py-2 text-gray-600">{passenger.department || '-'}</td>
                                                        <td className="py-2 text-gray-600">{passenger.position || '-'}</td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                ) : (
                                    <p className="text-gray-500">Tidak ada data penumpang.</p>
                                )}
                            </div>

                            <div className="rounded-lg bg-white p-6 shadow">
                                <h3 className="mb-4 text-lg font-semibold text-gray-900">Riwayat Status</h3>
                                <ol className="relative border-s border-gray-200">
                                    {trip.statusLogs.map((log) => (
                                        <li key={log.id} className="mb-6 ms-4">
                                            <span className="absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full border border-white bg-indigo-500"></span>
                                            <time className="mb-1 text-xs font-normal text-gray-400">{formatDateTime(log.created_at)}</time>
                                            <p className="text-sm text-gray-700">
                                                <span className="font-medium">{log.changedBy?.name || 'Sistem'}</span> mengubah status
                                                {log.from_status && <span className="font-medium"> dari {log.from_status}</span>} ke
                                                <span className="font-medium"> {log.to_status}</span>
                                            </p>
                                        </li>
                                    ))}
                                </ol>
                            </div>

                            {trip.approvals.length > 0 && (
                                <div className="rounded-lg bg-white p-6 shadow">
                                    <h3 className="mb-4 text-lg font-semibold text-gray-900">Riwayat Approval</h3>
                                    <div className="space-y-3">
                                        {trip.approvals.map((approval) => (
                                            <div key={approval.id} className="rounded-md border border-gray-100 p-3">
                                                <p className="text-sm font-medium text-gray-900">
                                                    {approval.approver?.name} - {approval.decision}
                                                </p>
                                                {approval.notes && <p className="text-sm text-gray-600">{approval.notes}</p>}
                                                <p className="text-xs text-gray-400">{formatDateTime(approval.decided_at)}</p>
                                            </div>
                                        ))}
                                    </div>
                                    {trip.rejection_reason && (
                                        <div className="mt-3 rounded-md bg-red-50 p-3">
                                            <p className="text-sm font-medium text-red-800">Alasan Penolakan:</p>
                                            <p className="text-sm text-red-700">{trip.rejection_reason}</p>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>

                        <div className="space-y-6">
                            <div className="rounded-lg bg-white p-6 shadow">
                                <h3 className="mb-4 text-lg font-semibold text-gray-900">Driver & Kendaraan</h3>
                                <dl>
                                    <InfoRow label="Driver" value={trip.driver?.name} />
                                    <InfoRow label="No. Lisensi" value={trip.driver?.license_number} />
                                    <InfoRow label="Kendaraan" value={trip.vehicle ? `${trip.vehicle.brand} ${trip.vehicle.model}` : null} />
                                    <InfoRow label="Plat Nomor" value={trip.vehicle?.plate_number} />
                                    <InfoRow label="Waktu Mulai" value={formatDateTime(trip.started_at)} />
                                    <InfoRow label="Waktu Selesai" value={formatDateTime(trip.completed_at)} />
                                </dl>
                                {trip.completion_notes && (
                                    <div className="mt-3 rounded-md bg-gray-50 p-3">
                                        <p className="text-sm font-medium text-gray-900">Catatan Penyelesaian:</p>
                                        <p className="text-sm text-gray-700">{trip.completion_notes}</p>
                                    </div>
                                )}

                                {trip.completion_submitted_at && (
                                    <div className="mt-3 rounded-md border border-amber-200 bg-amber-50 p-3">
                                        <p className="text-sm font-medium text-amber-900">Pengajuan Selesai</p>
                                        <p className="text-xs text-amber-800">
                                            Diajukan oleh {trip.completion_submitted_by ?? '-'} pada {formatDateTime(trip.completion_submitted_at)}
                                        </p>
                                        {trip.completion_approved_at && (
                                            <p className="mt-1 text-xs text-emerald-700">
                                                Disetujui oleh {trip.completion_approved_by ?? '-'} pada {formatDateTime(trip.completion_approved_at)}
                                            </p>
                                        )}
                                    </div>
                                )}
                            </div>

                            {trip.assignments.length > 0 && (
                                <div className="rounded-lg bg-white p-6 shadow">
                                    <h3 className="mb-4 text-lg font-semibold text-gray-900">Driver Penugasan</h3>
                                    <ul className="space-y-2">
                                        {trip.assignments.map((a) => (
                                            <li key={a.id} className="flex items-center justify-between gap-2 rounded-md border border-gray-100 px-3 py-2">
                                                <div className="min-w-0">
                                                    <p className="truncate text-sm font-medium text-gray-900">{a.driver_name ?? '-'}</p>
                                                    <p className="text-xs text-gray-500">
                                                        {a.role === 'MAIN' ? 'Driver Utama' : 'Pendukung Loading'} • {a.driver_response}
                                                    </p>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            )}

                            {trip.assignment?.driver_rejection_reason && (
                                <div className="rounded-lg bg-white p-6 shadow">
                                    <h3 className="mb-4 text-lg font-semibold text-gray-900">Alasan Penolakan Driver</h3>
                                    <p className="text-sm text-gray-700">{trip.assignment.driver_rejection_reason}</p>
                                </div>
                            )}

                            <div className="rounded-lg bg-white p-6 shadow">
                                <h3 className="mb-4 text-lg font-semibold text-gray-900">Aksi</h3>
                                <div className="space-y-3">
                                    {trip.status === 'WAITING_DRIVER' && ['SUPERADMIN', 'ADMIN', 'DISPATCHER'].includes(role || '') && (
                                        <button
                                            onClick={() => setShowAssignModal(true)}
                                            className="w-full rounded-md bg-[#2563EB] px-4 py-2 text-sm font-medium text-white hover:bg-[#1D4ED8]"
                                        >
                                            Assign Driver &amp; Kendaraan
                                        </button>
                                    )}

                                    {canSubmit && (
                                        <button
                                            onClick={() => router.post(route('trips.submit', trip.id), {}, { preserveScroll: true })}
                                            className="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                                        >
                                            Submit Permintaan
                                        </button>
                                    )}

                                    {canApprove && (
                                        <>
                                            <button
                                                onClick={() => setShowApproveModal(true)}
                                                className="w-full rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                                            >
                                                Setujui
                                            </button>
                                            <button
                                                onClick={() => setShowRejectModal(true)}
                                                className="w-full rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                                            >
                                                Tolak
                                            </button>
                                        </>
                                    )}

                                    {canDriverRespond && (
                                        <>
                                            <button
                                                onClick={() => router.post(route('trips.driver-response', trip.id), { action: 'accept' }, { preserveScroll: true })}
                                                className="w-full rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                                            >
                                                Terima Penugasan
                                            </button>
                                            <button
                                                onClick={() => {
                                                    const reason = window.prompt('Alasan menolak penugasan:');
                                                    if (reason) {
                                                        router.post(route('trips.driver-response', trip.id), { action: 'reject', reason }, { preserveScroll: true });
                                                    }
                                                }}
                                                className="w-full rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                                            >
                                                Tolak Penugasan
                                            </button>
                                        </>
                                    )}

                                    {canStart && (
                                        <button
                                            onClick={() => setShowStartModal(true)}
                                            className="w-full rounded-md bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700"
                                        >
                                            Mulai Perjalanan
                                        </button>
                                    )}

                                    {canComplete && (
                                        <button
                                            onClick={() => setShowCompleteModal(true)}
                                            className="w-full rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700"
                                        >
                                            Selesaikan Perjalanan
                                        </button>
                                    )}

                                    {trip.status === 'COMPLETION_SUBMITTED' && (
                                        <p className="rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                            Pengajuan selesai menunggu persetujuan Admin/SuperAdmin.
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    <ConfirmDialog
                        open={showAssignModal}
                        onClose={() => setShowAssignModal(false)}
                        title="Assign Driver & Kendaraan"
                        confirmLabel="Tugaskan"
                        onConfirm={() => {
                            if (selectedDriverId && selectedVehicleId) {
                                router.post(
                                    route('trips.assign-driver', trip.id),
                                    {
                                        driver_id: selectedDriverId,
                                        vehicle_id: selectedVehicleId,
                                        additional_driver_ids: additionalDriverIds,
                                    },
                                    {
                                        preserveScroll: true,
                                        onSuccess: () => {
                                            setShowAssignModal(false);
                                            setAdditionalDriverIds([]);
                                        },
                                    }
                                );
                            }
                        }}
                    >
                        <div className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Driver</label>
                                <select
                                    value={selectedDriverId ?? ''}
                                    onChange={(e) => {
                                        setSelectedDriverId(e.target.value ? Number(e.target.value) : null);
                                        setSelectedVehicleId(null);
                                    }}
                                    className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Pilih Driver</option>
                                    {drivers
                                        // Driver non-aktif disaring. Status ASSIGNED / ACCEPTED
                                        // tetap ditampilkan: driver dengan trip terjadwal masih
                                        // bisa dipilih selama jadwalnya tidak bentrok (divalidasi
                                        // backend saat penugasan).
                                        .filter((d) => !['SICK', 'LEAVE', 'OFF_DUTY', 'UNAVAILABLE'].includes(d.status))
                                        .map((d) => (
                                            <option key={d.id} value={d.id}>
                                                {d.name} ({d.license_number})
                                                {trip.requires_loading_assistance && !d.willing_to_load ? ' — tidak bersedia loading' : ''}
                                            </option>
                                        ))}
                                </select>
                            </div>

                            {canAssignMultipleDrivers && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        Driver Pendukung Loading (opsional)
                                    </label>
                                    <p className="mt-1 text-xs text-gray-500">
                                        Pengajuan Purchasing dengan bantuan loading dapat memakai lebih dari satu driver. Hanya driver yang
                                        bersedia membantu loading yang ditampilkan.
                                    </p>
                                    <div className="mt-2 max-h-44 space-y-1 overflow-y-auto rounded-md border border-gray-200 p-2">
                                        {drivers
                                            .filter(
                                                (d) =>
                                                    // Driver pendukung: bukan driver utama, tidak
                                                    // sedang ON_TRIP, belum ditugaskan di trip ini,
                                                    // dan bukan status tidak-bekerja.
                                                    d.willing_to_load &&
                                                    d.id !== selectedDriverId &&
                                                    d.status !== 'ON_TRIP' &&
                                                    !['SICK', 'LEAVE', 'OFF_DUTY', 'UNAVAILABLE'].includes(d.status) &&
                                                    !trip.assigned_driver_ids.includes(d.id)
                                            )
                                            .map((d) => (
                                                <label
                                                    key={d.id}
                                                    className="flex cursor-pointer items-center gap-2 rounded px-2 py-1 text-sm hover:bg-gray-50"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        checked={additionalDriverIds.includes(d.id)}
                                                        onChange={() => toggleAdditionalDriver(d.id)}
                                                        className="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                    />
                                                    <span className="flex-1">
                                                        {d.name} ({d.license_number})
                                                    </span>
                                                    <span className="text-xs text-gray-400">{d.status}</span>
                                                </label>
                                            ))}
                                    </div>
                                </div>
                            )}

                            <div>
                                <label className="block text-sm font-medium text-gray-700">Kendaraan</label>
                                <select
                                    value={selectedVehicleId ?? ''}
                                    onChange={(e) => setSelectedVehicleId(e.target.value ? Number(e.target.value) : null)}
                                    className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500"
                                    disabled={!selectedDriverId}
                                >
                                    <option value="">Pilih Kendaraan</option>
                                    {selectedDriverId &&
                                        vehicles
                                            .filter((v) => {
                                                const driver = drivers.find((d) => d.id === selectedDriverId);
                                                const isAllowed = driver?.allowed_vehicle_ids.includes(v.id);
                                                const isAvailable = ['AVAILABLE', 'ASSIGNED'].includes(v.status);
                                                return isAllowed && isAvailable;
                                            })
                                            .map((v) => (
                                                <option key={v.id} value={v.id}>
                                                    {v.brand} {v.model} ({v.plate_number})
                                                </option>
                                            ))}
                                </select>
                            </div>

                            {selectedDriverId && selectedVehicleId && (
                                <div className="rounded-md bg-green-50 p-3">
                                    <p className="text-sm text-green-800">
                                        ✓ Driver diperbolehkan menggunakan kendaraan ini.
                                    </p>
                                </div>
                            )}

                            {selectedDriverId && !selectedVehicleId && (
                                <div className="rounded-md bg-red-50 p-3">
                                    <p className="text-sm text-red-800">
                                        ⚠ Pilih driver terlebih dahulu untuk melihat kendaraan yang tersedia.
                                    </p>
                                </div>
                            )}
                        </div>
                    </ConfirmDialog>

                    <ConfirmDialog
                        open={showApproveModal}
                        onClose={() => setShowApproveModal(false)}
                        title="Setujui Permintaan"
                        confirmLabel="Setujui"
                        onConfirm={() => {
                            router.post(
                                route('trips.approve', trip.id),
                                role === 'SUPERADMIN'
                                    ? { notes: approveNotes, priority: approvePriority }
                                    : { notes: approveNotes },
                                { preserveScroll: true }
                            );
                            setShowApproveModal(false);
                            setApproveNotes('');
                            setApprovePriority('NORMAL');
                        }}
                    >
                        {/* Usulan & alasan urgensi dari admin departemen */}
                        {trip.requested_priority && trip.requested_priority !== 'NORMAL' && (
                            <div className="mb-4 rounded-lg border border-[#FDE68A] bg-[#FFFBEB] p-3">
                                <p className="text-xs font-semibold uppercase tracking-wide text-[#B45309]">
                                    Usulan Prioritas Pemohon
                                </p>
                                <p className="mt-1 text-sm font-semibold text-[#B45309]">{trip.requested_priority}</p>
                                {trip.urgency_notes && (
                                    <>
                                        <p className="mt-2 text-xs font-medium text-[#B45309]">Alasan:</p>
                                        <p className="mt-0.5 text-sm text-[#92400E]">{trip.urgency_notes}</p>
                                    </>
                                )}
                            </div>
                        )}

                        {/* Hanya SuperAdmin yang menetapkan prioritas final */}
                        {role === 'SUPERADMIN' ? (
                            <div className="mb-3">
                                <label className="block text-sm font-medium text-gray-700">
                                    Tetapkan Prioritas <span className="text-red-500">*</span>
                                </label>
                                <select
                                    value={approvePriority}
                                    onChange={(e) => setApprovePriority(e.target.value as 'NORMAL' | 'URGENT' | 'VERY_URGENT')}
                                    className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="NORMAL">Normal</option>
                                    <option value="URGENT">Urgent</option>
                                    <option value="VERY_URGENT">Very Urgent</option>
                                </select>
                                <p className="mt-1 text-xs text-gray-500">
                                    Hanya SuperAdmin yang dapat menentukan prioritas final.
                                </p>
                            </div>
                        ) : (
                            <p className="mb-3 rounded-md bg-[#F1F5F9] px-3 py-2 text-xs text-[#64748B]">
                                Prioritas akan ditetapkan oleh SuperAdmin.
                            </p>
                        )}

                        <label className="block text-sm font-medium text-gray-700">Catatan (opsional)</label>
                        <textarea
                            value={approveNotes}
                            onChange={(e) => setApproveNotes(e.target.value)}
                            rows={3}
                            className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </ConfirmDialog>

                    <ConfirmDialog
                        open={showRejectModal}
                        onClose={() => setShowRejectModal(false)}
                        title="Tolak Permintaan"
                        confirmLabel="Tolak"
                        danger
                        onConfirm={() => {
                            if (!rejectReason.trim()) {
                                window.alert('Alasan penolakan wajib diisi.');
                                return;
                            }
                            router.post(route('trips.reject', trip.id), { reason: rejectReason }, { preserveScroll: true });
                            setShowRejectModal(false);
                            setRejectReason('');
                        }}
                    >
                        <label className="block text-sm font-medium text-gray-700">Alasan Penolakan <span className="text-red-500">*</span></label>
                        <textarea
                            value={rejectReason}
                            onChange={(e) => setRejectReason(e.target.value)}
                            rows={3}
                            className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Masukkan alasan penolakan"
                        />
                    </ConfirmDialog>

                    <ConfirmDialog
                        open={showStartModal}
                        onClose={() => setShowStartModal(false)}
                        title="Mulai Perjalanan"
                        confirmLabel="Mulai"
                        onConfirm={() => {
                            router.post(route('trips.start', trip.id), { notes: startNotes }, { preserveScroll: true });
                            setShowStartModal(false);
                            setStartNotes('');
                        }}
                    >
                        <label className="block text-sm font-medium text-gray-700">Catatan (opsional)</label>
                        <textarea
                            value={startNotes}
                            onChange={(e) => setStartNotes(e.target.value)}
                            rows={3}
                            className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </ConfirmDialog>

                    <ConfirmDialog
                        open={showCompleteModal}
                        onClose={() => setShowCompleteModal(false)}
                        title="Selesaikan Perjalanan"
                        confirmLabel="Ajukan Selesai"
                        onConfirm={() => {
                            router.post(route('trips.complete', trip.id), { notes: completeNotes }, { preserveScroll: true });
                            setShowCompleteModal(false);
                            setCompleteNotes('');
                        }}
                    >
                        <p className="mb-3 rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-800">
                            Pengajuan selesai akan dikirim ke Admin/SuperAdmin untuk persetujuan akhir. Status perjalanan menjadi
                            COMPLETED setelah disetujui.
                        </p>
                        <label className="block text-sm font-medium text-gray-700">Catatan Penyelesaian (opsional)</label>
                        <textarea
                            value={completeNotes}
                            onChange={(e) => setCompleteNotes(e.target.value)}
                            rows={3}
                            className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </ConfirmDialog>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}