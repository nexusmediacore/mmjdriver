import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/UI/PageHeader';
import StatCard from '@/Components/UI/StatCard';
import Panel from '@/Components/UI/Panel';
import {
    TruckIcon,
    WrenchIcon,
    MapPinIcon,
    CheckCircleIcon,
    ClipboardDocumentListIcon,
    CameraIcon,
    LinkIcon,
} from '@heroicons/react/24/outline';
import StatusBadge from '@/Components/UI/StatusBadge';

interface ActiveTask {
    id: number;
    trip_number: string;
    destination: string;
    purpose: string;
    departure_time: string;
    status?: string;
    bod_link?: string | null;
    can_complete?: boolean;
}

interface DriverInfo {
    id: number;
    name: string;
    status: string;
    license_number?: string;
    phone?: string | null;
    vehicle_plate?: string | null;
}

interface DriverDashboardProps {
    stats?: {
        assigned_trips: number;
        active_trip: boolean;
        completed_trips: number;
        vehicle?: { plate_number: string; brand: string; model: string };
    };
    assignments?: Array<{ id: number; trip_number: string; destination: string; status: string; departure_time: string }>;
    driver?: DriverInfo | null;
    active_task?: ActiveTask | null;
    next_task?: ActiveTask | null;
    attendance_today?: { clocked_in: boolean; clocked_out: boolean; clock_in_at: string | null; clock_out_at: string | null } | null;
}

