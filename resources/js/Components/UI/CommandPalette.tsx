import { useState, useEffect, useMemo } from 'react';
import { createPortal } from 'react-dom';

interface CommandItem {
    id: string;
    label: string;
    description?: string;
    icon?: string;
    keywords?: string[];
    action: () => void;
    section?: string;
}

interface CommandPaletteProps {
    open: boolean;
    onClose: () => void;
    items: CommandItem[];
}

export default function CommandPalette({ open, onClose, items }: CommandPaletteProps) {
    const [query, setQuery] = useState('');
    const [activeIndex, setActiveIndex] = useState(0);

    const filtered = useMemo(() => {
        const q = query.trim().toLowerCase();
        if (!q) return items;
        return items.filter((item) =>
            item.label.toLowerCase().includes(q) ||
            item.description?.toLowerCase().includes(q) ||
            item.keywords?.some((k) => k.toLowerCase().includes(q))
        );
    }, [query, items]);

    const grouped = useMemo(() => {
        const map = new Map<string, CommandItem[]>();
        filtered.forEach((item) => {
            const section = item.section || 'Lainnya';
            if (!map.has(section)) map.set(section, []);
            map.get(section)!.push(item);
        });
        return Array.from(map.entries());
    }, [filtered]);

    useEffect(() => {
        if (!open) {
            setQuery('');
            setActiveIndex(0);
        }
    }, [open]);

    useEffect(() => {
        setActiveIndex(0);
    }, [query]);

    const handleKeyDown = (e: React.KeyboardEvent) => {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setActiveIndex((i) => Math.min(i + 1, filtered.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActiveIndex((i) => Math.max(i - 1, 0));
        } else if (e.key === 'Enter' && filtered[activeIndex]) {
            e.preventDefault();
            filtered[activeIndex].action();
            onClose();
        }
    };

    if (!open) return null;

    return createPortal(
        <div className="fixed inset-0 z-[200] flex items-start justify-center pt-[15vh]">
            <div className="absolute inset-0 bg-black/30 backdrop-blur-sm transition-opacity duration-200" onClick={onClose} />
            <div className="relative w-full max-w-xl transform rounded-xl border border-[#E2E8F0] bg-white shadow-[0_25px_60px_-15px_rgba(15,23,42,0.3)] transition-all duration-200 ease-out animate-modal-in">
                <div className="flex items-center gap-2 border-b border-[#E2E8F0] px-4 py-3">
                    <svg className="h-5 w-5 text-[#94A3B8]" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        type="text"
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        onKeyDown={handleKeyDown}
                        placeholder="Cari navigasi..."
                        className="flex-1 border-0 bg-transparent px-0 py-1.5 text-sm text-[#0F172A] outline-none placeholder:text-[#94A3B8]"
                        autoFocus
                    />
                    <kbd className="hidden rounded border border-[#E2E8F0] px-1.5 py-0.5 text-[10px] text-[#94A3B8] sm:inline-flex">ESC</kbd>
                </div>
                <div className="max-h-[60vh] overflow-y-auto py-2">
                    {filtered.length === 0 ? (
                        <div className="px-4 py-8 text-center text-sm text-[#94A3B8]">Tidak ada hasil</div>
                    ) : (
                        grouped.map(([section, groupItems]) => (
                            <div key={section}>
                                <div className="px-4 py-1.5 text-[10px] font-semibold uppercase tracking-wider text-[#94A3B8]">{section}</div>
                                {groupItems.map((item) => {
                                    const idx = filtered.indexOf(item);
                                    return (
                                        <button
                                            key={item.id}
                                            onClick={item.action}
                                            className={`flex w-full items-center gap-3 px-4 py-2 text-left transition-colors ${
                                                idx === activeIndex ? 'bg-[#F8FAFC]' : 'hover:bg-[#F8FAFC]'
                                            }`}
                                        >
                                            <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#F1F5F9] text-base">
                                                {item.icon || '•'}
                                            </span>
                                            <div className="flex-1 min-w-0">
                                                <p className="text-sm font-medium text-[#0F172A]">{item.label}</p>
                                                {item.description && <p className="text-xs text-[#64748B] truncate">{item.description}</p>}
                                            </div>
                                        </button>
                                    );
                                })}
                            </div>
                        ))
                    )}
                </div>
                <div className="flex items-center gap-3 border-t border-[#E2E8F0] px-4 py-2 text-[10px] text-[#94A3B8]">
                    <span className="flex items-center gap-1"><kbd className="rounded border border-[#E2E8F0] px-1">↑</kbd><kbd className="rounded border border-[#E2E8F0] px-1">↓</kbd> navigasi</span>
                    <span className="flex items-center gap-1"><kbd className="rounded border border-[#E2E8F0] px-1">Enter</kbd> pilih</span>
                    <span className="flex items-center gap-1"><kbd className="rounded border border-[#E2E8F0] px-1">ESC</kbd> tutup</span>
                </div>
            </div>
        </div>,
        document.body
    );
}