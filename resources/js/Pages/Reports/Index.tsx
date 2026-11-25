import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { ChartPieIcon, DocumentArrowDownIcon, FunnelIcon } from '@heroicons/react/24/outline';
import SectionPage from '@/Components/SectionPage';
import StatCard from '@/Components/UI/StatCard';
import Panel from '@/Components/UI/Panel';
import SearchInput from '@/Components/UI/SearchInput';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { useTableSearch } from '@/Hooks/useTableSearch';

interface Props {
    stats: {
        total_trips: number;
        completed_trips: number;
        cancelled_trips: number;
        pending_trips: number;
        total_passengers: number;
        urgent_trips: number;
    };
    byDepartment: Array<{ name: string; count: number }>;
    byDriver: Array<{ name: string; count: number }>;
       byVehicle: Array<{ name: string; plate_number?: string; brand?: string; model?: string; count: number }>;
    filters: { date_from: string; date_to: string };
}

export default function ReportsIndex({ stats, byDepartment, byDriver, byVehicle, filters }: Props) {
    const [dateFrom, setDateFrom] = useState(filters.date_from);
    const [dateTo, setDateTo] = useState(filters.date_to);

    const handleFilter = () => {
        router.get('/reports', { date_from: dateFrom, date_to: dateTo }, { preserveState: true });
    };

    const handlePdf = () => {
        window.location.href = `/reports/pdf?date_from=${dateFrom}&date_to=${dateTo}`;
    };

    const dept = useTableSearch(byDepartment, ['name']);
    const driver = useTableSearch(byDriver, ['name']);
        // Cari berdasarkan model kendaraan (brand/model)
        const vehicle = useTableSearch(byVehicle, ['name', 'brand', 'model']);

    return (
        <SectionPage
            title="Laporan Perjalanan"
            eyebrow="Analitik"
            description="Rekapitulasi data perjalanan dinas berdasarkan periode, departemen, driver, dan kendaraan."
            actions={
                <SecondaryButton onClick={handlePdf}>
                    <DocumentArrowDownIcon className="h-4 w-4" />
                    Lihat / Download PDF
                </SecondaryButton>
            }
        >
            <Panel
                title="Filter Periode"
                description="Tentukan rentang tanggal laporan"
                icon={<FunnelIcon className="h-4 w-4" />}
                className="mb-6"
                delay="mmx-d1"
            >
                <div className="flex flex-wrap items-end gap-4">
                    <div>
                        <label className="mmx-label">Dari Tanggal</label>
                        <input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} className="mmj-input w-40 px-3.5 py-2.5" />
                    </div>
                    <div>
                        <label className="mmx-label">Sampai Tanggal</label>
                        <input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} className="mmj-input w-40 px-3.5 py-2.5" />
                    </div>
                    <PrimaryButton onClick={handleFilter}>Terapkan Filter</PrimaryButton>
                </div>
            </Panel>

            <div className="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                <StatCard tone="blue" className="mmx-d1" title="Total" value={stats.total_trips} icon={<ChartPieIcon className="h-5 w-5" />} />
                <StatCard tone="green" className="mmx-d2" title="Selesai" value={stats.completed_trips} icon={<ChartPieIcon className="h-5 w-5" />} />
                <StatCard tone="red" className="mmx-d3" title="Dibatalkan" value={stats.cancelled_trips} icon={<ChartPieIcon className="h-5 w-5" />} />
                <StatCard tone="amber" className="mmx-d4" title="Pending" value={stats.pending_trips} icon={<ChartPieIcon className="h-5 w-5" />} />
                <StatCard tone="purple" className="mmx-d5" title="Penumpang" value={stats.total_passengers} icon={<ChartPieIcon className="h-5 w-5" />} />
                <StatCard tone="slate" className="mmx-d6" title="Urgent" value={stats.urgent_trips} icon={<ChartPieIcon className="h-5 w-5" />} />
            </div>

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <Panel
                    delay="mmx-d2"
                    title="Per Departemen"
                    description={`${dept.filtered.length} dari ${byDepartment.length} departemen`}
                    icon={<ChartPieIcon className="h-4 w-4" />}
                >
                    <SearchInput
                        value={dept.query}
                        onChange={dept.setQuery}
                        placeholder="Cari departemen... (tekan /)"
                        className="mb-4"
                    />
                    <table className="mmj-table w-full">
                        <thead>
                            <tr>
                                <th className="text-start">Departemen</th>
                                <th className="text-end">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            {dept.filtered.map((d, i) => (
                                <tr key={d.name + i} className="mmj-row-in" style={{ animationDelay: `${Math.min(i * 45, 300)}ms` }}>
                                    <td>{d.name}</td>
                                    <td className="text-end font-semibold text-[#2563EB]">{d.count}</td>
                                </tr>
                            ))}
                            {dept.filtered.length === 0 && (
                                <tr><td colSpan={2} className="py-6 text-center text-[#94A3B8]">Tidak ada data</td></tr>
                            )}
                        </tbody>
                    </table>
                </Panel>

                <Panel
                    delay="mmx-d3"
                    title="Per Driver"
                    description={`${driver.filtered.length} dari ${byDriver.length} driver`}
                    icon={<ChartPieIcon className="h-4 w-4" />}
                >
                    <SearchInput
                        value={driver.query}
                        onChange={driver.setQuery}
                        placeholder="Cari driver... (tekan /)"
                        className="mb-4"
                    />
                    <table className="mmj-table w-full">
                        <thead>
                            <tr>
                                <th className="text-start">Driver</th>
                                <th className="text-end">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            {driver.filtered.map((d, i) => (
                                <tr key={d.name + i} className="mmj-row-in" style={{ animationDelay: `${Math.min(i * 45, 300)}ms` }}>
                                    <td>{d.name}</td>
                                    <td className="text-end font-semibold text-[#2563EB]">{d.count}</td>
                                </tr>
                            ))}
                            {driver.filtered.length === 0 && (
                                <tr><td colSpan={2} className="py-6 text-center text-[#94A3B8]">Tidak ada data</td></tr>
                            )}
                        </tbody>
                    </table>
                </Panel>

                <Panel
                    delay="mmx-d4"
                    title="Per Kendaraan"
                    description={`${vehicle.filtered.length} dari ${byVehicle.length} kendaraan`}
                    icon={<ChartPieIcon className="h-4 w-4" />}
                >
                    <SearchInput
                        value={vehicle.query}
                        onChange={vehicle.setQuery}
                        placeholder="Cari model kendaraan... (tekan /)"
                        className="mb-4"
                    />
                    <table className="mmj-table w-full">
                        <thead>
                            <tr>
                                <th className="text-start">Model Kendaraan</th>
                                <th className="text-end">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            {vehicle.filtered.map((d, i) => (
                                <tr key={String(d.name ?? i) + i} className="mmj-row-in" style={{ animationDelay: `${Math.min(i * 45, 300)}ms` }}>
                                    <td>{d.name || d.model || '-'}</td>
                                    <td className="text-end font-semibold text-[#2563EB]">{d.count}</td>
                                </tr>
                            ))}
                            {vehicle.filtered.length === 0 && (
                                <tr><td colSpan={2} className="py-6 text-center text-[#94A3B8]">Tidak ada data</td></tr>
                            )}
                        </tbody>
                    </table>
                </Panel>
            </div>
        </SectionPage>
    );
}
