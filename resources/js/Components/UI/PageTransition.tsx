import { ReactNode } from 'react';

interface PageTransitionProps {
    children: ReactNode;
    className?: string;
}

export default function PageTransition({ children, className = '' }: PageTransitionProps) {
    return (
        <div className={`transition-all duration-200 ease-out animate-slide-up ${className}`}>
            {children}
        </div>
    );
}

export function FadeIn({ children, delay = 0, className = '' }: { children: ReactNode; delay?: number; className?: string }) {
    return (
        <div className={`transition-all duration-200 ease-out animate-fade-in ${className}`} style={{ animationDelay: `${delay}ms` }}>
            {children}
        </div>
    );
}

export function SlideUp({ children, delay = 0, className = '' }: { children: ReactNode; delay?: number; className?: string }) {
    return (
        <div className={`transition-all duration-200 ease-out animate-slide-up ${className}`} style={{ animationDelay: `${delay}ms` }}>
            {children}
        </div>
    );
}