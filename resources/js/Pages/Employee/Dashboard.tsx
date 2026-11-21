import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/UI/PageHeader';
import StatCard from '@/Components/UI/StatCard';
import Panel from '@/Components/UI/Panel';
import { CalendarIcon, ClockIcon, CheckCircleIcon, PlusIcon, DocumentTextIcon } from '@heroicons/react/24/outline';
import StatusBadge from '@/Components/UI/StatusBadge';

interface EmployeeDashboardProps {
    stats?: {
        my_trips: number;
        pending: number;
        approved: number;
        completed: number;
    };
    my_requests?: Array<{ id: number; trip_number: string; destination: string; status: string; trip_date: string }>;
}

export default function EmployeeDashboard({ stats, my_requests }: EmployeeDashboardProps) {
    const s = stats ?? { my_trips: 0, pending: 0, approved: 0, completed: 0 };

    return (
        <AuthenticatedLayout header="Employee Dashboard">
            <Head title="Employee Dashboard" />

            <div className="py-8 sm:py-10">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <PageHeader
                        eyebrow="Perjalanan Saya"
                        title="Employee Dashboard"
                        description="Ajukan perjalanan dinas baru dan pantau status permintaan Anda."
                        actions={
                            <Link
                                href={route('trips.create')}
                                className="mmj-btn mmj-btn-primary"
                            >
                                <PlusIcon className="h-4 w-4" />
                                Ajukan Perjalanan
                            </Link>
                        }
                    />

                    <div className="mb-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        <StatCard tone="blue" className="mmx-d1" title="Total Trips" value={s.my_trips} href={route('trips.index')} linkLabel="Daftar Perjalanan" icon={<DocumentTextIcon className="h-5 w-5" />} />
                        <StatCard tone="amber" className="mmx-d2" title="Pending" value={s.pending} href={route('trips.index')} linkLabel="Daftar Perjalanan" icon={<ClockIcon className="h-5 w-5" />} />
                        <StatCard tone="green" className="mmx-d3" title="Approved" value={s.approved} href={route('trips.index')} linkLabel="Daftar Perjalanan" icon={<CheckCircleIcon className="h-5 w-5" />} />
                        <StatCard tone="sky" className="mmx-d4" title="Completed" value={s.completed} href={route('trips.index')} linkLabel="Daftar Perjalanan" icon={<CalendarIcon className="h-5 w-5" />} />
                    </div>

                    <Panel
                        title="Permintaan Saya"
                        description={`${my_requests?.length ?? 0} permintaan terbaru`}
                        icon={<DocumentTextIcon className="h-4 w-4" />}
                    >
                        {my_requests && my_requests.length > 0 ? (
                            <div className="space-y-3">
                                {my_requests.map((req) => (
                                    <div key={req.id} className="flex flex-col gap-2 rounded-xl border border-[#E2E8F0] bg-white p-4 transition hover:border-[#BFDBFE] hover:shadow-[0_2px_8px_-4px_rgba(37,99,235,0.2)] sm:flex-row sm:items-center sm:justify-between">
                                        <div className="min-w-0">
                                            <p className="truncate font-semibold text-[#0F172A]">{req.trip_number}</p>
                                            <p className="mt-0.5 truncate text-sm text-[#64748B]">{req.destination} • {req.trip_date}</p>
                                        </div>
                                        <StatusBadge status={req.status} />
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="flex flex-col items-center justify-center py-12 text-center">
                                <span className="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-[#F1F5F9]">
                                    <DocumentTextIcon className="h-7 w-7 text-[#94A3B8]" />
                                </span>
                                <p className="text-sm font-medium text-[#64748B]">Belum ada permintaan perjalanan</p>
                                <p className="mt-1 text-xs text-[#94A3B8]">Klik "Ajukan Perjalanan" untuk membuat permintaan pertama Anda.</p>
                            </div>
                        )}
                    </Panel>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
