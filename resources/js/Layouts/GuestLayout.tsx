import { PropsWithChildren } from 'react';
import ApplicationLogo from '@/Components/ApplicationLogo';



export default function GuestLayout({ children }: PropsWithChildren) {
    return (
        <div className="flex min-h-screen">
            {/* ===== Left Panel: Visual ===== */}
            <div className="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-[#0B1220] p-12 lg:flex">
                {/* Animated aurora gradient base */}
                <div className="absolute inset-0 animate-gradient bg-[linear-gradient(120deg,#0B1220_0%,#16233D_30%,#1E3A8A_55%,#2563EB_75%,#0B1220_100%)] bg-[length:300%_300%]" />

                {/* Floating gradient blobs */}
                <div className="pointer-events-none absolute -left-20 top-10 h-80 w-80 animate-float-slow rounded-full bg-[#3B82F6]/35 blur-3xl" />
                <div className="pointer-events-none absolute -right-24 top-1/3 h-96 w-96 animate-float-slower rounded-full bg-[#0EA5E9]/25 blur-3xl" />
                <div className="pointer-events-none absolute bottom-0 left-1/4 h-72 w-72 animate-float-slow rounded-full bg-[#6366F1]/25 blur-3xl [animation-delay:3s]" />

                {/* Grid overlay */}
                <div
                    className="absolute inset-0 opacity-[0.06]"
                    style={{
                        backgroundImage: `linear-gradient(rgba(255,255,255,0.4) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.4) 1px, transparent 1px)`,
                        backgroundSize: '48px 48px',
                    }}
                />

                {/* Radial vignette for depth */}
                <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_center,transparent_35%,rgba(11,18,32,0.55)_100%)]" />

                {/* Logo block - vertically centered */}
                <div className="relative z-10 flex flex-1 flex-col items-center justify-center">
                    {/* Logo glow ring */}
                    <div className="relative">
                        <div className="absolute -inset-6 animate-pulse-subtle rounded-full bg-[#3B82F6]/20 blur-2xl" />
                        <div className="absolute -inset-1 rounded-full border border-white/15" />
                        <div className="relative flex h-44 w-44 items-center justify-center rounded-full border border-white/20 bg-white/10 shadow-[0_0_60px_-12px_rgba(59,130,246,0.55)] backdrop-blur-sm">
                            <ApplicationLogo className="h-28 w-auto object-contain animate-logo-pop" />
                        </div>
                    </div>

                    <span className="mt-8 text-3xl font-bold tracking-tight text-white animate-rise [animation-delay:250ms]">MMJ DRIVER</span>
                    <span className="mt-2 text-sm font-light uppercase tracking-[0.3em] text-[#93C5FD] animate-rise [animation-delay:400ms]">
                        Dispatch System
                    </span>
                    <span className="mt-6 block h-1 rounded-full bg-gradient-to-r from-[#3B82F6] to-[#38BDF8] animate-line-grow" />
                </div>

                {/* Footer highlight */}
                <div className="relative z-10 space-y-6">
                    <div className="flex items-center gap-3 animate-rise [animation-delay:500ms]">
                        <div className="h-9 w-9 rounded-full bg-white/10 flex items-center justify-center border border-white/15">
                            <svg className="h-4 w-4 text-[#93C5FD]" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 11c0-1 .9-2 2-2s2 1 2 2a2 2 0 01-2 2 2 2 0 01-2-2zM3 11a9 9 0 0118 0M3 11v6a2 2 0 002 2h14a2 2 0 002-2v-6" />
                            </svg>
                        </div>
                        <div>
                            <p className="text-sm font-medium text-white">Driver Dispatch System</p>
                            <p className="text-xs text-white/50">Internal Enterprise Platform</p>
                        </div>
                    </div>
                </div>
            </div>

            {/* ===== Right Panel: Login Card ===== */}
            <div className="relative flex flex-1 items-center justify-center overflow-hidden bg-[#F8FAFC] p-6 lg:p-12">
                {/* Soft background accents */}
                <div className="pointer-events-none absolute -right-32 -top-32 h-96 w-96 rounded-full bg-[#DBEAFE]/60 blur-3xl" />
                <div className="pointer-events-none absolute -bottom-32 -left-32 h-96 w-96 rounded-full bg-[#E0E7FF]/50 blur-3xl" />

                <div className="relative w-full max-w-md">
                    {/* Card with pop-in + shine sweep */}
                    <div className="relative overflow-hidden rounded-xl border border-[#E2E8F0] bg-white p-8 shadow-[0_1px_3px_-1px_rgba(15,23,42,0.06)] animate-modal-in">
                        {/* Accent top border */}
                        <div className="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[#2563EB] via-[#38BDF8] to-[#6366F1]" />
                        {/* Shine sweep */}
                        <div className="pointer-events-none absolute inset-y-0 -left-1/2 w-1/3 animate-shine bg-gradient-to-r from-transparent via-white/40 to-transparent" />

                        <div className="text-center">
                            <div className="lg:hidden mb-6 flex flex-col items-center">
                                <div className="relative">
                                    <div className="absolute -inset-3 rounded-full bg-[#DBEAFE] blur-xl" />
                                    <div className="relative flex h-20 w-20 items-center justify-center rounded-full border border-[#BFDBFE] bg-[#EFF6FF]">
                                        <ApplicationLogo className="h-12 w-auto object-contain animate-logo-pop" />
                                    </div>
                                </div>
                                <span className="mt-4 text-xl font-bold text-[#0F172A]">MMJ DRIVER</span>
                                <span className="mt-1 text-[0.65rem] uppercase tracking-[0.25em] text-[#64748B]">Dispatch System</span>
                            </div>
                            <h1 className="text-2xl font-semibold tracking-tight text-[#0F172A]">Selamat Datang</h1>
                            <p className="mt-1.5 text-sm text-[#64748B]">Masukkan kredensial Anda untuk mengakses sistem</p>
                        </div>
                        {children}
                    </div>
                    <p className="mt-6 text-center text-xs text-[#94A3B8]">Dilindungi oleh Laravel &amp; Inertia.js</p>
                </div>
            </div>
        </div>
    );
}
