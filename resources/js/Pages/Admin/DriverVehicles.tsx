import { useState } from 'react';
import { Head, router, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import SearchInput from '@/Components/UI/SearchInput';
import Toggle from '@/Components/UI/Toggle';
import { useTableSearch } from '@/Hooks/useTableSearch';
import { TruckIcon } from '@heroicons/react/24/outline';

interface Vehicle {
    id: number;
    plate_number: string;
    brand: string;
    model: string;
    type: string;
    status: string;
}

interface Driver {
    id: number;
    name: string;
    status: string;
}

interface Props {
    driver: Driver;
    vehicles: Vehicle[];
    allowed_vehicle_ids: number[];
}

export default function DriverVehicles({ driver, vehicles, allowed_vehicle_ids }: Props) {
    const [selectedIds, setSelectedIds] = useState<number[]>(allowed_vehicle_ids);
    const { filtered, query, setQuery } = useTableSearch<Vehicle>(vehicles, [
        'plate_number',
        'brand',
        'model',
        'type',
        'status',
    ]);

    const toggleVehicle = (id: number) => {
        setSelectedIds((prev) =>
            prev.includes(id) ? prev.filter((v) => v !== id) : [...prev, id]
        );
    };

    const handleSubmit = () => {
        router.post(route('admin.drivers.vehicles', driver.id), {
            vehicle_ids: selectedIds,
        }, {
            preserveScroll: true,
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-[#0F172A]">
                        Kendaraan yang Diizinkan - {driver.name}
                    </h2>
                    <a
                        href={route('admin.drivers')}
                        className="text-sm text-[#64748B] hover:text-[#0F172A]"
                    >
                        Kembali ke Daftar Driver
                    </a>
                </div>
            }
        >
            <Head title={`Kendaraan - ${driver.name}`} />

            <div className="py-12">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-4 rounded-lg bg-white p-6 shadow">
                        <h3 className="text-lg font-semibold text-[#0F172A]">Informasi Driver</h3>
                        <dl className="mt-2 space-y-1 text-sm">
                            <div className="flex">
                                <dt className="w-40 text-[#64748B]">Nama</dt>
                                <dd className="text-[#0F172A]">{driver.name}</dd>
                            </div>
                            <div className="flex">
                                <dt className="w-40 text-[#64748B]">Status</dt>
                                <dd className="text-[#0F172A]">{driver.status}</dd>
                            </div>
                        </dl>
                    </div>

                    <div className="rounded-lg bg-white p-6 shadow">
                        <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <h3 className="text-lg font-semibold text-[#0F172A]">
                                Kendaraan yang Diizinkan
                            </h3>
                            <span className="text-xs font-medium text-[#94A3B8]">
                                {filtered.length} dari {vehicles.length} kendaraan
                            </span>
                        </div>
                        <p className="mb-4 text-sm text-[#64748B]">
                            Pilih kendaraan yang diperbolehkan untuk driver ini.
                        </p>

                        <SearchInput
                            value={query}
                            onChange={setQuery}
                            placeholder="Cari kendaraan (plat/merk/model)... (tekan /)"
                            className="mb-4 max-w-md"
                            resultInfo={
                                query.trim()
                                    ? `Ditemukan ${filtered.length} kendaraan`
                                    : undefined
                            }
                        />

                        <div className="space-y-2.5">
                            {filtered.map((vehicle) => {
                                const isAllowed = selectedIds.includes(vehicle.id);
                                return (
                                    <button
                                        key={vehicle.id}
                                        type="button"
                                        onClick={() => toggleVehicle(vehicle.id)}
                                        className={`group flex w-full items-center gap-4 rounded-xl border p-3.5 text-left transition-all duration-200 ${
                                            isAllowed
                                                ? 'border-[#BFDBFE] bg-[#EFF6FF]/60 hover:border-[#93C5FD] hover:bg-[#EFF6FF]'
                                                : 'border-[#E2E8F0] bg-white hover:border-[#CBD5E1] hover:bg-[#F8FAFC]'
                                        }`}
                                    >
                                        <span
                                            className={`flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg transition-colors duration-200 ${
                                                isAllowed ? 'bg-[#DBEAFE] text-[#2563EB]' : 'bg-[#F1F5F9] text-[#94A3B8]'
                                            }`}
                                        >
                                            <TruckIcon className="h-5 w-5" />
                                        </span>

                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-semibold text-[#0F172A]">
                                                {vehicle.brand} {vehicle.model}
                                            </p>
                                            <p className="mt-0.5 truncate text-xs text-[#64748B]">
                                                {vehicle.plate_number} • {vehicle.type}
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
                                                onChange={() => toggleVehicle(vehicle.id)}
                                                label={`${isAllowed ? 'Nonaktifkan' : 'Izinkan'} ${vehicle.brand} ${vehicle.model}`}
                                            />
                                        </span>
                                    </button>
                                );
                            })}

                            {filtered.length === 0 && (
                                <div className="py-8 text-center">
                                    <p className="text-sm text-[#94A3B8]">
                                        Tidak ada kendaraan yang cocok dengan pencarian.
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
