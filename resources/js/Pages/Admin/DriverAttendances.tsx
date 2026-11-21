import { router } from '@inertiajs/react';
import { useState } from 'react';
import SectionPage from '@/Components/SectionPage';
import DataTable from '@/Components/DataTable';
import StatCard from '@/Components/UI/StatCard';
import {
    CameraIcon,
    UserGroupIcon,
    CheckCircleIcon,
    ArrowRightOnRectangleIcon,
    XCircleIcon,
    ClockIcon,
    TruckIcon,
    MapPinIcon,
    ExclamationCircleIcon,
} from '@heroicons/react/24/outline';

interface Attendance {
    id: number;
    driver_id: number;
    driver_name: string | null;
    driver_status?: string | null;
    date: string;
    clock_in_at: string | null;
    clock_out_at: string | null;
    clock_in_photo_timestamp?: string | null;
    clock_out_photo_timestamp?: string | null;
    has_clock_in_photo: boolean;
    has_clock_out_photo: boolean;
    clock_in_lat: number | null;
    clock_in_lng: number | null;
    clock_in_distance_meters?: number | null;
    is_late?: boolean;
    lateness_minutes?: number;
    clock_out_lat: number | null;
    clock_out_lng: number | null;
    notes: string | null;
    work_duration_minutes: number | null;
}

interface Summary {
    total_drivers: number;
    present: number;
    on_time?: number;
    late?: number;
    clocked_out: number;
    on_trip?: number;
    absent: number;
}

interface Props {
    date: string;
    attendances: Attendance[];
    summary: Summary;
    office?: {
        latitude: number;
        longitude: number;
        max_radius_meters: number;
        cutoff_time: string;
    };
}

const time = (value: string | null) => (value ? new Date(value).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '-');

const duration = (minutes: number | null) => {
    if (minutes === null) return '-';
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return `${h}j ${m}m`;
};

