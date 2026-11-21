import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import SearchInput from '@/Components/UI/SearchInput';
import Toggle from '@/Components/UI/Toggle';
import { useTableSearch } from '@/Hooks/useTableSearch';

interface Driver {
    id: number;
    name: string;
    license_number: string;
    phone: string;
    status: string;
}

interface Vehicle {
    id: number;
    plate_number: string;
    brand: string;
    model: string;
    type: string;
    status: string;
}

interface Props {
    vehicle: Vehicle;
    drivers: Driver[];
    authorized_driver_ids: number[];
}

export default function VehicleDrivers({ vehicle, drivers, authorized_driver_ids }: Props) {
    const [selectedIds, setSelectedIds] = useState<number[]>(authorized_driver_ids);
    const { filtered, query, setQuery } = useTableSearch<Driver>(drivers, [
        'name',
        'license_number',
        'phone',
        'status',
    ]);

    const toggleDriver = (id: number) => {
        setSelectedIds((prev) =>
            prev.includes(id) ? prev.filter((d) => d !== id) : [...prev, id]
        );
    };

    const handleSubmit = () => {
        router.post(route('admin.vehicles.drivers', vehicle.id), {
            driver_ids: selectedIds,
        }, {
            preserveScroll: true,
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-[#0F172A]">
                        Driver yang Diizinkan - {vehicle.brand} {vehicle.model}
                    </h2>
                    <a
                        href={route('admin.vehicles')}
                        className="text-sm text-[#64748B] hover:text-[#0F172A]"
                    >
                        Kembali ke Daftar Kendaraan
                    </a>
                </div>
            }
        >
            <Head title={`Driver - ${vehicle.brand} ${vehicle.model}`} />

            <div className="py-12">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-4 rounded-lg bg-white p-6 shadow">
                        <h3 className="text-lg font-semibold text-[#0F172A]">Informasi Kendaraan</h3>
                        <dl className="mt-2 space-y-1 text-sm">
                            <div className="flex">
                                <dt className="w-40 text-[#64748B]">Merk & Model</dt>
                                <dd className="text-[#0F172A]">{vehicle.brand} {vehicle.model}</dd>
                            </div>
                            <div className="flex">
                                <dt className="w-40 text-[#64748B]">Plat Nomor</dt>
                                <dd className="text-[#0F172A]">{vehicle.plate_number}</dd>
                            </div>
                            <div className="flex">
                                <dt className="w-40 text-[#64748B]">Status</dt>
                                <dd className="text-[#0F172A]">{vehicle.status}</dd>
                            </div>
                        </dl>
                    </div>

                    <div className="rounded-lg bg-white p-6 shadow">
                        <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <h3 className="text-lg font-semibold text-[#0F172A]">
                                Driver yang Diizinkan
                            </h3>
                            <span className="text-xs font-medium text-[#94A3B8]">
                                {filtered.length} dari {drivers.length} driver
                            </span>
                        </div>
                        <p className="mb-4 text-sm text-[#64748B]">
                            Pilih driver yang diperbolehkan menggunakan kendaraan ini.
                        </p>

                        <SearchInput
                            value={query}
                            onChange={setQuery}
                            placeholder="Cari driver (nama/lisensi/telepon)... (tekan /)"
                            className="mb-4 max-w-md"
                            resultInfo={query.trim() ? `Ditemukan ${filtered.length} driver` : undefined}
                        />

                        <div className="space-y-2.5">
                            {filtered.map((driver) => {
                                const isAllowed = selectedIds.includes(driver.id);
                                return (
                                    <button
                                        key={driver.id}
                                        type="button"
                                        onClick={() => toggleDriver(driver.id)}
                                        className={`group flex w-full items-center gap-4 rounded-xl border p-3.5 text-left transition-all duration-200 ${
                                            isAllowed
                                                ? 'border-[#BFDBFE] bg-[#EFF6FF]/60 hover:border-[#93C5FD] hover:bg-[#EFF6FF]'
                                                : 'border-[#E2E8F0] bg-white hover:border-[#CBD5E1] hover:bg-[#F8FAFC]'
                                        }`}
                                    >
                                        <span
                                            className={`flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full text-[0.7rem] font-bold transition-colors duration-200 ${
                                                isAllowed
                                                    ? 'bg-gradient-to-br from-[#2563EB] to-[#6366F1] text-white'
                                                    : 'bg-[#F1F5F9] text-[#94A3B8]'
                                            }`}
                                        >
                                            {driver.name?.charAt(0)?.toUpperCase() ?? '?'}
                                        </span>

                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-semibold text-[#0F172A]">{driver.name}</p>
                                            <p className="mt-0.5 truncate text-xs text-[#64748B]">
                                                {driver.license_number}
                                                {driver.phone ? ` • ${driver.phone}` : ''}
                                            </p>
                                        </div>

                                        <span
                                            className={`hidden shrink-0 rounded-full px-2.5 py-1 text-[0.7rem] font-semibold sm:inline-block ${
                                                isAllowed ? 'bg-[#DCFCE7] text-[#16A34A]' : 'bg-[#F1F5F9] text-[#94A3B8]'
                                            }`}
                                        >
                                            {isAllowed ? 'Diizinkan' : 'Tidak Diizinkan'}
                                        </span>

                                        <span className="shrink-0" onClick={(e) => e.stopPropagation()}>
                                            <Toggle
                                                checked={isAllowed}
                                                onChange={() => toggleDriver(driver.id)}
                                                label={`${isAllowed ? 'Nonaktifkan' : 'Izinkan'} driver ${driver.name}`}
                                            />
                                        </span>
                                    </button>
                                );
                            })}

                            {filtered.length === 0 && (
                                <div className="py-8 text-center">
                                    <p className="text-sm text-[#94A3B8]">
                                        Tidak ada driver yang cocok dengan pencarian.
                                    </p>
                                </div>
                            )}
                        </div>

                        <div className="mt-6 flex justify-end gap-3 border-t border-[#E2E8F0] pt-4">
                            <button
                                type="button"
                                onClick={handleSubmit}
                                className="mmj-btn mmj-btn-primary"
                            >
                                Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
