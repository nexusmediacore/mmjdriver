import { ReactNode, useState, useEffect } from 'react';
import { createPortal } from 'react-dom';

interface BottomSheetProps {
    show: boolean;
    onClose: () => void;
    children: ReactNode;
    title?: string;
}

export default function BottomSheet({ show, onClose, children, title }: BottomSheetProps) {
    useEffect(() => {
        const handleEscape = (e: KeyboardEvent) => {
            if (e.key === 'Escape') onClose();
        };
        if (show) {
            document.body.style.overflow = 'hidden';
            document.addEventListener('keydown', handleEscape);
        }
        return () => {
            document.body.style.overflow = 'unset';
            document.removeEventListener('keydown', handleEscape);
        };
    }, [show, onClose]);

    if (!show) return null;

    return createPortal(
        <div className="fixed inset-0 z-50 sm:hidden">
            <div className="absolute inset-0 bg-black/40 transition-opacity duration-200" onClick={onClose} />
            <div className="absolute bottom-0 left-0 right-0 transform rounded-t-xl bg-white transition-all duration-250 ease-out animate-sheet-up">
                <div className="flex items-center justify-between border-b border-[#E2E8F0] px-4 py-3">
                    <h3 className="text-sm font-semibold text-[#0F172A]">{title || ''}</h3>
                    <button onClick={onClose} className="text-[#94A3B8]">
                        <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div className="max-h-[80vh] overflow-y-auto">
                    {children}
                </div>
            </div>
        </div>,
        document.body
    );
}