export default function DriverDashboard({
    stats,
    assignments,
    driver,
    active_task,
    next_task,
    attendance_today,
}: DriverDashboardProps) {
    const s = stats ?? { assigned_trips: 0, active_trip: false, completed_trips: 0 };
    const [completeNotes, setCompleteNotes] = useState('');
    const [showComplete, setShowComplete] = useState(false);

    const submitCompletion = () => {
        if (!active_task) return;
        router.post(route('trips.complete', active_task.id), { notes: completeNotes }, {
            preserveScroll: true,
            onSuccess: () => {
                setShowComplete(false);
                setCompleteNotes('');
            },
        });
    };

    const clockStatus = !attendance_today?.clocked_in
        ? 'Belum absen masuk'
        : attendance_today.clocked_out
            ? 'Absensi hari ini lengkap'
            : 'Sudah absen masuk';

    return (
        <AuthenticatedLayout header="Driver Dashboard">
            <Head title="Driver Dashboard" />

            <div className="py-8 sm:py-10">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <PageHeader
                        eyebrow="MMJ Driver"
                        title="Driver Dashboard"
                        description="Lihat tugas aktif, status perjalanan, dan riwayat perjalanan Anda."
                        meta={
                            <span className={`inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold ${
                                s.active_trip ? 'border-[#FDBA74] bg-[#FFF7ED] text-[#C2410C]' : 'border-white/15 bg-white/10 text-white/80'
                            }`}>
                                <span className={`h-1.5 w-1.5 rounded-full ${s.active_trip ? 'animate-pulse bg-[#F97316]' : 'bg-[#4ADE80]'}`} />
                                {s.active_trip ? 'Sedang Dalam Perjalanan' : 'Siaga'}
                            </span>
                        }
                    />

                    <div className="mb-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        <StatCard tone="blue" className="mmx-d1" title="Tugas Aktif" value={s.assigned_trips} href={route('driver.my-tasks')} linkLabel="Buka Tugas" icon={<ClipboardDocumentListIcon className="h-5 w-5" />} />
                        <StatCard tone="amber" className="mmx-d2" title="Dalam Perjalanan" value={s.active_trip ? 'Ya' : 'Tidak'} href={route('driver.my-tasks')} linkLabel="Buka Tugas" icon={<MapPinIcon className="h-5 w-5" />} />
                        <StatCard tone="green" className="mmx-d3" title="Selesai" value={s.completed_trips} href={route('driver.trip-history')} linkLabel="Lihat Riwayat" icon={<CheckCircleIcon className="h-5 w-5" />} />
                        <StatCard
                            tone="purple"
                            className="mmx-d4"
                            title="Kendaraan"
                            value={s.vehicle ? `${s.vehicle.brand} ${s.vehicle.model}` : '-'}
                            hint={s.vehicle?.plate_number}
                            icon={<TruckIcon className="h-5 w-5" />}
                        />
                    </div>

                    {driver && (
                        <div className="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <div className="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-sm">
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <p className="text-xs font-semibold uppercase tracking-wide text-[#94A3B8]">Absensi Hari Ini</p>
                                        <p className="mt-1 text-sm font-semibold text-[#0F172A]">{clockStatus}</p>
                                        <p className="mt-0.5 text-xs text-[#64748B]">
                                            Masuk {attendance_today?.clock_in_at ? new Date(attendance_today.clock_in_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '-'}
                                            {' • '}
                                            Pulang {attendance_today?.clock_out_at ? new Date(attendance_today.clock_out_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '-'}
                                        </p>
                                    </div>
                                    <Link
                                        href={route('driver.attendance')}
                                        className="inline-flex items-center gap-1.5 rounded-lg bg-[#2563EB] px-3 py-2 text-xs font-semibold text-white transition hover:bg-[#1D4ED8]"
                                    >
                                        <CameraIcon className="h-4 w-4" /> Absensi Selfie
                                    </Link>
                                </div>
                            </div>

                            <div className="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-sm">
                                <div className="flex items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <p className="text-xs font-semibold uppercase tracking-wide text-[#94A3B8]">Tugas Aktif</p>
                                        {active_task ? (
                                            <>
                                                <p className="mt-1 truncate text-sm font-semibold text-[#0F172A]">{active_task.trip_number}</p>
                                                <p className="mt-0.5 truncate text-xs text-[#64748B]">{active_task.destination}</p>
                                                {active_task.bod_link && (
                                                    <a
                                                        href={active_task.bod_link}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-[#2563EB] underline"
                                                    >
                                                        <LinkIcon className="h-3.5 w-3.5" /> Buka Tautan Referensi
                                                    </a>
                                                )}
                                            </>
                                        ) : (
                                            <p className="mt-1 text-sm text-[#64748B]">Tidak ada tugas aktif</p>
                                        )}
                                    </div>
                                    <div className="flex flex-col items-end gap-2">
                                        {active_task?.status ? (
                                            <StatusBadge status={active_task.status} pulse={active_task.status === 'ON_TRIP'} />
                                        ) : null}
                                        {active_task?.can_complete && (
                                            <button
                                                onClick={() => setShowComplete(true)}
                                                className="inline-flex items-center gap-1.5 rounded-lg bg-[#16A34A] px-3 py-2 text-xs font-semibold text-white transition hover:bg-[#15803D]"
                                            >
                                                <CheckCircleIcon className="h-4 w-4" /> Selesaikan Perjalanan
                                            </button>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    <Panel
                        title="Tugas yang Ditugaskan"
                        description={`${assignments?.length ?? 0} tugas`}
                        icon={<ClipboardDocumentListIcon className="h-4 w-4" />}
                    >
                        {assignments && assignments.length > 0 ? (
                            <div className="space-y-3">
                                {assignments.map((a) => (
                                    <div key={a.id} className="flex flex-col gap-2 rounded-xl border border-[#E2E8F0] bg-white p-4 transition hover:border-[#BFDBFE] hover:shadow-[0_2px_8px_-4px_rgba(37,99,235,0.2)] sm:flex-row sm:items-center sm:justify-between">
                                        <div className="min-w-0">
                                            <p className="truncate font-semibold text-[#0F172A]">{a.trip_number}</p>
                                            <p className="mt-0.5 truncate text-sm text-[#64748B]">{a.destination} • {a.departure_time}</p>
                                        </div>
                                        <StatusBadge status={a.status} pulse={a.status === 'ON_TRIP'} />
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="flex flex-col items-center justify-center py-12 text-center">
                                <span className="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-[#F1F5F9]">
                                    <WrenchIcon className="h-7 w-7 text-[#94A3B8]" />
                                </span>
                                <p className="text-sm font-medium text-[#64748B]">Tidak ada tugas yang ditugaskan</p>
                                <p className="mt-1 text-xs text-[#94A3B8]">Tugas baru akan muncul di sini saat dispatcher menugaskan Anda.</p>
                            </div>
                        )}
                    </Panel>
                </div>
            </div>

            {showComplete && active_task && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                    <div className="w-full max-w-md rounded-xl bg-white p-5 shadow-xl">
                        <h3 className="text-lg font-semibold text-[#0F172A]">Selesaikan Perjalanan</h3>
                        <p className="mt-1 rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-800">
                            Pengajuan selesai dikirim ke Admin/SuperAdmin. Status perjalanan menjadi COMPLETED setelah
                            disetujui.
                        </p>
                        <label className="mt-3 block text-sm font-medium text-gray-700">Catatan Penyelesaian (opsional)</label>
                        <textarea
                            rows={3}
                            value={completeNotes}
                            onChange={(e) => setCompleteNotes(e.target.value)}
                            className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Contoh: sudah kembali ke kantor"
                        />
                        <div className="mt-4 flex justify-end gap-2">
                            <button
                                onClick={() => setShowComplete(false)}
                                className="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Batal
                            </button>
                            <button
                                onClick={submitCompletion}
                                className="rounded-md bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700"
                            >
                                Ajukan Selesai
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
