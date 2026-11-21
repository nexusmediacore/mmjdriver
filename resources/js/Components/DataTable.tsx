import { ReactNode, useMemo } from 'react';
import EmptyState from '@/Components/UI/EmptyState';
import SearchInput from '@/Components/UI/SearchInput';
import { useTableSearch } from '@/Hooks/useTableSearch';

interface Column<T> {
    key: string;
    label: string;
    render?: (row: T) => ReactNode;
}

interface DataTableProps<T extends Record<string, any>> {
    data: T[];
    columns: Column<T>[];
    emptyMessage?: string;
    emptyDescription?: string;
    /** enable the search box (default: true) */
    searchable?: boolean;
    /** keys used for searching; defaults to all column keys */
    searchKeys?: string[];
    searchPlaceholder?: string;
    /** extra controls shown next to the search box */
    toolbarActions?: ReactNode;
    /** text shown below the count, e.g. total data */
    footerNote?: string;
}

export default function DataTable<T extends Record<string, any>>({
    data,
    columns,
    emptyMessage = 'Tidak ada data',
    emptyDescription,
    searchable = true,
    searchKeys,
    searchPlaceholder,
    toolbarActions,
    footerNote,
}: DataTableProps<T>) {
    const keys = useMemo(
        () => searchKeys ?? columns.map((c) => c.key).filter((k) => !k.includes('.')),
        [searchKeys, columns]
    );

    const { filtered, query, setQuery } = useTableSearch<T>(data, keys);
    const isSearching = query.trim().length > 0;

    return (
        <div className="mmj-card mmj-card-flat mmj-reveal overflow-hidden">
            {(searchable || toolbarActions) && (
                <div className="flex flex-col gap-3 border-b border-[#E2E8F0]/70 bg-[#FBFCFE] px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between">
                    {searchable ? (
                        <SearchInput
                            value={query}
                            onChange={setQuery}
                            placeholder={searchPlaceholder ?? 'Cari data... (tekan /)'}
                            className="w-full sm:max-w-[19rem]"
                        />
                    ) : (
                        <div />
                    )}
                    <div className="flex flex-wrap items-center gap-2">
                        {isSearching && (
                            <span className="inline-flex items-center gap-1.5 rounded-full border border-[#BFDBFE] bg-[#EFF6FF] px-2.5 py-1 text-[0.7rem] font-semibold text-[#2563EB]">
                                <span className="h-1.5 w-1.5 rounded-full bg-[#2563EB]" />
                                {filtered.length} hasil
                            </span>
                        )}
                        {toolbarActions}
                    </div>
                </div>
            )}

            <div className="overflow-x-auto">
                <table className="mmj-table w-full">
                    <thead>
                        <tr>
                            {columns.map((col) => (
                                <th key={col.key} className="text-start">
                                    {col.label}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {filtered.length > 0 ? (
                            filtered.map((row, idx) => (
                                <tr
                                    key={row.id ?? idx}
                                    className="mmj-row-in"
                                    style={{ animationDelay: `${Math.min(idx * 45, 400)}ms` }}
                                >
                                    {columns.map((col) => (
                                        <td key={col.key}>
                                            {col.render ? col.render(row) : row[col.key]}
                                        </td>
                                    ))}
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td colSpan={columns.length} className="px-6 py-4">
                                    <EmptyState
                                        title={isSearching ? 'Data tidak ditemukan' : emptyMessage}
                                        description={
                                            isSearching
                                                ? `Tidak ada data yang cocok dengan pencarian "${query}". Coba kata kunci lain.`
                                                : emptyDescription
                                        }
                                    />
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            <div className="flex flex-wrap items-center justify-between gap-2 border-t border-[#E2E8F0]/70 bg-[#FBFCFE] px-5 py-3 text-xs font-medium text-[#94A3B8]">
                <span>
                    {isSearching ? (
                        <>
                            Menampilkan <span className="font-semibold text-[#2563EB]">{filtered.length}</span> dari {data.length} data
                        </>
                    ) : (
                        <>{data.length} data ditemukan</>
                    )}
                </span>
                {footerNote && <span>{footerNote}</span>}
            </div>
        </div>
    );
}
