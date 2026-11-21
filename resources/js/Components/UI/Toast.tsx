import { useState, useEffect } from 'react';

interface ToastItem {
    id: string;
    type: 'success' | 'error' | 'warning' | 'info';
    title: string;
    message?: string;
    duration?: number;
}

const variants = {
    success: { icon: '✓', color: '#16A34A', bg: '#DCFCE7' },
    error: { icon: '✕', color: '#DC2626', bg: '#FEE2E2' },
    warning: { icon: '!', color: '#F59E0B', bg: '#FEF3C7' },
    info: { icon: 'i', color: '#0EA5E9', bg: '#E0F2FE' },
};

export default function Toast({ toast, onDismiss }: { toast: ToastItem; onDismiss: (id: string) => void }) {
    const v = variants[toast.type];
    const [progress, setProgress] = useState(100);
    const duration = toast.duration ?? 4000;

    useEffect(() => {
        const start = Date.now();
        const interval = setInterval(() => {
            const elapsed = Date.now() - start;
            const remaining = Math.max(0, 100 - (elapsed / duration) * 100);
            setProgress(remaining);
            if (remaining === 0) {
                clearInterval(interval);
                onDismiss(toast.id);
            }
        }, 50);
        return () => clearInterval(interval);
    }, [toast.id, duration, onDismiss]);

    return (
        <div className="relative mb-2 w-full max-w-sm transform rounded-lg border border-black/[0.06] bg-white shadow-[0_4px_20px_-5px_rgba(0,0,0,0.15)] transition-all duration-200 ease-out animate-toast-in">
            <div className="flex items-start gap-3 p-3">
                <div className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-white text-sm font-bold" style={{ backgroundColor: v.color }}>
                    {v.icon}
                </div>
                <div className="flex-1 min-w-0">
                    <p className="text-sm font-semibold text-[#0F172A]">{toast.title}</p>
                    {toast.message && <p className="mt-0.5 text-xs text-[#64748B]">{toast.message}</p>}
                </div>
                <button onClick={() => onDismiss(toast.id)} className="text-[#94A3B8] hover:text-[#64748B]">
                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div className="h-1 overflow-hidden rounded-b-lg" style={{ backgroundColor: v.bg }}>
                <div className="h-full transition-all duration-50" style={{ width: `${progress}%`, backgroundColor: v.color }} />
            </div>
        </div>
    );
}