import { useEffect } from 'react';

interface ConfirmDialogProps {
    show: boolean;
    title: string;
    message: string;
    confirmLabel?: string;
    cancelLabel?: string;
    variant?: 'warning' | 'danger' | 'success' | 'info';
    onConfirm: () => void;
    onCancel: () => void;
}

const variants = {
    warning: { icon: '!', color: '#F59E0B', bg: '#FEF3C7', label: '#B45309' },
    danger: { icon: '✕', color: '#DC2626', bg: '#FEE2E2', label: '#B91C1C' },
    success: { icon: '✓', color: '#16A34A', bg: '#DCFCE7', label: '#15803D' },
    info: { icon: 'i', color: '#0EA5E9', bg: '#E0F2FE', label: '#0369A1' },
};

export default function ConfirmDialog({
    show, title, message, confirmLabel = 'Ya', cancelLabel = 'Batal',
    variant = 'warning', onConfirm, onCancel,
}: ConfirmDialogProps) {
    const v = variants[variant];

    useEffect(() => {
        const handleEscape = (e: KeyboardEvent) => {
            if (e.key === 'Escape') onCancel();
        };
        if (show) document.addEventListener('keydown', handleEscape);
        return () => document.removeEventListener('keydown', handleEscape);
    }, [show, onCancel]);

    if (!show) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div className="absolute inset-0 bg-black/20 backdrop-blur-sm transition-opacity duration-200" onClick={onCancel} />
            <div className="relative w-full max-w-sm transform rounded-xl bg-white p-6 shadow-[0_20px_60px_-15px_rgba(15,23,42,0.25)] transition-all duration-200 ease-out animate-modal-in">
                <div className="flex flex-col items-center text-center">
                    <div className="flex h-12 w-12 items-center justify-center rounded-full" style={{ backgroundColor: v.bg }}>
                        <span className="text-lg font-bold" style={{ color: v.color }}>{v.icon}</span>
                    </div>
                    <h3 className="mt-4 text-lg font-semibold text-[#0F172A]">{title}</h3>
                    <p className="mt-1.5 text-sm text-[#64748B]">{message}</p>
                </div>
                <div className="mt-6 flex gap-3">
                    <button
                        onClick={onCancel}
                        className="flex-1 rounded-lg border border-[#E2E8F0] px-4 py-2.5 text-sm font-medium text-[#64748B] transition-colors hover:bg-[#F1F5F9]"
                    >
                        {cancelLabel}
                    </button>
                    <button
                        onClick={onConfirm}
                        className="flex-1 rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition-all hover:brightness-105"
                        style={{ backgroundColor: v.color }}
                    >
                        {confirmLabel}
                    </button>
                </div>
            </div>
        </div>
    );
}