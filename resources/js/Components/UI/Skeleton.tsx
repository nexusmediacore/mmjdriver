import { ReactNode } from 'react';

interface SkeletonProps {
    className?: string;
    variant?: 'text' | 'card' | 'circle' | 'rect';
    count?: number;
}

const shapes: Record<string, string> = {
    text: 'h-4 w-full',
    card: 'h-32 w-full rounded-lg',
    circle: 'h-10 w-10 rounded-full',
    rect: 'h-20 w-full rounded-lg',
};

export default function Skeleton({ className = '', variant = 'text', count = 1 }: SkeletonProps) {
    return (
        <div className="flex flex-col gap-2">
            {Array.from({ length: count }).map((_, i) => (
                <div key={i} className={`skeleton-shimmer ${shapes[variant]} ${className}`} />
            ))}
        </div>
    );
}

export function SkeletonCard() {
    return (
        <div className="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-[0_1px_3px_-1px_rgba(15,23,42,0.06)]">
            <div className="flex items-center gap-3">
                <div className="h-10 w-10 rounded-lg bg-[#E2E8F0] skeleton-shimmer" />
                <div className="flex-1">
                    <div className="h-4 w-24 bg-[#E2E8F0] rounded skeleton-shimmer" />
                    <div className="mt-1.5 h-3 w-16 bg-[#E2E8F0] rounded skeleton-shimmer" />
                </div>
            </div>
            <div className="mt-4 space-y-2">
                <div className="h-3 w-full bg-[#E2E8F0] rounded skeleton-shimmer" />
                <div className="h-3 w-2/3 bg-[#E2E8F0] rounded skeleton-shimmer" />
            </div>
        </div>
    );
}