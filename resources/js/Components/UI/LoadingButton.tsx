import { ButtonHTMLAttributes, ReactNode } from 'react';

interface LoadingButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    loading?: boolean;
    loadingText?: string;
    children: ReactNode;
}

export default function LoadingButton({ loading, loadingText, children, disabled, className = '', ...props }: LoadingButtonProps) {
    const hasCustomBg = className.includes('bg-') || className.includes('#');
    const base = hasCustomBg
        ? className
        : `inline-flex items-center justify-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 active:bg-gray-900 ${className}`;

    return (
        <button
            {...props}
            disabled={disabled || loading}
            className={base + (disabled || loading ? ' opacity-50 cursor-not-allowed' : '')}
        >
            {loading ? (
                <span className="flex items-center gap-2">
                    <svg className="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="3" />
                        <path className="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    {loadingText || 'Loading...'}
                </span>
            ) : (
                children
            )}
        </button>
    );
}