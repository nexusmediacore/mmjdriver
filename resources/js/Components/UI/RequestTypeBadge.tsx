interface RequestTypeBadgeProps {
    type?: string | null;
    size?: 'sm' | 'md';
}

const config: Record<string, { label: string; short: string; color: string; bg: string }> = {
    BUSINESS_TRIP: { label: 'Perjalanan Dinas', short: 'Dinas', color: '#1D4ED8', bg: '#EFF6FF' },
    GUEST_PICKUP: { label: 'Jemput Tamu', short: 'Tamu', color: '#B45309', bg: '#FFF7ED' },
    CARGO_PICKUP: { label: 'Muat Barang', short: 'Barang', color: '#6D28D9', bg: '#F5F0FF' },
};

export default function RequestTypeBadge({ type, size = 'sm' }: RequestTypeBadgeProps) {
    const cfg = config[type ?? 'BUSINESS_TRIP'] ?? config.BUSINESS_TRIP;
    const isSm = size === 'sm';

    return (
        <span
            className={`inline-flex items-center gap-1 rounded-full font-semibold whitespace-nowrap ${
                isSm ? 'px-2.5 py-1 text-[0.7rem]' : 'px-3 py-1.5 text-xs'
            }`}
            style={{ color: cfg.color, backgroundColor: cfg.bg }}
            title={cfg.label}
        >
            {type === 'GUEST_PICKUP' ? (
                <svg className={isSm ? 'h-3 w-3' : 'h-3.5 w-3.5'} fill="none" viewBox="0 0 24 24" strokeWidth={2.2} stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
            ) : (
                <svg className={isSm ? 'h-3 w-3' : 'h-3.5 w-3.5'} fill="none" viewBox="0 0 24 24" strokeWidth={2.2} stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" />
                </svg>
            )}
            {cfg.short}
        </span>
    );
}
