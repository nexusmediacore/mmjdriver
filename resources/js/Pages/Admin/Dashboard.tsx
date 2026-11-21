import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/UI/PageHeader';
import StatCard from '@/Components/UI/StatCard';
import Panel from '@/Components/UI/Panel';
import { UserGroupIcon, TruckIcon, BuildingOfficeIcon, ChartBarIcon, UsersIcon } from '@heroicons/react/24/outline';
import ChartCard from '@/Components/ChartCard';

interface DashboardProps {
    stats?: {
        total_users: number;
        total_drivers: number;
        total_vehicles: number;
        total_departments: number;
        total_trips: number;
        active_trips: number;
        completed_trips: number;
        trips_this_month: number;
    };
    trip_scale?: Array<{ department: string; count: number }>;
    driver_duration?: Array<{ driver: string; avg_duration_hours: number; total_trips: number }>;
}

export default function AdminDashboard({ stats, trip_scale, driver_duration }: DashboardProps) {
    const s = stats ?? {
        total_users: 0,
        total_drivers: 0,
        total_vehicles: 0,
        total_departments: 0,
        total_trips: 0,
        active_trips: 0,
        completed_trips: 0,
        trips_this_month: 0,
    };

    const today = new Date().toLocaleDateString('id-ID', { dateStyle: 'full' });

    return (
        <AuthenticatedLayout header="Admin Dashboard">
            <Head title="Admin Dashboard" />

            <div className="py-8 sm:py-10">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <PageHeader
                        eyebrow="Overview"
                        title="System Overview"
                        description="Ringkasan data pengguna, armada, dan aktivitas perjalanan dalam satu tampilan."
                        meta={
                            <div className="flex flex-wrap items-center gap-2">
                                <span className="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-medium text-white/80">
                                    <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                                    {today}
                                </span>
                                <span className="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-medium text-white/80">
                                    <span className="h-1.5 w-1.5 rounded-full bg-[#4ADE80]" />
                                    Sistem Aktif
                                </span>
                            </div>
                        }
                    />

                    <div className="mb-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        <StatCard tone="blue" className="mmx-d1" title="Total Users" value={s.total_users} href={route('admin.users')} linkLabel="Kelola Pengguna" icon={<UserGroupIcon className="h-5 w-5" />} />
                        <StatCard tone="green" className="mmx-d2" title="Total Drivers" value={s.total_drivers} href={route('admin.drivers')} linkLabel="Kelola Driver" icon={<TruckIcon className="h-5 w-5" />} />
                        <StatCard tone="purple" className="mmx-d3" title="Total Vehicles" value={s.total_vehicles} href={route('admin.vehicles')} linkLabel="Kelola Kendaraan" icon={<UsersIcon className="h-5 w-5" />} />
                        <StatCard tone="amber" className="mmx-d4" title="Departments" value={s.total_departments} href={route('admin.departments')} linkLabel="Kelola Departemen" icon={<BuildingOfficeIcon className="h-5 w-5" />} />
                    </div>

                    <div className="mb-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        <StatCard tone="slate" className="mmx-d2" title="Total Trips" value={s.total_trips} href={route('trips.index')} linkLabel="Daftar Perjalanan" icon={<ChartBarIcon className="h-5 w-5" />} />
                        <StatCard tone="amber" className="mmx-d3" title="Active Trips" value={s.active_trips} href={route('dispatcher.active-trips')} linkLabel="Pantau Aktif" hint="Sedang berlangsung" icon={<ChartBarIcon className="h-5 w-5" />} />
                        <StatCard tone="green" className="mmx-d4" title="Completed" value={s.completed_trips} href={route('reports.index')} linkLabel="Lihat Laporan" icon={<ChartBarIcon className="h-5 w-5" />} />
                        <StatCard tone="sky" className="mmx-d5" title="Trips Bulan Ini" value={s.trips_this_month} href={route('reports.index')} linkLabel="Lihat Laporan" icon={<ChartBarIcon className="h-5 w-5" />} />
                    </div>

                    <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
                        <Panel
                            title="Grafik Skala Perjalanan"
                            description="Jumlah perjalanan berdasarkan departemen"
                            icon={<ChartBarIcon className="h-4 w-4" />}
                        >
                            <ChartCard
                                title=""
                                type="bar"
                                data={{
                                    labels: trip_scale?.map((t) => t.department) ?? [],
                                    values: trip_scale?.map((t) => t.count) ?? [],
                                }}
                                yAxisLabel="Jumlah Perjalanan"
                            />
                        </Panel>
                        <Panel
                            title="Kinerja Lama Perjalanan"
                            description="Rata-rata durasi perjalanan per driver"
                            icon={<ChartBarIcon className="h-4 w-4" />}
                        >
                            <ChartCard
                                title=""
                                type="line"
                                data={{
                                    labels: driver_duration?.map((d) => d.driver) ?? [],
                                    values: driver_duration?.map((d) => d.avg_duration_hours) ?? [],
                                }}
                                yAxisLabel="Rata-rata Jam"
                            />
                        </Panel>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
