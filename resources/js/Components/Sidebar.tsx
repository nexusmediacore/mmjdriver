import { Link, usePage } from '@inertiajs/react';
import logoMmj from '../../images/logo-mmj.png';
import {
    ChartBarIcon,
    ClipboardDocumentListIcon,
    UserGroupIcon,
    BuildingOfficeIcon,
    TruckIcon,
    UserIcon,
    DocumentTextIcon,
    WrenchIcon,
    CalendarIcon,
    ClockIcon,
    ShieldCheckIcon,
    ChartPieIcon,
    CameraIcon,
} from '@heroicons/react/24/outline';

interface SidebarItem {
    label: string;
    href: string;
    icon: React.ReactNode;
    activeRoute?: string;
}

const sectionLabels: Record<string, string> = {
    SUPERADMIN: 'ADMINISTRASI',
    ADMIN: 'ADMINISTRASI',
    DISPATCHER: 'DISPATCH',
    APPROVER: 'PERSETUJUAN',
    EMPLOYEE: 'PERJALANAN',
    DRIVER: 'TUGAS SAYA',
};

const sectionIcons: Record<string, React.ReactNode> = {
    SUPERADMIN: <div className="h-1 w-1 rounded-full bg-[#2563EB]" />,
    ADMIN: <div className="h-1 w-1 rounded-full bg-[#2563EB]" />,
    DISPATCHER: <div className="h-1 w-1 rounded-full bg-[#F59E0B]" />,
    APPROVER: <div className="h-1 w-1 rounded-full bg-[#8B5CF6]" />,
    EMPLOYEE: <div className="h-1 w-1 rounded-full bg-[#16A34A]" />,
    DRIVER: <div className="h-1 w-1 rounded-full bg-[#0EA5E9]" />,
};

