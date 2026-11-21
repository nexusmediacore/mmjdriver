import { useEffect, useRef, useState } from 'react';

/**
 * Animates a number from 0 to `value` using requestAnimationFrame.
 * Falls back to rendering the raw value if it is not numeric.
 */
export function useCountUp(value: number | undefined, duration = 900): number {
    const [display, setDisplay] = useState<number>(value ?? 0);
    const rafRef = useRef<number | null>(null);
    const startRef = useRef<number | null>(null);

    useEffect(() => {
        if (typeof value !== 'number') {
            setDisplay(0);
            return;
        }

        const from = typeof display === 'number' ? display : 0;
        const to = value;

        if (from === to) {
            setDisplay(to);
            return;
        }

        const easeOutCubic = (t: number) => 1 - Math.pow(1 - t, 3);

        const step = (timestamp: number) => {
            if (startRef.current === null) startRef.current = timestamp;
            const elapsed = timestamp - startRef.current;
            const progress = Math.min(elapsed / duration, 1);
            const next = Math.round(from + (to - from) * easeOutCubic(progress));
            setDisplay(next);
            if (progress < 1) {
                rafRef.current = requestAnimationFrame(step);
            } else {
                setDisplay(to);
                startRef.current = null;
            }
        };

        rafRef.current = requestAnimationFrame(step);

        return () => {
            if (rafRef.current !== null) cancelAnimationFrame(rafRef.current);
            startRef.current = null;
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [value, duration]);

    return display;
}
