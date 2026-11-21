import { ButtonHTMLAttributes } from 'react';

export default function PrimaryButton({
    className = '',
    disabled,
    children,
    ...props
}: ButtonHTMLAttributes<HTMLButtonElement>) {
    const hasCustomBg = className.includes('bg-') || className.includes('#');
    const base = hasCustomBg
        ? className
        : `inline-flex items-center rounded-md border border-transparent bg-[#2563EB] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-[#1D4ED8] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:ring-offset-2 active:bg-[#1D4ED8] ${className}`;

    return (
        <button
            {...props}
            className={base + (disabled ? ' opacity-50 cursor-not-allowed' : '')}
            disabled={disabled}
        >
            {children}
        </button>
    );
}