export default function Sidebar() {
    const user = usePage().props.auth.user as {
        name?: string;
        email?: string;
        role?: { name: string } | null;
    };

    const roleName = user?.role?.name ?? null;

    const getNavItems = (): SidebarItem[] => {
        const baseItems: SidebarItem[] = [
            {
                label: 'Dashboard',
                href: route('dashboard'),
                icon: <ChartBarIcon className="h-5 w-5" />,
                activeRoute: 'dashboard',
            },
        ];

        const roleSpecificItems: Record<string, SidebarItem[]> = {
            SUPERADMIN: [
                { label: 'User Management', href: route('admin.users'), icon: <UserGroupIcon className="h-5 w-5" />, activeRoute: 'admin.users' },
                { label: 'Drivers', href: route('admin.drivers'), icon: <TruckIcon className="h-5 w-5" />, activeRoute: 'admin.drivers*' },
                { label: 'Vehicles', href: route('admin.vehicles'), icon: <WrenchIcon className="h-5 w-5" />, activeRoute: 'admin.vehicles*' },
                { label: 'Departments', href: route('admin.departments'), icon: <BuildingOfficeIcon className="h-5 w-5" />, activeRoute: 'admin.departments' },
                { label: 'Trip Requests', href: route('trips.index'), icon: <ClipboardDocumentListIcon className="h-5 w-5" />, activeRoute: 'trips.*' },
                { label: 'Reports', href: route('reports.index'), icon: <ChartPieIcon className="h-5 w-5" />, activeRoute: 'reports.*' },
                { label: 'Berita Acara', href: route('admin.berita-acara'), icon: <DocumentTextIcon className="h-5 w-5" />, activeRoute: 'admin.berita-acara' },
                { label: 'Absensi Driver', href: route('admin.driver-attendances'), icon: <CameraIcon className="h-5 w-5" />, activeRoute: 'admin.driver-attendances' },
                { label: 'Audit Log', href: route('admin.audit-logs'), icon: <DocumentTextIcon className="h-5 w-5" />, activeRoute: 'admin.audit-logs' },
                { label: 'Settings', href: route('admin.settings'), icon: <ShieldCheckIcon className="h-5 w-5" />, activeRoute: 'admin.settings' },
            ],
            ADMIN: [
                { label: 'User Management', href: route('admin.users'), icon: <UserGroupIcon className="h-5 w-5" />, activeRoute: 'admin.users' },
                { label: 'Drivers', href: route('admin.drivers'), icon: <TruckIcon className="h-5 w-5" />, activeRoute: 'admin.drivers*' },
                { label: 'Vehicles', href: route('admin.vehicles'), icon: <WrenchIcon className="h-5 w-5" />, activeRoute: 'admin.vehicles*' },
                { label: 'Departments', href: route('admin.departments'), icon: <BuildingOfficeIcon className="h-5 w-5" />, activeRoute: 'admin.departments' },
                { label: 'Trip Requests', href: route('trips.index'), icon: <ClipboardDocumentListIcon className="h-5 w-5" />, activeRoute: 'trips.*' },
                { label: 'Reports', href: route('reports.index'), icon: <ChartPieIcon className="h-5 w-5" />, activeRoute: 'reports.*' },
                { label: 'Berita Acara', href: route('admin.berita-acara'), icon: <DocumentTextIcon className="h-5 w-5" />, activeRoute: 'admin.berita-acara' },
                { label: 'Absensi Driver', href: route('admin.driver-attendances'), icon: <CameraIcon className="h-5 w-5" />, activeRoute: 'admin.driver-attendances' },
            ],
            DISPATCHER: [
                { label: 'Driver Availability', href: route('dispatcher.driver-availability'), icon: <TruckIcon className="h-5 w-5" />, activeRoute: 'dispatcher.driver-availability' },
                { label: 'Vehicle Availability', href: route('dispatcher.vehicle-availability'), icon: <WrenchIcon className="h-5 w-5" />, activeRoute: 'dispatcher.vehicle-availability' },
                { label: 'Trip Queue', href: route('trips.index'), icon: <ClipboardDocumentListIcon className="h-5 w-5" />, activeRoute: 'trips.*' },
                { label: 'Active Trips', href: route('dispatcher.active-trips'), icon: <CalendarIcon className="h-5 w-5" />, activeRoute: 'dispatcher.active-trips' },
                { label: 'Absensi Driver', href: route('dispatcher.driver-attendances'), icon: <CameraIcon className="h-5 w-5" />, activeRoute: 'dispatcher.driver-attendances' },
                { label: 'Schedule', href: route('dispatcher.schedule'), icon: <ClockIcon className="h-5 w-5" />, activeRoute: 'dispatcher.schedule' },
            ],
            APPROVER: [
                { label: 'Pending Approvals', href: route('approver.pending-approvals'), icon: <ClockIcon className="h-5 w-5" />, activeRoute: 'approver.pending-approvals' },
                { label: 'Trip History', href: route('approver.trip-history'), icon: <ClipboardDocumentListIcon className="h-5 w-5" />, activeRoute: 'approver.trip-history' },
                { label: 'Reports', href: route('reports.index'), icon: <ChartPieIcon className="h-5 w-5" />, activeRoute: 'reports.*' },
            ],
            EMPLOYEE: [
                { label: 'My Trips', href: route('trips.index'), icon: <ClipboardDocumentListIcon className="h-5 w-5" />, activeRoute: 'trips.*' },
                { label: 'Create Trip', href: route('trips.create'), icon: <CalendarIcon className="h-5 w-5" />, activeRoute: 'trips.*' },
            ],
            DRIVER: [
                { label: 'MMJ Driver', href: route('driver.my-tasks'), icon: <ClipboardDocumentListIcon className="h-5 w-5" />, activeRoute: 'driver.my-tasks' },
                { label: 'Absensi Saya', href: route('driver.attendance'), icon: <CameraIcon className="h-5 w-5" />, activeRoute: 'driver.attendance' },
                { label: 'Trip History', href: route('driver.trip-history'), icon: <ClockIcon className="h-5 w-5" />, activeRoute: 'driver.trip-history' },
            ],
        };

        const roleItems = roleName ? (roleSpecificItems[roleName] ?? []) : [];
        return [...baseItems, ...roleItems];
    };

    const navItems = getNavItems();

    const isActive = (activeRoute?: string) => {
        if (!activeRoute) return false;
        try {
            return (window as any).route?.current?.(activeRoute) ?? false;
        } catch {
            return false;
        }
    };

    const initials = (user?.name ?? 'U')
        .split(' ')
        .map((n) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();

    return (
        <aside className="mmj-sidebar flex h-full w-64 flex-shrink-0 flex-col overflow-y-auto max-lg:hidden">
            {/* Brand */}
            <div className="relative flex h-28 items-center justify-center border-b border-[#E2E8F0]/70 px-4">
                <div className="pointer-events-none absolute inset-x-0 -bottom-8 mx-auto h-16 w-32 rounded-full bg-[#DBEAFE] opacity-60 blur-2xl" />
                <Link href={route('dashboard')} className="group relative flex flex-col items-center">
                    <img src={logoMmj} alt="MMJ Driver" className="h-12 w-auto object-contain transition-transform duration-300 group-hover:scale-105" />
                    <span className="mt-2 text-center text-sm font-bold tracking-tight text-[#0F172A]">MMJ DRIVER</span>
                </Link>
            </div>

            {/* Nav */}
            <nav className="flex-1 px-3 py-4">
                {roleName && (
                    <div className="mb-3 flex items-center gap-2 px-1">
                        {sectionIcons[roleName]}
                        <span className="mmj-navsection">{sectionLabels[roleName] ?? 'MENU'}</span>
                    </div>
                )}

                <p className="mmj-navsection mb-2 mt-4">Utama</p>
                <ul className="space-y-1 px-0.5">
                    {navItems.map((item, idx) => {
                        const active = isActive(item.activeRoute);
                        return (
                            <li
                                key={item.label}
                                className="mmj-nav-in"
                                style={{ animationDelay: `${Math.min(idx * 40, 400)}ms` }}
                            >
                                <Link
                                    href={item.href}
                                    className={`mmj-navitem ${active ? 'mmj-navitem-active' : ''}`}
                                >
                                    <span className="flex-shrink-0">{item.icon}</span>
                                    <span className="truncate">{item.label}</span>
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            </nav>

            {/* User card */}
            <div className="mt-auto border-t border-[#E2E8F0]/70 p-3">
                <div className="rounded-xl border border-[#E2E8F0] bg-white p-3 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
                    <div className="flex items-center gap-3">
                        <span className="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#2563EB] to-[#6366F1] text-[0.7rem] font-bold text-white">
                            {initials}
                        </span>
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-sm font-semibold text-[#0F172A]">{user?.name ?? 'User'}</p>
                            <p className="truncate text-[0.7rem] text-[#94A3B8]">{user?.role?.name ?? 'No Role'}</p>
                        </div>
                    </div>
                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="mt-2.5 flex w-full items-center justify-center gap-2 rounded-lg border border-[#E2E8F0] bg-white px-3 py-2 text-xs font-semibold text-[#64748B] transition hover:border-[#FECACA] hover:bg-[#FEF2F2] hover:text-[#DC2626]"
                    >
                        <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 11-4 0v-1m4-11V5a2 2 0 10-4 0v1" />
                        </svg>
                        <span>Keluar</span>
                    </Link>
                </div>
            </div>
        </aside>
    );
}
