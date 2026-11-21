import { router, usePage } from '@inertiajs/react';
import { useRef } from 'react';
import Dropdown from '@/Components/Dropdown';

interface NotificationItem {
    id: string;
    type: string;
    data: Record<string, unknown>;
    read_at: string | null;
    created_at: string;
}

const TYPE_LABELS: Record<string, string> = {
    'App\\Notifications\\TripCreatedNotification': 'Permintaan Perjalanan Baru',
    'App\\Notifications\\TripSubmitted': 'Trip Diajukan',
    'App\\Notifications\\TripApproved': 'Trip Disetujui',
    'App\\Notifications\\TripRejected': 'Trip Ditolak',
    'App\\Notifications\\DriverAssigned': 'Driver Ditugaskan',
    'App\\Notifications\\DriverAssignedNotification': 'Driver Ditugaskan',
    'App\\Notifications\\TripCompletionRequested': 'Pengajuan Selesai',
    'App\\Notifications\\TripCompletionApproved': 'Penyelesaian Disetujui',
};

function timeAgo(iso: string): string {
    const diff = Date.now() - new Date(iso).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return 'baru saja';
    if (mins < 60) return `${mins} menit lalu`;
    const hours = Math.floor(mins / 60);
    if (hours < 24) return `${hours} jam lalu`;
    const days = Math.floor(hours / 24);
    return `${days} hari lalu`;
}

export default function NotificationBell() {
    const page = usePage() as unknown as {
        props: {
            unreadNotifications: NotificationItem[];
            unreadNotificationsCount: number;
        };
    };
    const notifications = page.props.unreadNotifications ?? [];
    const unreadCount = page.props.unreadNotificationsCount ?? 0;
    const skipRefresh = useRef(false);

    const refresh = () => {
        if (skipRefresh.current) {
            skipRefresh.current = false;
            return;
        }
        router.reload({ only: ['unreadNotifications', 'unreadNotificationsCount'] });
    };

    const markRead = (id: string) => {
        router.post(route('notifications.read', id), {}, { preserveScroll: true });
    };

    const markAllRead = () => {
        router.post(route('notifications.read-all'), {}, { preserveScroll: true });
   };

    return (
        <Dropdown>
            <Dropdown.Trigger>
                <button
                    type="button"
                    onClick={refresh}
                    className="relative flex h-9 w-9 items-center justify-center rounded-full border border-[#E2E8F0] bg-white text-[#64748B] transition hover:border-[#BFDBFE] hover:text-[#2563EB]"
                    aria-label="Notifikasi"
                >
                    <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                    </svg>
                    {unreadCount > 0 && (
                        <span className="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-[#DC2626] px-1 text-[0.65rem] font-bold text-white">
                            {unreadCount > 99 ? '99+' : unreadCount}
                        </span>
                    )}
                </button>
            </Dropdown.Trigger>
            <Dropdown.Content width="80" contentClasses="py-2">
                <div className="flex items-center justify-between px-4 pb-2">
                    <span className="text-sm font-semibold text-[#0F172A]">Notifikasi</span>
                    {unreadCount > 0 && (
                        <button
                            type="button"
                            onClick={markAllRead}
                            className="text-xs font-medium text-[#2563EB] hover:underline"
                        >
                            Tandai semua dibaca
                        </button>
                    )}
                </div>
                <div className="max-h-80 overflow-y-auto">
                    {notifications.length === 0 ? (
                        <p className="px-4 py-6 text-center text-sm text-[#94A3B8]">Tidak ada notifikasi baru.</p>
                    ) : (
                        notifications.map((n) => (
                            <div
                                key={n.id}
                                className="flex items-start justify-between gap-3 border-t border-[#F1F5F9] px-4 py-3 hover:bg-[#F8FAFC]"
                            >
                                <div className="min-w-0">
                                    <p className="text-sm font-semibold text-[#0F172A]">
                                        {TYPE_LABELS[n.type] ?? 'Notifikasi'}
                                    </p>
                                    <p className="truncate text-xs text-[#64748B]">
                                        {String(n.data?.message ?? '')}
                                    </p>
                                    <p className="mt-0.5 text-[0.7rem] text-[#94A3B8]">{timeAgo(n.created_at)}</p>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => markRead(n.id)}
                                    className="mt-1 flex-shrink-0 text-[0.7rem] font-medium text-[#2563EB] hover:underline"
                                >
                                    Tandai dibaca
                                </button>
                            </div>
                        ))
                    )}
                </div>
            </Dropdown.Content>
        </Dropdown>
    );
}
