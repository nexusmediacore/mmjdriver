import { ReactNode } from 'react';
import { Link } from '@inertiajs/react';
import { useCountUp } from '@/Hooks/useCountUp';

type StatTone = 'blue' | 'green' | 'purple' | 'amber' | 'sky' | 'red' | 'slate';

const tones: Record<StatTone, { color: string; soft: string; glow: string; shadow: string }> = {
    blue:   { color: '#2563EB', soft: 'rgba(37,99,235,0.12)',   glow: 'rgba(37,99,235,0.10)',   shadow: 'rgba(37,99,235,0.35)' },
    green:  { color: '#16A34A', soft: 'rgba(22,163,74,0.12)',   glow: 'rgba(22,163,74,0.10)',   shadow: 'rgba(22,163,74,0.35)' },
    purple: { color: '#8B5CF6', soft: 'rgba(139,92,246,0.12)',  glow: 'rgba(139,92,246,0.10)',  shadow: 'rgba(139,92,246,0.35)' },
    amber:  { color: '#D97706', soft: 'rgba(245,158,11,0.14)',  glow: 'rgba(245,158,11,0.12)',  shadow: 'rgba(245,158,11,0.32)' },
    sky:    { color: '#0284C7', soft: 'rgba(14,165,233,0.12)',  glow: 'rgba(14,165,233,0.10)',  shadow: 'rgba(14,165,233,0.35)' },
    red:    { color: '#DC2626', soft: 'rgba(220,38,38,0.12)',   glow: 'rgba(220,38,38,0.10)',   shadow: 'rgba(220,38,38,0.32)' },
    slate:  { color: '#475569', soft: 'rgba(71,85,105,0.12)',   glow: 'rgba(71,85,105,0.09)',   shadow: 'rgba(71,85,105,0.28)' },
};

interface StatCardProps {
    title: string;
    value: ReactNode;
    icon: ReactNode;
    tone?: StatTone;
    hint?: string;
    /** set false to skip count-up animation on numbers */
    animate?: boolean;
    /** makes the card a link to the given route/URL */
    href?: string;
    /** label shown on hover for clickable cards */
    linkLabel?: string;
    className?: string;
    style?: React.CSSProperties;
}

export default function StatCard({
    title,
    value,
    icon,
    tone = 'blue',
    hint,
    animate = true,
    href,
    linkLabel = 'Lihat detail',
    className = '',
    style,
}: StatCardProps) {
    const t = tones[tone];
    const numericValue = typeof value === 'number' ? value : undefined;
    const animated = useCountUp(numericValue, 900);
    const displayValue = animate && numericValue !== undefined ? animated : value;

    const content = (
        <>
            <div className="relative z-10 flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="mmj-stat-label truncate">{title}</p>
                    <p className="mmj-stat-value mt-1.5 truncate mmj-value-pop" key={String(displayValue)}>{displayValue}</p>
                    {hint && <p className="mt-1 text-xs text-[#94A3B8]">{hint}</p>}
                </div>
                <div className="mmj-stat-icon mmj-icon-rot flex-shrink-0">
                    <span className="[&>svg]:h-5 [&>svg]:w-5">{icon}</span>
                </div>
            </div>

            {href && (
                <div className="relative z-10 mt-3 flex items-center gap-1.5 border-t border-[#E2E8F0]/70 pt-3 text-xs font-semibold opacity-0 transition-all duration-300 group-hover:opacity-100" style={{ color: t.color }}>
                    {linkLabel}
                    <svg className="h-3.5 w-3.5 transition-transform duration-300 group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" strokeWidth={2.5} stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </div>
            )}
        </>
    );

    const cardStyle = {
        '--stat-color': t.color,
        '--stat-soft': t.soft,
        '--stat-glow': t.glow,
        '--stat-shadow': t.shadow,
        ...style,
    } as React.CSSProperties;

    if (href) {
        return (
            <Link
                href={href}
                className={`mmj-stat mmj-reveal group block cursor-pointer ${className}`}
                style={cardStyle}
            >
                {content}
            </Link>
        );
    }

    return (
        <div className={`mmj-stat mmj-reveal ${className}`} style={cardStyle}>
            {content}
        </div>
    );
}
