import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ShieldExclamationIcon, HomeIcon, ArrowLeftIcon } from '@heroicons/react/24/outline';

interface ErrorPageProps {
    status?: number;
    title?: string;
    description?: string;
}

const presets: Record<number, { title: string; description: string }> = {
    403: {
        title: 'Akses Ditolak',
        description: 'Anda tidak memiliki izin untuk mengakses halaman ini. Halaman tersebut hanya tersedia untuk pengguna atau peran tertentu.',
    },
    404: {
        title: 'Halaman Tidak Ditemukan',
        description: 'Halaman yang Anda cari tidak tersedia atau telah dipindahkan.',
    },
    419: {
        title: 'Sesi Berakhir',
        description: 'Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan muat ulang halaman dan coba lagi.',
    },
    500: {
        title: 'Terjadi Kesalahan',
        description: 'Terjadi kesalahan pada server saat memproses permintaan Anda. Silakan coba beberapa saat lagi.',
    },
    503: {
        title: 'Layanan Tidak Tersedia',
        description: 'Sistem sedang dalam pemeliharaan. Silakan coba lagi nanti.',
    },
};

export default function ErrorPage({ status = 403, title, description }: ErrorPageProps) {
    const preset = presets[status] ?? presets[403];
    const heading = title ?? preset.title;
    const body = description ?? preset.description;

    return (
        <AuthenticatedLayout header="Akses Ditolak">
            <Head title={`${status} — ${heading}`} />

            <div className="mmj-page-in flex min-h-[70vh] items-center justify-center px-4 py-12">
                <div className="w-full max-w-lg text-center">
                    {/* Icon */}
                    <div className="mmj-float-icon relative mx-auto mb-6 h-24 w-24">
                        <span className="absolute -inset-4 animate-pulse-subtle rounded-full bg-[#FEE2E2] blur-xl" />
                        <span className="relative flex h-24 w-24 items-center justify-center rounded-full border border-[#FECACA] bg-[#FEF2F2]">
                            <ShieldExclamationIcon className="h-11 w-11 text-[#DC2626]" />
                        </span>
                    </div>

                    {/* Status code */}
                    <p
                        className="animate-gradient bg-[linear-gradient(90deg,#DC2626,#F59E0B,#DC2626)] bg-clip-text text-6xl font-bold tracking-tight text-transparent"
                    >
                        {status}
                    </p>

                    {/* Title & description */}
                    <h1 className="mt-4 text-2xl font-bold tracking-tight text-[#0F172A] animate-rise [animation-delay:150ms]">
                        {heading}
                    </h1>
                    <p className="mx-auto mt-3 max-w-md text-sm leading-relaxed text-[#64748B] animate-rise [animation-delay:250ms]">
                        {body}
                    </p>

                    {/* Actions */}
                    <div className="mt-8 flex flex-wrap items-center justify-center gap-3 animate-rise [animation-delay:350ms]">
                        <Link href={route('dashboard')} className="mmj-btn mmj-btn-primary">
                            <HomeIcon className="h-4 w-4" />
                            Kembali ke Dashboard
                        </Link>
                        <button
                            type="button"
                            onClick={() => window.history.back()}
                            className="inline-flex items-center gap-2 rounded-[0.65rem] border border-[#E2E8F0] bg-white px-4 py-2 text-sm font-semibold text-[#64748B] transition hover:border-[#CBD5E1] hover:text-[#0F172A]"
                        >
                            <ArrowLeftIcon className="h-4 w-4" />
                            Halaman Sebelumnya
                        </button>
                    </div>

                    {/* Helpful hint for 403 */}
                    {status === 403 && (
                        <p className="mt-8 rounded-xl border border-[#E2E8F0] bg-[#F8FAFC] px-4 py-3 text-xs text-[#64748B]">
                            Bila menurut Anda seharusnya memiliki akses, hubungi <span className="font-semibold text-[#0F172A]">SuperAdmin</span> untuk mendapatkan izin.
                        </p>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
