import { ReactNode } from 'react';

interface PanelProps {
    children: ReactNode;
    title?: string;
    description?: string;
    icon?: ReactNode;
    actions?: ReactNode;
    footer?: ReactNode;
    flat?: boolean;
    /** stagger delay class e.g. mmx-d2 */
    delay?: string;
    className?: string;
    bodyClassName?: string;
    style?: React.CSSProperties;
}

export default function Panel({
    children,
    title,
    description,
    icon,
    actions,
    footer,
    flat = false,
    delay = '',
    className = '',
    bodyClassName = 'p-5 sm:p-6',
    style,
}: PanelProps) {
    return (
        <section className={`mmj-card mmj-panel-in ${delay} ${flat ? 'mmj-card-flat' : ''} ${className}`} style={style}>
            {(title || actions) && (
                <header className="flex items-center justify-between gap-4 border-b border-[#E2E8F0]/70 px-5 py-4 sm:px-6">
                    <div className="flex items-center gap-3 min-w-0">
                        {icon && (
                            <span className="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-[#EFF6FF] text-[#2563EB] transition-transform duration-300 group-hover:scale-105 [&>svg]:h-4 [&>svg]:w-4">
                                {icon}
                            </span>
                        )}
                        <div className="min-w-0">
                            <h3 className="truncate text-sm font-semibold leading-tight text-[#0F172A] sm:text-base">{title}</h3>
                            {description && <p className="mt-0.5 truncate text-xs text-[#64748B]">{description}</p>}
                        </div>
                    </div>
                    {actions && <div className="flex flex-shrink-0 items-center gap-2">{actions}</div>}
                </header>
            )}
            <div className={bodyClassName}>{children}</div>
            {footer && <footer className="border-t border-[#E2E8F0]/70 px-5 py-3 text-xs text-[#64748B] sm:px-6">{footer}</footer>}
        </section>
    );
}
