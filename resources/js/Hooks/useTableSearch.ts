import { useMemo, useState } from 'react';

/**
 * Client-side search across the given keys of each row.
 * Case-insensitive, trims the query, and returns the filtered rows
 * plus the current query and its setter.
 */
export function useTableSearch<T extends Record<string, any>>(
    data: T[],
    keys: string[],
    options: { initialQuery?: string } = {}
) {
    const [query, setQuery] = useState(options.initialQuery ?? '');

    const filtered = useMemo(() => {
        const q = query.trim().toLowerCase();
        if (!q) return data;

        return data.filter((row) =>
            keys.some((key) => {
                const raw = key.split('.').reduce<any>((acc, part) => (acc == null ? undefined : acc[part]), row);
                return raw != null && String(raw).toLowerCase().includes(q);
            })
        );
    }, [data, keys.join(','), query]);

    return { filtered, query, setQuery };
}
