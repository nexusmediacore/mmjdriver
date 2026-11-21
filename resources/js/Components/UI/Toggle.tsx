interface ToggleProps {
    checked: boolean;
    onChange: (checked: boolean) => void;
    disabled?: boolean;
    label?: string;
    size?: 'sm' | 'md';
}

const sizes = {
    sm: {
        track: 'h-5 w-9',
        thumb: 'h-3.5 w-3.5',
        translate: 'translate-x-5',
    },
    md: {
        track: 'h-6 w-11',
        thumb: 'h-[18px] w-[18px]',
        translate: 'translate-x-[22px]',
    },
};

export default function Toggle({ checked, onChange, disabled = false, label, size = 'md' }: ToggleProps) {
    const s = sizes[size];

    return (
        <button
            type="button"
            role="switch"
            aria-checked={checked}
            aria-label={label ?? 'Toggle'}
            disabled={disabled}
            onClick={() => onChange(!checked)}
            className={[
                'relative inline-flex shrink-0 items-center rounded-full transition-all duration-300 ease-out',
                'focus:outline-none focus-visible:ring-4 focus-visible:ring-[#2563EB]/15',
                s.track,
                checked
                    ? 'bg-gradient-to-r from-[#2563EB] to-[#3B82F6] shadow-[0_2px_8px_-2px_rgba(37,99,235,0.45)]'
                    : 'bg-[#CBD5E1]',
                disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer',
            ].join(' ')}
        >
            <span
                className={[
                    'pointer-events-none absolute left-1 inline-block transform rounded-full bg-white shadow-sm ring-1 ring-black/5',
                    'transition-transform duration-300 ease-out',
                    s.thumb,
                    checked ? s.translate : 'translate-x-0',
                ].join(' ')}
            />
        </button>
    );
}