function PhotoLink({ attendance, type }: { attendance: Attendance; type: 'in' | 'out' }) {
    const has = type === 'in' ? attendance.has_clock_in_photo : attendance.has_clock_out_photo;
    const ts = type === 'in' ? attendance.clock_in_photo_timestamp : attendance.clock_out_photo_timestamp;
    if (!has) return <span className="text-xs text-[#94A3B8]">-</span>;

    const base =
        typeof route === 'function' && route().has?.('admin.driver-attendances.photo')
            ? route('admin.driver-attendances.photo', attendance.id)
            : `/admin/driver-attendances/${attendance.id}/photo`;

    const url = `${base}?type=${type}`;

    return (
        <div className="flex flex-col items-start gap-1">
            <a
                href={url}
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-1 rounded-lg border border-[#E2E8F0] bg-white px-2.5 py-1 text-xs font-semibold text-[#2563EB] transition hover:border-[#BFDBFE] hover:bg-[#EFF6FF]"
            >
                <CameraIcon className="h-3.5 w-3.5" />
                Lihat Foto
            </a>
            {ts && (
                <span className="text-[10px] text-[#64748B]" title={`Waktu foto diambil: ${ts}`}>
                    TS: {time(ts)}
                </span>
            )}
        </div>
    );
}

export default function AdminDriverAttendances({ date, attendances, summary, office }: Props) {
    const [selectedDate, setSelectedDate] = useState(date);

    const applyDate = (value: string) => {
        setSelectedDate(value);
        router.get(route('admin.driver-attendances'), { date: value }, { preserveState: true, preserveScroll: true });
    };

    const columns = [
        {
            key: 'driver_name',
            label: 'Driver',
            render: (row: Attendance) => (
                <div>
                    <div className="font-medium text-[#0F172A]">{row.driver_name ?? '-'}</div>
                    {row.driver_status && (
                        <div className="text-[11px] text-[#64748B]">
                            Status: <span className="font-semibold">{row.driver_status}</span>
                        </div>
                    )}
                </div>
            ),
        },
        { key: 'date', label: 'Tanggal' },
        {
            key: 'clock_in_at',
            label: 'Masuk & Ketepatan',
            render: (row: Attendance) => {
                if (!row.clock_in_at) return <span className="text-xs text-[#94A3B8]">-</span>;
                return (
                    <div className="flex flex-col items-start gap-1">
                        <span className="font-medium text-[#0F172A]">{time(row.clock_in_at)}</span>
                        {row.is_late ? (
                            <span className="inline-flex items-center gap-1 rounded-full bg-[#FEE2E2] px-2 py-0.5 text-[10px] font-semibold text-[#DC2626]">
                                <ExclamationCircleIcon className="h-3 w-3" />
                                Terlambat {row.lateness_minutes ? `(+${row.lateness_minutes} m)` : ''}
                            </span>
                        ) : (
                            <span className="inline-flex items-center gap-1 rounded-full bg-[#DCFCE7] px-2 py-0.5 text-[10px] font-semibold text-[#16A34A]">
                                <CheckCircleIcon className="h-3 w-3" />
                                Tepat Waktu
                            </span>
                        )}
                    </div>
                );
            },
        },
        {
            key: 'clock_in_photo',
            label: 'Foto Masuk',
            render: (row: Attendance) => <PhotoLink attendance={row} type="in" />,
        },
        { key: 'clock_out_at', label: 'Pulang', render: (row: Attendance) => time(row.clock_out_at) },
        {
            key: 'clock_out_photo',
            label: 'Foto Pulang',
            render: (row: Attendance) => <PhotoLink attendance={row} type="out" />,
        },
        { key: 'duration', label: 'Durasi', render: (row: Attendance) => duration(row.work_duration_minutes) },
        {
            key: 'location',
            label: 'Lokasi & Jarak',
            render: (row: Attendance) =>
                row.clock_in_lat && row.clock_in_lng ? (
                    <div className="flex flex-col items-start gap-0.5">
                        <a
                            className="inline-flex items-center gap-1 text-xs font-semibold text-[#2563EB] hover:underline"
                            href={`https://www.google.com/maps?q=${row.clock_in_lat},${row.clock_in_lng}`}
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <MapPinIcon className="h-3.5 w-3.5" />
                            Peta
                        </a>
                        {row.clock_in_distance_meters !== null && row.clock_in_distance_meters !== undefined && (
                            <span
                                className={`text-[10px] font-medium ${
                                    row.clock_in_distance_meters <= 100 ? 'text-[#16A34A]' : 'text-[#DC2626]'
                                }`}
                                title="Jarak saat absen masuk dari kantor PT. Marita Makmur Jaya"
                            >
                                {row.clock_in_distance_meters.toFixed(1)}m dari kantor
                            </span>
                        )}
                    </div>
                ) : (
                    <span className="text-xs text-[#94A3B8]">-</span>
                ),
        },
    ];

    return (
        <SectionPage
            title="Rekap Absensi Driver"
            eyebrow="Monitoring"
            description="Pantau kehadiran driver beserta foto selfie absensi masuk dan pulang."
            actions={
                <div className="flex items-center gap-2">
                    <input
                        type="date"
                        value={selectedDate}
                        onChange={(e) => applyDate(e.target.value)}
                        className="rounded-lg border border-[#E2E8F0] px-3 py-2 text-sm focus:border-[#2563EB] focus:outline-none focus:ring-4 focus:ring-[#2563EB]/10"
                    />
                </div>
            }
        >
            {/* Kebijakan Kantor Info Box */}
            <div className="mb-6 rounded-xl border border-[#BFDBFE] bg-[#EFF6FF] p-4 text-xs text-[#1E40AF]">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div className="flex items-center gap-2 font-medium">
                        <ClockIcon className="h-4 w-4 text-[#2563EB]" />
                        <span>
                            Batas Masuk Tepat Waktu: <strong>{office?.cutoff_time ?? '07:30 WIB'}</strong> (lewat jam ini tercatat terlambat)
                        </span>
                    </div>
                    <div className="flex items-center gap-2 font-medium">
                        <MapPinIcon className="h-4 w-4 text-[#2563EB]" />
                        <span>
                            Radius Absensi: <strong>Maks. {office?.max_radius_meters ?? 100} meter</strong> dari kantor PT. Marita Makmur Jaya
                        </span>
                    </div>
                </div>
                <div className="mt-2 text-[11px] text-[#3B82F6]">
                    Catatan Dispensasi: Driver yang belum kembali dari perjalanan (status <em>ON_TRIP</em> / <em>RETURNING</em>) diizinkan tidak absen selama sedang bertugas di luar pangkalan.
                </div>
            </div>

            <div className="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                <StatCard tone="blue" title="Total Driver" value={summary.total_drivers} icon={<UserGroupIcon className="h-5 w-5" />} />
                <StatCard tone="green" title="Hadir Masuk" value={summary.present} icon={<CheckCircleIcon className="h-5 w-5" />} />
                <StatCard tone="green" title="Tepat Waktu" value={summary.on_time ?? 0} icon={<ClockIcon className="h-5 w-5" />} />
                <StatCard tone="amber" title="Terlambat" value={summary.late ?? 0} icon={<ExclamationCircleIcon className="h-5 w-5" />} />
                <StatCard tone="purple" title="Sedang Tugas" value={summary.on_trip ?? 0} icon={<TruckIcon className="h-5 w-5" />} />
                <StatCard tone="slate" title="Belum Absen" value={summary.absent} icon={<XCircleIcon className="h-5 w-5" />} />
            </div>

            <DataTable
                data={attendances}
                columns={columns}
                searchPlaceholder="Cari driver... (tekan /)"
                emptyMessage="Belum ada absensi pada tanggal ini"
                emptyDescription="Data absensi muncul setelah driver melakukan check-in via dashboard atau aplikasi mobile."
            />
        </SectionPage>
    );
}
