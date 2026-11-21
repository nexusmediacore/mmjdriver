import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import SectionPage from '@/Components/SectionPage';
import { ChatBubbleLeftRightIcon, CheckCircleIcon } from '@heroicons/react/24/outline';

interface Props {
    whatsapp: {
        driver: string;
        token: string;
        endpoint: string;
        has_token: boolean;
        auto_send: boolean;
    };
}

const driverOptions = [
    {
        value: 'fonnte',
        label: 'Fonnte',
        desc: 'Gateway berbayar (Rekomendasi) — pesan terkirim OTOMATIS',
        accent: '#2563EB',
    },
    {
        value: 'wablas',
        label: 'Wablas',
        desc: 'Gateway berbayar — pesan terkirim OTOMATIS',
        accent: '#2563EB',
    },
    {
        value: 'link',
        label: 'Tautan wa.me',
        desc: 'Gratis — buka WhatsApp dengan pesan siap kirim (klik manual)',
        accent: '#F59E0B',
    },
    {
        value: 'log',
        label: 'Nonaktif',
        desc: 'Hanya catat ke log, tidak mengirim apa pun',
        accent: '#64748B',
    },
];

export default function AdminSettings({ whatsapp }: Props) {
    const { data, setData, post, processing, errors, recentlySuccessful } = useForm({
        whatsapp_driver: whatsapp.driver,
        whatsapp_token: whatsapp.token,
        whatsapp_endpoint: whatsapp.endpoint,
    });

    const [showToken, setShowToken] = useState(false);
    const activeOption = driverOptions.find((o) => o.value === data.whatsapp_driver) ?? driverOptions[2];

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.settings.update'), { preserveScroll: true });
    };

    return (
        <SectionPage
            title="Pengaturan Sistem"
            eyebrow="Administrasi"
            description="Konfigurasi integrasi notifikasi WhatsApp untuk penugasan driver."
        >
            <Head title="Pengaturan" />

            <form onSubmit={submit} className="space-y-5">
                {/* Status pengiriman */}
                <div
                    className="flex flex-col gap-3 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between"
                    style={{
                        borderColor: whatsapp.auto_send ? '#BBF7D0' : '#FDE68A',
                        backgroundColor: whatsapp.auto_send ? '#F0FDF4' : '#FFFBEB',
                    }}
                >
                    <div className="flex items-start gap-3">
                        <ChatBubbleLeftRightIcon
                            className="mt-0.5 h-5 w-5 flex-shrink-0"
                            style={{ color: whatsapp.auto_send ? '#16A34A' : '#B45309' }}
                        />
                        <div>
                            <p
                                className="text-sm font-semibold"
                                style={{ color: whatsapp.auto_send ? '#16A34A' : '#B45309' }}
                            >
                                {whatsapp.auto_send
                                    ? 'Pengiriman otomatis AKTIF'
                                    : 'Pengiriman otomatis BELUM AKTIF'}
                            </p>
                            <p className="mt-0.5 text-xs text-[#64748B]">
                                {whatsapp.auto_send
                                    ? 'Notifikasi tugas akan langsung terkirim ke WhatsApp driver saat ditugaskan.'
                                    : 'Pilih driver "Fonnte" atau "Wablas" dan isi Token agar pesan terkirim otomatis tanpa klik tombol.'}
                            </p>
                        </div>
                    </div>
                    {whatsapp.has_token && whatsapp.auto_send && (
                        <span className="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-[#DCFCE7] px-3 py-1 text-xs font-semibold text-[#16A34A]">
                            <CheckCircleIcon className="h-3.5 w-3.5" />
                            Token terpasang
                        </span>
                    )}
                </div>

                {/* Driver WhatsApp */}
                <div className="mmj-card p-5 sm:p-6">
                    <h3 className="text-base font-semibold text-[#0F172A]">Metode Pengiriman WhatsApp</h3>
                    <p className="mt-1 text-sm text-[#64748B]">
                        Tentukan bagaimana notifikasi tugas dikirim ke driver.
                    </p>

                    <div className="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        {driverOptions.map((opt) => {
                            const active = data.whatsapp_driver === opt.value;
                            const isGateway = opt.value === 'fonnte' || opt.value === 'wablas';

                            return (
                                <button
                                    key={opt.value}
                                    type="button"
                                    onClick={() => setData('whatsapp_driver', opt.value)}
                                    aria-pressed={active}
                                    className={`rounded-xl border-2 p-4 text-left transition-all duration-200 ${
                                        active ? 'shadow-md' : 'border-[#E2E8F0] bg-white hover:border-[#CBD5E1]'
                                    }`}
                                    style={active ? { borderColor: opt.accent, backgroundColor: opt.value === 'link' || opt.value === 'log' ? '#F8FAFC' : '#EFF6FF' } : undefined}
                                >
                                    <p className="flex items-center gap-2 text-sm font-semibold" style={{ color: active ? opt.accent : '#0F172A' }}>
                                        {opt.label}
                                        {isGateway && (
                                            <span className="rounded-full bg-[#DBEAFE] px-2 py-0.5 text-[0.6rem] font-bold text-[#2563EB]">
                                                OTOMATIS
                                            </span>
                                        )}
                                    </p>
                                    <p className="mt-1 text-xs text-[#64748B]">{opt.desc}</p>
                                </button>
                            );
                        })}
                    </div>
                    {errors.whatsapp_driver && (
                        <p className="mt-2 text-xs text-[#DC2626]">{errors.whatsapp_driver}</p>
                    )}
                </div>

                {/* Kredensial gateway */}
                {(data.whatsapp_driver === 'fonnte' || data.whatsapp_driver === 'wablas') && (
                    <div className="mmj-card animate-slide-up p-5 sm:p-6">
                        <h3 className="text-base font-semibold text-[#0F172A]">Kredensial Gateway</h3>
                        <p className="mt-1 text-sm text-[#64748B]">
                            Daftar di {data.whatsapp_driver === 'fonnte' ? 'fonnte.com' : 'wablas.com'}, lalu salin Token dari dashboard Anda.
                        </p>

                        <div className="mt-4 space-y-4">
                            <div>
                                <label className="mmx-label">Token Gateway <span className="text-red-500">*</span></label>
                                <div className="relative">
                                    <input
                                        type={showToken ? 'text' : 'password'}
                                        value={data.whatsapp_token}
                                        onChange={(e) => setData('whatsapp_token', e.target.value)}
                                        placeholder="Tempel token di sini"
                                        className="mmj-input pr-24"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowToken((v) => !v)}
                                        className="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg border border-[#E2E8F0] px-2.5 py-1 text-xs font-semibold text-[#64748B] transition hover:bg-[#F8FAFC]"
                                    >
                                        {showToken ? 'Sembunyikan' : 'Tampilkan'}
                                    </button>
                                </div>
                                {errors.whatsapp_token && (
                                    <p className="mt-1 text-xs text-[#DC2626]">{errors.whatsapp_token}</p>
                                )}
                            </div>

                            <div>
                                <label className="mmx-label">Endpoint</label>
                                <input
                                    type="text"
                                    value={data.whatsapp_endpoint}
                                    onChange={(e) => setData('whatsapp_endpoint', e.target.value)}
                                    className="mmj-input"
                                />
                                <p className="mt-1 text-xs text-[#94A3B8]">
                                    Fonnte: https://api.fonnte.com/send — Wablas: lihat dokumentasi akun Anda.
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                <div className="flex items-center justify-end gap-3">
                    {recentlySuccessful && (
                        <span className="inline-flex items-center gap-1.5 text-sm font-medium text-[#16A34A]">
                            <CheckCircleIcon className="h-4 w-4" />
                            Tersimpan
                        </span>
                    )}
                    <button type="submit" disabled={processing} className="mmj-btn mmj-btn-primary">
                        {processing ? 'Menyimpan...' : 'Simpan Pengaturan'}
                    </button>
                </div>
            </form>
        </SectionPage>
    );
}
