import { useEffect, useRef } from 'react';
import { createPortal } from 'react-dom';

interface ModalProps {
    show: boolean;
    onClose: () => void;
    children: React.ReactNode;
    maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl';
}

export default function Modal({ show, onClose, children, maxWidth = 'md' }: ModalProps) {
    const ref = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const handleEscape = (e: KeyboardEvent) => {
            if (e.key === 'Escape') onClose();
        };
        if (show) {
            document.body.style.overflow = 'hidden';
            document.addEventListener('keydown', handleEscape);
            ref.current?.focus();
        }
        return () => {
            document.body.style.overflow = 'unset';
            document.removeEventListener('keydown', handleEscape);
        };
    }, [show, onClose]);

    if (!show) return null;

    const widths: Record<string, string> = {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
    };

    return createPortal(
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div
                className="absolute inset-0 bg-black/20 backdrop-blur-sm transition-opacity duration-200"
                onClick={onClose}
            />
            <div
                ref={ref}
                className={`relative w-full ${widths[maxWidth]} transform rounded-xl border border-[#E2E8F0] bg-white p-6 shadow-[0_20px_60px_-15px_rgba(15,23,42,0.2)] transition-all duration-200 ease-out animate-modal-in`}
                tabIndex={-1}
            >
                {children}
            </div>
        </div>,
        document.body
    );
}