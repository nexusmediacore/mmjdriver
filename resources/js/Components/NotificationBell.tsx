import { router, usePage } from '@inertiajs/react';
import { useRef, useState } from 'react';

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
    'App\\Notifications\\LoadingApprovalRequested': 'Persetujuan Driver Bantu Muat',
    'App\\Notifications\\LoadingApprovalDecision': 'Keputusan Driver Bantu Muat',
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
    const [open, setOpen] = useState(false);

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

    /**
     * Arahkan pengguna ke rute tujuan dari payload notifikasi.
     * Setiap notification mengirimkan field "url" (mis. "/trips/12").
     * Status read langsung ditandai saat item diklik.
     */
    const openNotification = (n: NotificationItem) => {
        const url = typeof n.data?.url === 'string' ? n.data.url : null;

        if (n.read_at === null) {
            markRead(n.id);
        }

        if (url) {
            router.visit(url, { preserveScroll: false });
        }
        setOpen(false);
    };

    return (
        <div className="relative">
            <button
                type="button"
                onClick={() => {
                    setOpen((p) => !p);
                    refresh();
                }}
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

            {open && (
                <>
                    {/* Backdrop menutup dropdown saat klik di luar */}
                    <div
                        className="fixed inset-0 z-40"
                        onClick={() => setOpen(false)}
                    />
                    {/* Panel solid — tidak transparan, border tegas, di atas semua elemen */}
                    <div
                        className="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-lg border-2 border-[#0F172A] bg-white shadow-[0_20px_50px_-12px_rgba(15,23,42,0.45)]"
                        role="menu"
                    >
                        <div className="flex items-center justify-between border-b-2 border-[#E2E8F0] bg-[#0F172A] px-4 py-3">
                            <span className="text-sm font-bold text-white">Notifikasi</span>
                            {unreadCount > 0 && (
                                <button
                                    type="button"
                                    onClick={markAllRead}
                                    className="text-xs font-semibold text-[#93C5FD] hover:underline"
                                >
                                    Tandai semua dibaca
                                </button>
                            )}
                        </div>
                        <div className="max-h-80 overflow-y-auto bg-white">
                            {notifications.length === 0 ? (
                                <p className="px-4 py-6 text-center text-sm text-[#64748B]">Tidak ada notifikasi baru.</p>
                            ) : (
                                notifications.map((n) => (
                                    <button
                                        key={n.id}
                                        type="button"
                                        onClick={() => openNotification(n)}
                                        className={`flex w-full items-start gap-3 border-b border-[#E2E8F0] px-4 py-3 text-left transition ${
                                            n.read_at === null ? 'bg-[#EFF6FF]' : 'bg-white hover:bg-[#F8FAFC]'
                                        }`}
                                    >
                                        <span
                                            className={`mt-1 h-2 w-2 flex-shrink-0 rounded-full ${
                                                n.read_at === null ? 'bg-[#2563EB]' : 'bg-[#CBD5E1]'
                                            }`}
                                        />
                                        <span className="min-w-0 flex-1">
                                            <span className="block text-sm font-semibold text-[#0F172A]">
                                                {TYPE_LABELS[n.type] ?? 'Notifikasi'}
                                            </span>
                                            <span className="mt-0.5 block truncate text-xs text-[#475569]">
                                                {String(n.data?.message ?? '')}
                                            </span>
                                            <span className="mt-0.5 block text-[0.7rem] text-[#94A3B8]">
                                                {timeAgo(n.created_at)}
                                            </span>
                                        </span>
                                    </button>
                                ))
                            )}
                        </div>
                        <div className="border-t-2 border-[#E2E8F0] bg-[#F8FAFC] px-4 py-2.5 text-center">
                            <button
                                type="button"
                                onClick={() => {
                                    router.visit('/trips', { preserveScroll: false });
                                    setOpen(false);
                                }}
                                className="text-xs font-semibold text-[#2563EB] hover:underline"
                            >
                                Lihat Semua Perjalanan
                            </button>
                        </div>
                    </div>
                </>
            )}
        </div>
    );
}
