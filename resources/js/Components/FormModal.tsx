import { ReactNode, useEffect, useState } from 'react';
import { createPortal } from 'react-dom';
import { router, usePage } from '@inertiajs/react';

interface FormField {
    name: string;
    label: string;
    type?: 'text' | 'email' | 'password' | 'number' | 'select' | 'textarea' | 'date' | 'multicheck';
    options?: Array<{ value: string; label: string }>;
    required?: boolean;
    default?: string | number | boolean;
    placeholder?: string;
    hint?: string;
}

interface FormModalProps {
    title: string;
    fields: FormField[];
    onSubmitUrl?: string;
    /** Resolver URL saat submit — dipakai agar URL tidak dihitung selama render */
    getSubmitUrl?: () => string;
    method?: 'post' | 'put';
    isOpen: boolean;
    onClose: () => void;
    data?: Record<string, any>;
    includePassword?: boolean;
}

export default function FormModal({
    title,
    fields,
    onSubmitUrl,
    getSubmitUrl,
    method = 'post',
    isOpen,
    onClose,
    data,
    includePassword = false,
}: FormModalProps) {
    const [formData, setFormData] = useState<Record<string, any>>(
        data ?? {}
    );

    // Error validasi dari Laravel (dikirim balik saat request gagal 422)
    const pageErrors = usePage().props.errors as Record<string, string> | undefined;
    const errors = pageErrors ?? {};
    const hasErrors = Object.keys(errors).length > 0;

    // Kunci scroll latar belakang selama modal terbuka.
    // Tanpa ini, halaman di belakang modal tetap bisa di-scroll sehingga
    // modal (meski position:fixed) ikut bergeser bila ada ancestor
    // yang memiliki transform/filter.
    useEffect(() => {
        if (!isOpen) return;

        const { overflow, paddingRight } = document.body.style;
        const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;

        document.body.style.overflow = 'hidden';
        if (scrollbarWidth > 0) {
            document.body.style.paddingRight = `${scrollbarWidth}px`;
        }

        const onKeyDown = (e: KeyboardEvent) => {
            if (e.key === 'Escape') onClose();
        };
        document.addEventListener('keydown', onKeyDown);

        return () => {
            document.body.style.overflow = overflow;
            document.body.style.paddingRight = paddingRight;
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [isOpen, onClose]);

    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
        const { name, value } = e.target;
        const type = (e.target as HTMLInputElement).type;
        const checked = (e.target as HTMLInputElement).checked;
        setFormData((prev) => ({
            ...prev,
            [name]: type === 'checkbox' ? checked : value,
        }));
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const url = getSubmitUrl ? getSubmitUrl() : (onSubmitUrl ?? '');
        if (!url) return;

        // Serialisasi angka sebagai string agar cocok dengan value opsi <select>
        const payload = Object.fromEntries(
            Object.entries(formData).map(([k, v]) => [k, typeof v === 'number' ? String(v) : v])
        );

        if (method === 'post') {
            router.post(url, payload, {
                onSuccess: onClose,
            });
        } else {
            router.put(url, payload, {
                onSuccess: onClose,
            });
        }
    };

    if (!isOpen) return null;

    // Portal ke <body> adalah kunci perbaikan: modal keluar dari rantai
    // ancestor yang memiliki transform/filter/overflow, sehingga benar-benar
    // menempel pada viewport dan tidak ikut ter-scroll bersama halaman.
    return createPortal(
        <div
            className="fixed inset-0 z-[9999] flex items-start justify-center overflow-y-auto bg-black/50 p-4 backdrop-blur-sm sm:items-center"
            role="dialog"
            aria-modal="true"
            onMouseDown={onClose}
        >
            <div
                className="my-auto w-full max-w-lg animate-modal-in rounded-2xl bg-white shadow-2xl"
                onMouseDown={(e) => e.stopPropagation()}
            >
                {/* Header */}
                <div className="flex items-center justify-between border-b border-[#E2E8F0] px-6 py-4">
                    <h2 className="text-lg font-semibold text-[#0F172A]">{title}</h2>
                    <button
                        type="button"
                        onClick={onClose}
                        aria-label="Tutup"
                        className="flex h-8 w-8 items-center justify-center rounded-lg text-[#94A3B8] transition hover:bg-[#F1F5F9] hover:text-[#0F172A]"
                    >
                        <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="space-y-4 px-6 py-5">
                    {hasErrors && (
                        <div className="rounded-lg border border-[#FECACA] bg-[#FEF2F2] p-3">
                            <div className="flex items-start gap-2">
                                <svg className="mt-0.5 h-4 w-4 flex-shrink-0 text-[#DC2626]" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                </svg>
                                <div>
                                    <p className="text-sm font-semibold text-[#DC2626]">Gagal menyimpan. Perbaiki kesalahan berikut:</p>
                                    <ul className="mt-1.5 list-inside list-disc space-y-0.5 text-xs text-[#B91C1C]">
                                        {Object.entries(errors).map(([field, message]) => (
                                            <li key={field}>{message}</li>
                                        ))}
                                    </ul>
                                </div>
                            </div>
                        </div>
                    )}

                    {fields.map((field) => (
                        <div key={field.name}>
                            <label className="mb-1 block text-sm font-medium text-[#0F172A]">
                                {field.label}
                                {field.required && <span className="text-[#DC2626]"> *</span>}
                            </label>
                            {field.type === 'multicheck' ? (
                                <div className="max-h-48 space-y-1 overflow-y-auto rounded-lg border border-[#E2E8F0] p-2">
                                    {(field.options ?? []).length === 0 && (
                                        <p className="px-1 py-1 text-sm text-[#94A3B8]">Tidak ada user</p>
                                    )}
                                    {(field.options ?? []).map((opt) => {
                                        const selected: string[] = Array.isArray(formData[field.name])
                                            ? formData[field.name].map(String)
                                            : [];
                                        const checked = selected.includes(String(opt.value));
                                        return (
                                            <label
                                                key={opt.value}
                                                className="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-sm text-[#0F172A] transition hover:bg-[#F1F5F9]"
                                            >
                                                <input
                                                    type="checkbox"
                                                    checked={checked}
                                                    onChange={(e) => {
                                                        setFormData((prev) => {
                                                            const current: string[] = Array.isArray(prev[field.name])
                                                                ? prev[field.name].map(String)
                                                                : [];
                                                            const next = e.target.checked
                                                                ? [...current, String(opt.value)]
                                                                : current.filter((v) => v !== String(opt.value));
                                                            return { ...prev, [field.name]: next };
                                                        });
                                                    }}
                                                    className="h-4 w-4 rounded border-[#CBD5E1] text-[#2563EB] focus:ring-[#2563EB]/30"
                                                />
                                                {opt.label}
                                            </label>
                                        );
                                    })}
                                </div>
                            ) : field.type === 'select' ? (
                                <select
                                    name={field.name}
                                    value={String(formData[field.name] ?? '')}
                                    onChange={handleChange}
                                    disabled={!field.options?.length}
                                    className={`w-full rounded-lg border px-3 py-2 text-sm focus:outline-none disabled:cursor-not-allowed disabled:bg-[#F1F5F9] disabled:text-[#94A3B8] ${
                                        errors[field.name]
                                            ? 'border-[#FCA5A5] focus:border-[#DC2626] focus:ring-2 focus:ring-[#DC2626]/15'
                                            : 'border-[#E2E8F0] focus:border-[#2563EB] focus:ring-4 focus:ring-[#2563EB]/10'
                                    }`}
                                    required={field.required}
                                >
                                    <option value="">{field.placeholder ?? 'Pilih...'}</option>
                                    {field.options?.map((opt) => (
                                        <option key={opt.value} value={opt.value}>
                                            {opt.label}
                                        </option>
                                    ))}
                                </select>
                            ) : field.type === 'textarea' ? (
                                <textarea
                                    name={field.name}
                                    value={formData[field.name] ?? ''}
                                    onChange={handleChange}
                                    placeholder={field.placeholder}
                                    className={`w-full rounded-lg border px-3 py-2 text-sm focus:outline-none ${
                                        errors[field.name]
                                            ? 'border-[#FCA5A5] focus:border-[#DC2626] focus:ring-2 focus:ring-[#DC2626]/15'
                                            : 'border-[#E2E8F0] focus:border-[#2563EB]'
                                    }`}
                                    required={field.required}
                                    rows={3}
                                />
                            ) : (
                                <input
                                    type={field.type ?? 'text'}
                                    name={field.name}
                                    value={formData[field.name] ?? ''}
                                    onChange={handleChange}
                                    placeholder={field.placeholder}
                                    className={`w-full rounded-lg border px-3 py-2 text-sm focus:outline-none ${
                                        errors[field.name]
                                            ? 'border-[#FCA5A5] focus:border-[#DC2626] focus:ring-2 focus:ring-[#DC2626]/15'
                                            : 'border-[#E2E8F0] focus:border-[#2563EB] focus:ring-4 focus:ring-[#2563EB]/10'
                                    }`}
                                    required={field.required}
                                />
                            )}
                            {errors[field.name] && (
                                <p className="mt-1 text-xs font-medium text-[#DC2626]">{errors[field.name]}</p>
                            )}
                            {field.hint && <p className="mt-1 text-xs text-[#94A3B8]">{field.hint}</p>}
                        </div>
                    ))}

                    {includePassword && (
                        <>
                            <div>
                                <label className="mb-1 block text-sm font-medium text-[#0F172A]">
                                    Password
                                </label>
                                <input
                                    type="password"
                                    name="password"
                                    value={formData.password ?? ''}
                                    onChange={handleChange}
                                    className="w-full rounded-lg border border-[#E2E8F0] px-3 py-2 text-sm focus:border-[#2563EB] focus:outline-none"
                                />
                            </div>
                            <div>
                                <label className="mb-1 block text-sm font-medium text-[#0F172A]">
                                    Konfirmasi Password
                                </label>
                                <input
                                    type="password"
                                    name="password_confirmation"
                                    value={formData.password_confirmation ?? ''}
                                    onChange={handleChange}
                                    className="w-full rounded-lg border border-[#E2E8F0] px-3 py-2 text-sm focus:border-[#2563EB] focus:outline-none"
                                />
                            </div>
                        </>
                    )}

                    <div className="flex justify-end gap-3 border-t border-[#E2E8F0] pt-4">
                        <button
                            type="button"
                            onClick={onClose}
                            className="rounded-lg border border-[#E2E8F0] bg-white px-4 py-2 text-sm font-medium text-[#64748B] transition hover:border-[#CBD5E1] hover:text-[#0F172A]"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            className="mmj-btn mmj-btn-primary"
                        >
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>,
        document.body
    );
}
