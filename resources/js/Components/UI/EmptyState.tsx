import { ReactNode } from 'react';

interface EmptyStateProps {
    icon?: ReactNode;
    title: string;
    description?: string;
    action?: ReactNode;
}

export default function EmptyState({ icon, title, description, action }: EmptyStateProps) {
    return (
        <div className="mmj-page-in flex flex-col items-center justify-center py-12 text-center">
            {icon && (
                <div className="mmj-float-icon relative mb-5">
                    <span className="absolute -inset-3 rounded-full bg-[#DBEAFE] opacity-60 blur-xl" />
                    <span className="relative flex h-16 w-16 items-center justify-center rounded-full border border-[#BFDBFE] bg-[#EFF6FF]">
                        {icon}
                    </span>
                </div>
            )}
            <h3 className="text-base font-semibold text-[#0F172A]">{title}</h3>
            {description && <p className="mt-1 max-w-sm text-sm text-[#64748B]">{description}</p>}
            {action && <div className="mt-5 animate-slide-up">{action}</div>}
        </div>
    );
}
