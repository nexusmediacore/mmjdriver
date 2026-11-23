import { Head, Link, router, usePage } from '@inertiajs/react';
import { PlusIcon, ClipboardDocumentListIcon, ArrowsUpDownIcon, TrashIcon, PencilSquareIcon } from '@heroicons/react/24/outline';
import { useState } from 'react';
import SectionPage from '@/Components/SectionPage';
import StatusBadge from '@/Components/UI/StatusBadge';
import Panel from '@/Components/UI/Panel';
import SearchInput from '@/Components/UI/SearchInput';
import RequestTypeBadge from '@/Components/UI/RequestTypeBadge';
import { useTableSearch } from '@/Hooks/useTableSearch';

interface Trip {
    id: number;
    trip_number: string;
    destination: string;
    purpose: string;
    departure_time: string;
    status: string;
    priority: string;
    request_type: string;
    requestor_id: number;
    requestor: { id: number; name: string } | null;
}

interface PaginationData {
    data: Trip[];
    links: string;
    current_page: number;
    per_page: number;
    total: number;
}

interface Props {
    trips: PaginationData;
    sort_by?: string | null;
    sort_dir?: string | null;
}

const priorityStyles: Record<string, string> = {
    VERY_URGENT: 'bg-[#FEF2F2] text-[#DC2626]',
    URGENT: 'bg-[#FFF7ED] text-[#C2410C]',
    NORMAL: 'bg-[#F1F5F9] text-[#475569]',
};

const sortOptions = [
    { value: 'created_at', label: 'Terbaru dibuat' },
    { value: 'trip_number', label: 'No. Trip' },
    { value: 'departure_time', label: 'Tanggal Berangkat' },
    { value: 'destination', label: 'Tujuan' },
    { value: 'status', label: 'Status' },
    { value: 'priority', label: 'Prioritas' },
];

