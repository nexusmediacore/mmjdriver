import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/UI/PageHeader';
import StatCard from '@/Components/UI/StatCard';
import Panel from '@/Components/UI/Panel';
import { ClockIcon, CheckCircleIcon, XCircleIcon, CalendarIcon, ShieldCheckIcon } from '@heroicons/react/24/outline';
import StatusBadge from '@/Components/UI/StatusBadge';

interface ApproverDashboardProps {
    stats?: {
        pending_approval: number;
        approved_today: number;
        rejected_today: number;
        total_this_month: number;
    };
    pending_requests?: Array<{ id: number; trip_number: string; destination: string; priority: string; requestor_name: string; created_at: string }>;
}

export default function ApproverDashboard({ stats, pending_requests }: ApproverDashboardProps) {
    const s = stats ?? {
        pending_approval: 0,
        approved_today: 0,
        rejected_today: 0,
        total_this_month: 0,
    };

    return (
        <AuthenticatedLayout header="Approver Dashboard">
            <Head title="Approver Dashboard" />

            <div className="py-8 sm:py-10">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <PageHeader
                        eyebrow="Persetujuan"
                        title="Approver Dashboard"
                        description="Tinjau dan setujui permintaan perjalanan dari departemen Anda."
                        actions={
                            <span className="inline-flex items-center gap-2 rounded-full border border-[#FDE68A] bg-[#FFFBEB] px-3 py-1.5 text-xs font-semibold text-[#B45309]">
                                <ClockIcon className="h-4 w-4" />
                                {s.pending_approval} Menunggu
                            </span>
                        }
                        meta={
                            <span className="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-medium text-white/80">
                                <ShieldCheckIcon className="h-3.5 w-3.5" />
                                Wewenang persetujuan aktif
                            </span>
                        }
                    />

                    <div className="mb-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        <StatCard tone="amber" className="mmx-d1" title="Pending Approval" value={s.pending_approval} href={route('approver.pending-approvals')} linkLabel="Tinjau Sekarang" hint="Perlu ditinjau" icon={<ClockIcon className="h-5 w-5" />} />
                        <StatCard tone="green" className="mmx-d2" title="Approved Today" value={s.approved_today} href={route('approver.trip-history')} linkLabel="Lihat Riwayat" icon={<CheckCircleIcon className="h-5 w-5" />} />
                        <StatCard tone="red" className="mmx-d3" title="Rejected Today" value={s.rejected_today} href={route('approver.trip-history')} linkLabel="Lihat Riwayat" icon={<XCircleIcon className="h-5 w-5" />} />
                        <StatCard tone="blue" className="mmx-d4" title="Total This Month" value={s.total_this_month} href={route('reports.index')} linkLabel="Lihat Laporan" icon={<CalendarIcon className="h-5 w-5" />} />
                    </div>

                    <Panel
                        title="Permintaan Menunggu Persetujuan"
                        description={`${pending_requests?.length ?? 0} permintaan`}
                        icon={<ClockIcon className="h-4 w-4" />}
                    >
                        {pending_requests && pending_requests.length > 0 ? (
                            <div className="space-y-3">
                                {pending_requests.map((req) => (
                                    <div key={req.id} className="flex flex-col gap-2 rounded-xl border border-[#E2E8F0] bg-white p-4 transition hover:border-[#BFDBFE] hover:shadow-[0_2px_8px_-4px_rgba(37,99,235,0.2)] sm:flex-row sm:items-center sm:justify-between">
                                        <div className="min-w-0">
                                            <p className="truncate font-semibold text-[#0F172A]">{req.trip_number}</p>
                                            <p className="mt-0.5 truncate text-sm text-[#64748B]">{req.destination} • {req.requestor_name}</p>
                                        </div>
                                        <StatusBadge status={req.priority} />
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="flex flex-col items-center justify-center py-12 text-center">
                                <span className="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-[#F0FDF4]">
                                    <CheckCircleIcon className="h-7 w-7 text-[#16A34A]" />
                                </span>
                                <p className="text-sm font-medium text-[#64748B]">Semua permintaan sudah diproses</p>
                                <p className="mt-1 text-xs text-[#94A3B8]">Tidak ada permintaan yang menunggu persetujuan.</p>
                            </div>
                        )}
                    </Panel>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
