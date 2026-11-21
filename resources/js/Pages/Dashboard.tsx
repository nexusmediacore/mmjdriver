import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/UI/PageHeader';
import Panel from '@/Components/UI/Panel';
import { CheckCircleIcon, ArrowRightIcon } from '@heroicons/react/24/outline';

export default function Dashboard() {
    return (
        <AuthenticatedLayout header="Dashboard">
            <Head title="Dashboard" />

            <div className="py-8 sm:py-10">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <PageHeader
                        eyebrow="MMJ Driver"
                        title="Selamat Datang"
                        description="Sistem manajemen dispatch driver terpusat untuk seluruh perjalanan dinas."
                    />

                    <Panel
                        title="Status Akun"
                        description="Sesi Anda aktif"
                        icon={<CheckCircleIcon className="h-4 w-4" />}
                    >
                        <div className="flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div className="flex items-center gap-3">
                                <span className="flex h-12 w-12 items-center justify-center rounded-full bg-[#F0FDF4]">
                                    <CheckCircleIcon className="h-6 w-6 text-[#16A34A]" />
                                </span>
                                <div>
                                    <p className="text-sm font-semibold text-[#0F172A]">Anda berhasil masuk ke sistem</p>
                                    <p className="text-xs text-[#64748B]">Gunakan menu navigasi untuk mulai mengelola perjalanan.</p>
                                </div>
                            </div>
                            <Link
                                href={route('dashboard')}
                                className="mmj-btn mmj-btn-primary"
                            >
                                Buka Dashboard
                                <ArrowRightIcon className="h-4 w-4" />
                            </Link>
                        </div>
                    </Panel>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
