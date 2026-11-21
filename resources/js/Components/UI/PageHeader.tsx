import { ReactNode } from 'react';

interface PageHeaderProps {
    eyebrow?: string;
    title: string;
    description?: string;
    actions?: ReactNode;
    meta?: ReactNode;
}

export default function PageHeader({ eyebrow, title, description, actions, meta }: PageHeaderProps) {
    return (
        <div className="mmj-pagehead mb-6">
            <div className="relative z-10 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div className="min-w-0">
                    {eyebrow && (
                        <p className="mb-1.5 flex items-center gap-2 text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-[#93C5FD]">
                            <span className="inline-block h-1.5 w-1.5 rounded-full bg-[#38BDF8]" />
                            {eyebrow}
                        </p>
                    )}
                    <h1 className="text-xl font-bold tracking-tight text-white sm:text-2xl">{title}</h1>
                    {description && <p className="mt-1.5 max-w-2xl text-sm text-white/65">{description}</p>}
                    {meta && <div className="mt-3">{meta}</div>}
                </div>
                {actions && <div className="flex flex-shrink-0 flex-wrap items-center gap-2">{actions}</div>}
            </div>
        </div>
    );
}
