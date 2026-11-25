import { router, usePage } from '@inertiajs/react';
import SectionPage from '@/Components/SectionPage';
import DataTable from '@/Components/DataTable';
import { TrashIcon } from '@heroicons/react/24/outline';

interface BeritaAcara {
    id: number;
    ba_number: string;
    source_type?: string | null;
    source?: string;
    auto?: boolean;
    trip_number: string;
    driver: string;
    vehicle: string;
    destination: string;
    purpose: string;
    departure_time: string;
    return_time: string;
    created_at: string;
}

interface Props {
    beritaAcaras: BeritaAcara[];
}

export default function AdminBeritaAcara({ beritaAcaras }: Props) {
    const user = usePage().props.auth.user as { role?: { name: string } | null };
    const isSuperAdmin = user?.role?.name === 'SUPERADMIN';

    const handleDelete = (ba: BeritaAcara) => {
        if (confirm(`Hapus Berita Acara ${ba.ba_number}?`)) {
            router.delete(route('admin.berita-acara.destroy', ba.id));
        }
    };

    const columns = [
        { key: 'ba_number', label: 'No. BA' },
        { key: 'trip_number', label: 'No. Trip' },
        {
            key: 'source',
            label: 'Sumber',
            render: (row: BeritaAcara) => (
                <span
                    className={`rounded-full px-2 py-0.5 text-[0.65rem] font-semibold ${
                        row.auto ? 'bg-[#EFF6FF] text-[#2563EB]' : 'bg-[#F1F5F9] text-[#64748B]'
                    }`}
                >
                    {row.auto ? 'Otomatis' : 'Manual'}
                </span>
            ),
        },
        { key: 'driver', label: 'Driver' },
        { key: 'vehicle', label: 'Kendaraan' },
        { key: 'destination', label: 'Tujuan' },
        { key: 'purpose', label: 'Keperluan' },
        { key: 'departure_time', label: 'Berangkat' },
        { key: 'created_at', label: 'Dibuat' },
        ...(isSuperAdmin
            ? [
                  {
                      key: 'actions',
                      label: 'Aksi',
                      render: (row: BeritaAcara) => (
                          <button
                              type="button"
                              onClick={() => handleDelete(row)}
                              className="inline-flex items-center justify-center rounded-lg border border-[#FEE2E2] bg-white p-1 text-[#DC2626] hover:bg-[#FEE2E2]"
                              title="Hapus"
                          >
                              <TrashIcon className="h-4 w-4" />
                          </button>
                      ),
                  },
              ]
            : []),
    ];

    return (
        <SectionPage title="Berita Acara">
            <DataTable
                data={beritaAcaras}
                columns={columns}
                searchPlaceholder="Cari berita acara (no. BA/driver/destinasi)... (tekan /)"
                emptyMessage="Tidak ada Berita Acara"
            />
        </SectionPage>
    );
}
