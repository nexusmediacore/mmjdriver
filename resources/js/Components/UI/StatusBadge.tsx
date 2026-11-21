type StatusVariant = 'trip' | 'driver' | 'vehicle' | 'priority';

interface StatusBadgeProps {
    status: string;
    pulse?: boolean;
    /** pilih pemetaan warna agar status antar-modul tidak tercampur */
    variant?: StatusVariant;
}

const statusConfig: Record<string, { label: string; color: string; bg: string }> = {
    DRAFT: { label: 'Draft', color: '#64748B', bg: '#F1F5F9' },
    SUBMITTED: { label: 'Dikirim', color: '#0EA5E9', bg: '#E0F2FE' },
    WAITING_APPROVAL: { label: 'Menunggu Persetujuan', color: '#F59E0B', bg: '#FEF3C7' },
    APPROVED: { label: 'Disetujui', color: '#16A34A', bg: '#DCFCE7' },
    REJECTED: { label: 'Ditolak', color: '#DC2626', bg: '#FEE2E2' },
    WAITING_DRIVER: { label: 'Menunggu Driver', color: '#0EA5E9', bg: '#E0F2FE' },
    DRIVER_ASSIGNED: { label: 'Driver Ditugaskan', color: '#0EA5E9', bg: '#E0F2FE' },
    DRIVER_ACCEPTED: { label: 'Driver Menerima', color: '#8B5CF6', bg: '#F5F0FF' },
    DRIVER_REJECTED: { label: 'Driver Menolak', color: '#DC2626', bg: '#FEE2E2' },
    READY_TO_DEPART: { label: 'Siap Berangkat', color: '#8B5CF6', bg: '#F5F0FF' },
    ON_TRIP: { label: 'Dalam Perjalanan', color: '#D97706', bg: '#FEF3C7' },
    COMPLETED: { label: 'Selesai', color: '#16A34A', bg: '#DCFCE7' },
    CANCELLED: { label: 'Dibatalkan', color: '#64748B', bg: '#F1F5F9' },
    STANDBY: { label: 'Standby', color: '#0EA5E9', bg: '#E0F2FE' },
    ASSIGNED: { label: 'Ditugaskan', color: '#D97706', bg: '#FEF3C7' },
    RETURNING: { label: 'Kembali', color: '#7C3AED', bg: '#EDE9FE' },
    OFF_DUTY: { label: 'Off Duty', color: '#64748B', bg: '#F1F5F9' },
    UNAVAILABLE: { label: 'Tidak Tersedia', color: '#DC2626', bg: '#FEE2E2' },
    PENDING: { label: 'Pending', color: '#D97706', bg: '#FEF3C7' },
    AVAILABLE: { label: 'Tersedia', color: '#16A34A', bg: '#DCFCE7' },
    IN_USE: { label: 'Dipakai', color: '#D97706', bg: '#FEF3C7' },
    MAINTENANCE: { label: 'Perawatan', color: '#DC2626', bg: '#FEE2E2' },
    URGENT: { label: 'Urgent', color: '#DC2626', bg: '#FEE2E2' },
    NORMAL: { label: 'Normal', color: '#64748B', bg: '#F1F5F9' },
    VERY_URGENT: { label: 'Sangat Urgent', color: '#DC2626', bg: '#FEE2E2' },
};

// Status driver memakai palet khusus agar mudah dibedakan dari status trip
const driverStatusConfig: Record<string, { label: string; color: string; bg: string }> = {
    STANDBY: { label: 'Standby', color: '#0EA5E9', bg: '#E0F2FE' },
    ON_TRIP: { label: 'Sedang Bertugas', color: '#2563EB', bg: '#DBEAFE' },
    ASSIGNED: { label: 'Ditugaskan', color: '#D97706', bg: '#FEF3C7' },
    ACCEPTED: { label: 'Menerima Tugas', color: '#059669', bg: '#D1FAE5' },
    RETURNING: { label: 'Kembali ke Base', color: '#7C3AED', bg: '#EDE9FE' },
    COMPLETED: { label: 'Selesai Bertugas', color: '#16A34A', bg: '#DCFCE7' },
    LEAVE: { label: 'Cuti', color: '#DB2777', bg: '#FCE7F3' },
    SICK: { label: 'Sakit', color: '#DC2626', bg: '#FEE2E2' },
    OFF_DUTY: { label: 'Off Duty', color: '#64748B', bg: '#F1F5F9' },
    UNAVAILABLE: { label: 'Tidak Tersedia', color: '#64748B', bg: '#F1F5F9' },
};

// Status kendaraan
const vehicleStatusConfig: Record<string, { label: string; color: string; bg: string }> = {
    AVAILABLE: { label: 'Tersedia', color: '#16A34A', bg: '#DCFCE7' },
    IN_USE: { label: 'Dipakai', color: '#2563EB', bg: '#DBEAFE' },
    ASSIGNED: { label: 'Ditugaskan', color: '#D97706', bg: '#FEF3C7' },
    MAINTENANCE: { label: 'Perawatan', color: '#DC2626', bg: '#FEE2E2' },
    UNAVAILABLE: { label: 'Tidak Tersedia', color: '#64748B', bg: '#F1F5F9' },
};

const fallback = { color: '#64748B', bg: '#F1F5F9' };

export default function StatusBadge({ status, pulse = false, variant = 'trip' }: StatusBadgeProps) {
    const map = variant === 'driver'
        ? driverStatusConfig
        : variant === 'vehicle'
            ? vehicleStatusConfig
            : statusConfig;

    const cfg = map[status] ?? statusConfig[status] ?? { label: status, ...fallback };

    return (
        <span
            className={`inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold ${pulse ? 'animate-pulse-subtle' : ''}`}
            style={{ color: cfg.color, backgroundColor: cfg.bg }}
        >
            <span className="h-1.5 w-1.5 flex-shrink-0 rounded-full" style={{ backgroundColor: cfg.color }} />
            {cfg.label}
        </span>
    );
}
