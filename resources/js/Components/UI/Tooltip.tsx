import { ReactNode, useState } from 'react';

interface TooltipProps {
    children: ReactNode;
    content: string;
    position?: 'top' | 'bottom' | 'left' | 'right';
}

export default function Tooltip({ children, content, position = 'top' }: TooltipProps) {
    const [show, setShow] = useState(false);
    let timeoutId: ReturnType<typeof setTimeout> | null = null;

    const handleEnter = () => {
        if (timeoutId) clearTimeout(timeoutId);
        timeoutId = setTimeout(() => setShow(true), 400);
    };

    const handleLeave = () => {
        if (timeoutId) clearTimeout(timeoutId);
        timeoutId = setTimeout(() => setShow(false), 100);
    };

    const positions: Record<string, string> = {
        top: 'bottom-full left-1/2 -translate-x-1/2 mb-2',
        bottom: 'top-full left-1/2 -translate-x-1/2 mt-2',
        left: 'right-full top-1/2 -translate-y-1/2 mr-2',
        right: 'left-full top-1/2 -translate-y-1/2 ml-2',
    };

    const arrowPositions: Record<string, string> = {
        top: 'top-full left-1/2 -translate-x-1/2 border-t-white',
        bottom: 'bottom-full left-1/2 -translate-x-1/2 border-b-white',
        left: 'left-full top-1/2 -translate-y-1/2 border-l-white',
        right: 'right-full top-1/2 -translate-y-1/2 border-r-white',
    };

    return (
        <div className="relative inline-flex" onMouseEnter={handleEnter} onMouseLeave={handleLeave}>
            {children}
            {show && (
                <div className={`absolute z-50 ${positions[position]}`}>
                    <div className="relative">
                        <div className="rounded-md bg-[#0F172A] px-2 py-1 text-xs font-medium text-white shadow-lg">
                            {content}
                        </div>
                        <div className={`absolute h-0 w-0 border-[6px] border-transparent ${arrowPositions[position]}`} />
                    </div>
                </div>
            )}
        </div>
    );
}