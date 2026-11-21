import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/UI/PageHeader';
import StatCard from '@/Components/UI/StatCard';
import Panel from '@/Components/UI/Panel';
import { ClockIcon, TruckIcon, ExclamationTriangleIcon, CalendarIcon, UserGroupIcon } from '@heroicons/react/24/outline';
import StatusBadge from '@/Components/UI/StatusBadge';

interface DispatcherDashboardProps {
    stats?: {
        total_requests_today: number;
        waiting_approval: number;
        waiting_driver: number;
        driver_standby: number;
        driver_on_trip: number;
        vehicle_available: number;
        active_trips: number;
        completed_trips: number;
        urgent_requests: number;
    };
    drivers?: Array<{ id: number; name: string; status: string }>;
    recent_requests?: Array<{ id: number; trip_number: string; destination: string; status: string; priority: string; created_at: string }>;
}

export default function DispatcherDashboard({ stats, drivers, recent_requests }: DispatcherDashboardProps) {
    const s = stats ?? {
        total_requests_today: 0,
        waiting_approval: 0,
        waiting_driver: 0,
        driver_standby: 0,
        driver_on_trip: 0,
        vehicle_available: 0,
        active_trips: 0,
        completed_trips: 0,
        urgent_requests: 0,
    };

    return (
        <AuthenticatedLayout header="Dispatcher Dashboard">
            <Head title="Dispatcher Dashboard" />

            <div className="py-8 sm:py-10">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <PageHeader
                        eyebrow="Dispatch Control"
                        title="Dispatcher Dashboard"
                        description="Pantau ketersediaan driver, kendaraan, dan antrian permintaan perjalanan secara real-time."
                        actions={
                            <span className="inline-flex items-center gap-2 rounded-full border border-[#FECACA] bg-[#FEF2F2] px-3 py-1.5 text-xs font-semibold text-[#DC2626]">
                                <ExclamationTriangleIcon className="h-4 w-4" />
                                {s.urgent_requests} Urgent
                            </span>
                        }
                        meta={
                            <span className="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-medium text-white/80">
                                <span className="h-1.5 w-1.5 animate-pulse rounded-full bg-[#4ADE80]" />
                                Update real-time
                            </span>
                        }
                    />

                    <div className="mb-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        <StatCard tone="blue" className="mmx-d1" title="Request Hari Ini" value={s.total_requests_today} href={route('trips.index')} linkLabel="Buka Trip Queue" icon={<ClockIcon className="h-5 w-5" />} />
                        <StatCard tone="amber" className="mmx-d2" title="Waiting Driver" value={s.waiting_driver} href={route('trips.index')} linkLabel="Buka Trip Queue" icon={<ClockIcon className="h-5 w-5" />} />
                        <StatCard tone="green" className="mmx-d3" title="Driver Standby" value={s.driver_standby} href={route('dispatcher.driver-availability')} linkLabel="Lihat Ketersediaan" hint="Siap bertugas" icon={<TruckIcon className="h-5 w-5" />} />
                        <StatCard tone="purple" className="mmx-d4" title="Vehicle Available" value={s.vehicle_available} href={route('dispatcher.vehicle-availability')} linkLabel="Lihat Ketersediaan" icon={<UserGroupIcon className="h-5 w-5" />} />
                    </div>

                    <div className="mb-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        <StatCard tone="amber" className="mmx-d2" title="Driver On Trip" value={s.driver_on_trip} href={route('dispatcher.active-trips')} linkLabel="Pantau Perjalanan" icon={<TruckIcon className="h-5 w-5" />} />
                        <StatCard tone="sky" className="mmx-d3" title="Active Trips" value={s.active_trips} href={route('dispatcher.active-trips')} linkLabel="Pantau Perjalanan" icon={<CalendarIcon className="h-5 w-5" />} />
                        <StatCard tone="green" className="mmx-d4" title="Completed" value={s.completed_trips} href={route('reports.index')} linkLabel="Lihat Laporan" icon={<CalendarIcon className="h-5 w-5" />} />
                        <StatCard tone="red" className="mmx-d5" title="Urgent Requests" value={s.urgent_requests} href={route('trips.index')} linkLabel="Buka Trip Queue" icon={<ExclamationTriangleIcon className="h-5 w-5" />} />
                    </div>

                    <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                        <Panel
                            className="lg:col-span-2"
                            title="Driver Availability"
                            description={`${drivers?.length ?? 0} driver terdaftar`}
                            icon={<TruckIcon className="h-4 w-4" />}
                        >
                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3">
                                {drivers && drivers.length > 0 ? (
                                    drivers.map((driver) => (
                                        <div key={driver.id} className="flex items-center gap-3 rounded-lg border border-[#E2E8F0] bg-[#F8FAFC]/60 p-3 transition hover:border-[#BFDBFE] hover:bg-white">
                                            <StatusBadge status={driver.status} variant="driver" pulse={driver.status === 'STANDBY' || driver.status === 'ON_TRIP'} />
                                            <span className="truncate text-sm font-medium text-[#0F172A]">{driver.name}</span>
                                        </div>
                                    ))
                                ) : (
                                    <div className="col-span-full flex flex-col items-center justify-center py-10 text-center">
                                        <span className="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-[#F1F5F9]">
                                            <TruckIcon className="h-6 w-6 text-[#94A3B8]" />
                                        </span>
                                        <p className="text-sm text-[#94A3B8]">Tidak ada driver</p>
                                    </div>
                                )}
                            </div>
                        </Panel>

                        <Panel
                            title="Recent Requests"
                            description="5 permintaan terbaru"
                            icon={<ClockIcon className="h-4 w-4" />}
                        >
                            <div className="space-y-3">
                                {recent_requests && recent_requests.length > 0 ? (
                                    recent_requests.slice(0, 5).map((req) => (
                                        <div key={req.id} className="rounded-lg border border-[#E2E8F0] p-3 transition hover:border-[#BFDBFE] hover:bg-[#F8FAFC]/60">
                                            <div className="flex items-center justify-between gap-2">
                                                <p className="truncate text-sm font-semibold text-[#0F172A]">{req.trip_number}</p>
                                                <StatusBadge status={req.priority} />
                                            </div>
                                            <p className="mt-0.5 truncate text-xs text-[#64748B]">{req.destination}</p>
                                        </div>
                                    ))
                                ) : (
                                    <div className="flex flex-col items-center justify-center py-10 text-center">
                                        <span className="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-[#F1F5F9]">
                                            <ClockIcon className="h-6 w-6 text-[#94A3B8]" />
                                        </span>
                                        <p className="text-sm text-[#94A3B8]">Tidak ada request</p>
                                    </div>
                                )}
                            </div>
                        </Panel>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