export default function TripsIndex({ trips, sort_by = 'created_at', sort_dir = 'desc' }: Props) {
    const user = usePage().props.auth.user as { id: number; role?: { name: string } | null } | null;
    const isSuperAdmin = user?.role?.name === 'SUPERADMIN';
    const isAdmin = ['SUPERADMIN', 'ADMIN'].includes(user?.role?.name ?? '');
    const canDelete = (trip: Trip) =>
        isAdmin || (
            ['EMPLOYEE', 'SUPERADMIN'].includes(user?.role?.name ?? '') &&
            trip.requestor_id === user?.id &&
            trip.status === 'DRAFT'
        );
    // Edit hanya tersedia selama trip masih DRAFT, oleh pemohon
    // (atau Admin/SuperAdmin).
    const canEdit = (trip: Trip) =>
        trip.status === 'DRAFT' &&
        (isAdmin || trip.requestor_id === user?.id);
    const [sortBy, setSortBy] = useState(sort_by ?? 'created_at');
    const [sortDir, setSortDir] = useState(sort_dir === 'asc' ? 'asc' : 'desc');

    const applySort = (nextBy: string, nextDir: string) => {
        router.get(route('trips.index'), { sort_by: nextBy, sort_dir: nextDir }, { preserveState: true, replace: true });
    };

    const { filtered, query, setQuery } = useTableSearch<Trip>(trips.data, [
        'trip_number',
        'destination',
        'purpose',
        'status',
        'priority',
        'request_type',
        'requestor.name',
    ]);

    return (
        <SectionPage
            title="Trip Requests"
            eyebrow="Perjalanan"
            description="Daftar seluruh permintaan perjalanan dinas beserta status dan prioritasnya."
            actions={
                <Link href={route('trips.create')} className="mmj-btn mmj-btn-primary">
                    <PlusIcon className="h-4 w-4" />
                    Buat Permintaan
                </Link>
            }
            meta={
                <span className="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-medium text-white/80">
                    <ClipboardDocumentListIcon className="h-3.5 w-3.5" />
                    {trips.total} permintaan
                </span>
            }
        >
            <Panel
                title="Daftar Permintaan Perjalanan"
                description={`Menampilkan ${filtered.length} dari ${trips.data.length} data`}
                icon={<ClipboardDocumentListIcon className="h-4 w-4" />}
            >
                <div className="mb-4 flex flex-wrap items-start gap-3">
                    <SearchInput
                        value={query}
                        onChange={setQuery}
                        placeholder="Cari no. trip, jenis, tujuan, pemohon, status... (tekan /)"
                        className="w-full sm:max-w-lg"
                        resultInfo={query.trim() ? `Ditemukan ${filtered.length} dari ${trips.data.length} permintaan` : undefined}
                    />

                    {isSuperAdmin && (
                        <div className="flex items-center gap-2">
                            <label
                                htmlFor="trip-sort-by"
                                className="inline-flex items-center gap-1.5 text-xs font-medium text-[#64748B]"
                            >
                                <ArrowsUpDownIcon className="h-3.5 w-3.5" />
                                Urutkan
                            </label>
                            <select
                                id="trip-sort-by"
                                value={sortBy}
                                onChange={(e) => {
                                    setSortBy(e.target.value);
                                    applySort(e.target.value, sortDir);
                                }}
                                className="mmj-input py-2 text-sm"
                            >
                                {sortOptions.map((opt) => (
                                    <option key={opt.value} value={opt.value}>{opt.label}</option>
                                ))}
                            </select>
                            <button
                                type="button"
                                onClick={() => {
                                    const nextDir = sortDir === 'asc' ? 'desc' : 'asc';
                                    setSortDir(nextDir);
                                    applySort(sortBy, nextDir);
                                }}
                                title={sortDir === 'asc' ? 'Naik (A-Z / terlama)' : 'Turun (Z-A / terbaru)'}
                                className="inline-flex items-center gap-1 rounded-lg border border-[#E2E8F0] bg-white px-2.5 py-2 text-xs font-semibold text-[#475569] transition hover:border-[#BFDBFE] hover:text-[#2563EB]"
                            >
                                {sortDir === 'asc' ? 'Naik ↑' : 'Turun ↓'}
                            </button>
                        </div>
                    )}
                </div>

                <div className="overflow-x-auto">
                    <table className="mmj-table w-full">
                        <thead>
                            <tr>
                                <th className="text-start">No. Trip</th>
                                <th className="text-start">Jenis</th>
                                <th className="text-start">Tujuan</th>
                                <th className="text-start">Pemohon</th>
                                <th className="text-start">Tanggal</th>
                                <th className="text-start">Status</th>
                                <th className="text-start">Prioritas</th>
                                <th className="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {filtered.length > 0 ? (
                                filtered.map((trip, idx) => (
                                    <tr key={trip.id} className="mmj-row-in" style={{ animationDelay: `${Math.min(idx * 45, 400)}ms` }}>
                                        <td className="font-semibold text-[#0F172A]">{trip.trip_number}</td>
                                        <td><RequestTypeBadge type={trip.request_type} /></td>
                                        <td className="max-w-[220px] truncate text-[#334155]">{trip.destination}</td>
                                        <td className="text-[#64748B]">{trip.requestor?.name || '-'}</td>
                                        <td className="text-[#64748B]">{new Date(trip.departure_time.replace(' ', 'T')).toLocaleDateString('id-ID')}</td>
                                        <td><StatusBadge status={trip.status} /></td>
                                        <td>
                                            <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${priorityStyles[trip.priority] ?? priorityStyles.NORMAL}`}>
                                                {trip.priority}
                                            </span>
                                        </td>
                                        <td className="text-center">
                                            <div className="inline-flex items-center gap-2">
                                                <Link
                                                    href={route('trips.show', trip.id)}
                                                    className="inline-flex items-center gap-1 text-sm font-semibold text-[#2563EB] transition hover:text-[#1D4ED8]"
                                                >
                                                    Lihat
                                                    <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                                                </Link>
                                                {canEdit(trip) && (
                                                    <Link
                                                        href={route('trips.edit', trip.id)}
                                                        aria-label="Edit permintaan perjalanan"
                                                        className="inline-flex items-center justify-center rounded-lg border border-[#E2E8F0] bg-white p-1.5 text-[#64748B] transition hover:border-[#BFDBFE] hover:bg-[#EFF6FF] hover:text-[#2563EB]"
                                                    >
                                                        <PencilSquareIcon className="h-3.5 w-3.5" />
                                                    </Link>
                                                )}
                                                {canDelete(trip) && (
                                                    <button
                                                        type="button"
                                                        onClick={() => {
                                                            if (confirm(`Hapus permintaan perjalanan ${trip.trip_number}?`)) {
                                                                router.delete(route('trips.destroy', trip.id), { preserveState: false });
                                                            }
                                                        }}
                                                        aria-label="Hapus permintaan perjalanan"
                                                        className="inline-flex items-center justify-center rounded-lg border border-[#E2E8F0] bg-white p-1.5 text-[#64748B] transition hover:border-[#FECACA] hover:bg-[#FEF2F2] hover:text-[#DC2626]"
                                                    >
                                                        <TrashIcon className="h-3.5 w-3.5" />
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={8} className="px-6 py-12">
                                        <div className="mmj-page-in flex flex-col items-center justify-center text-center">
                                            <span className="mmj-float-icon relative mb-5">
                                                <span className="absolute -inset-3 rounded-full bg-[#DBEAFE] opacity-60 blur-xl" />
                                                <span className="relative flex h-16 w-16 items-center justify-center rounded-full border border-[#BFDBFE] bg-[#EFF6FF]">
                                                    <ClipboardDocumentListIcon className="h-7 w-7 text-[#2563EB]" />
                                                </span>
                                            </span>
                                            <h3 className="text-base font-semibold text-[#0F172A]">Belum ada permintaan perjalanan</h3>
                                            <p className="mt-1 text-sm text-[#64748B]">Buat permintaan perjalanan pertama Anda.</p>
                                        </div>
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </Panel>
        </SectionPage>
    );
}
