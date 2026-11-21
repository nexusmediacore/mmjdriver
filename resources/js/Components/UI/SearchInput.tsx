import { useEffect, useRef } from 'react';
import { MagnifyingGlassIcon, XMarkIcon } from '@heroicons/react/24/outline';

interface SearchInputProps {
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    /** result counter text shown under the input */
    resultInfo?: string;
    className?: string;
}

export default function SearchInput({
    value,
    onChange,
    placeholder = 'Cari...',
    resultInfo,
    className = '',
}: SearchInputProps) {
    const inputRef = useRef<HTMLInputElement>(null);

    // Press "/" anywhere to focus the search field (unless typing in a field),
    // and Escape to clear it.
    useEffect(() => {
        const onKeyDown = (e: KeyboardEvent) => {
            const target = e.target as HTMLElement | null;
            const tag = target?.tagName;
            const isTyping = tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || target?.isContentEditable;

            if (e.key === '/' && !isTyping) {
                e.preventDefault();
                inputRef.current?.focus();
            }
            if (e.key === 'Escape' && target === inputRef.current) {
                onChange('');
                inputRef.current?.blur();
            }
        };

        document.addEventListener('keydown', onKeyDown);
        return () => document.removeEventListener('keydown', onKeyDown);
    }, [onChange]);

    return (
        <div className={`relative ${className}`}>
            <div className="relative">
                <MagnifyingGlassIcon className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#94A3B8]" />
                <input
                    ref={inputRef}
                    type="text"
                    value={value}
                    onChange={(e) => onChange(e.target.value)}
                    placeholder={placeholder}
                    aria-label={placeholder}
                    className="mmj-input py-2 pl-10 pr-8 text-sm"
                />
                {value && (
                    <button
                        type="button"
                        onClick={() => {
                            onChange('');
                            inputRef.current?.focus();
                        }}
                        aria-label="Hapus pencarian"
                        className="absolute right-2 top-1/2 flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full text-[#94A3B8] transition hover:bg-[#F1F5F9] hover:text-[#0F172A]"
                    >
                        <XMarkIcon className="h-3.5 w-3.5" />
                    </button>
                )}
            </div>
            {resultInfo && (
                <p className="mt-1.5 text-xs text-[#94A3B8]">{resultInfo}</p>
            )}
        </div>
    );
}
