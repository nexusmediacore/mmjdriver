import { useState, ReactNode } from 'react';
import Dropdown from '@/Components/Dropdown';
import NotificationBell from '@/Components/NotificationBell';
import Sidebar from '@/Components/Sidebar';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { Link, usePage } from '@inertiajs/react';

function TopLink({ href, active, children }: { href: string; active: boolean; children: ReactNode }) {
    return (
        <Link
            href={href}
            className={`mmj-navlink ${active ? 'mmj-navlink-active' : ''}`}
        >
            {children}
        </Link>
    );
}

export default function Authenticated({ header, children }: { header?: ReactNode; children: ReactNode }) {
    const user = usePage().props.auth.user as { name?: string; email?: string; role?: { name: string } | null };
    const flash = usePage().props.flash as {
        success?: string;
        error?: string;
        info?: string;
        warning?: string;
        whatsapp_link?: string;
    };
    const [showingNavigationDropdown, setShowingNavigationDropdown] = useState(false);

    const initials = (user?.name ?? 'U')
        .split(' ')
        .map((n) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();

    return (
        <div className="mmj-canvas flex h-screen overflow-hidden">
            <Sidebar />

            <div className="flex h-full min-w-0 flex-1 flex-col overflow-hidden">
                {/* Premium topbar */}
                <nav className="mmj-topbar flex-shrink-0">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div className="flex h-16 items-center justify-between gap-4">
                            {/* Left: page header + quick links */}
                            <div className="flex min-w-0 items-center gap-6">
                                <div className="hidden truncate text-sm font-semibold text-[#0F172A] md:block">
                                    {(header as { props?: { children?: string } } | undefined)?.props?.children ?? 'Dashboard'}
                                </div>
                                <div className="hidden items-center gap-7 sm:flex">
                                    <TopLink href={route('dashboard')} active={route().current('dashboard')}>
                                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                                        </svg>
                                        Dashboard
                                    </TopLink>
                                    <TopLink href={route('trips.index')} active={route().current('trips.*')}>
                                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                                        </svg>
                                        Perjalanan
                                    </TopLink>
                                    {user?.role?.name !== 'DRIVER' && (
                                    <TopLink href={route('reports.index')} active={route().current('reports.*')}>
                                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                        </svg>
                                        Laporan
                                    </TopLink>
                                    )}
                                </div>
                            </div>

                            {/* Right: role chip + notifications + user */}
                            <div className="flex items-center gap-3">
                                <NotificationBell />
                                {user?.role?.name && (
                                    <span className="hidden rounded-full border border-[#BFDBFE] bg-[#EFF6FF] px-2.5 py-1 text-[0.7rem] font-semibold text-[#2563EB] sm:inline-block">
                                        {user.role.name}
                                    </span>
                                )}
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <button type="button" className="flex items-center gap-2.5 rounded-full border border-[#E2E8F0] bg-white py-1 pl-1 pr-3 transition hover:border-[#BFDBFE] hover:shadow-[0_2px_8px_-2px_rgba(37,99,235,0.25)]">
                                            <span className="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#2563EB] to-[#6366F1] text-[0.7rem] font-bold text-white">
                                                {initials}
                                            </span>
                                            <span className="hidden text-sm font-semibold text-[#0F172A] sm:block">{user?.name}</span>
                                            <svg className="-me-0.5 h-4 w-4 text-[#94A3B8]" viewBox="0 0 20 20" fill="currentColor">
                                                <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd" />
                                            </svg>
                                        </button>
                                    </Dropdown.Trigger>
                                    <Dropdown.Content>
                                        <Dropdown.Link href={route('profile.edit')}>Profile</Dropdown.Link>
                                        <Dropdown.Link href={route('logout')} method="post" as="button">Log Out</Dropdown.Link>
                                    </Dropdown.Content>
                                </Dropdown>
                                <button
                                    onClick={() => setShowingNavigationDropdown((p) => !p)}
                                    className="inline-flex items-center justify-center rounded-lg p-2 text-[#64748B] transition hover:bg-[#F1F5F9] hover:text-[#0F172A] sm:hidden"
                                >
                                    <svg className="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                        <path className={!showingNavigationDropdown ? 'inline-flex' : 'hidden'} strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
                                        <path className={showingNavigationDropdown ? 'inline-flex' : 'hidden'} strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Mobile nav */}
                    <div className={(showingNavigationDropdown ? 'block ' : 'hidden ') + 'border-t border-[#E2E8F0] bg-white sm:hidden'}>
                        <div className="space-y-1 px-4 pb-3 pt-2">
                            <ResponsiveNavLink href={route('dashboard')} active={route().current('dashboard')}>Dashboard</ResponsiveNavLink>
                            <ResponsiveNavLink href={route('trips.index')} active={route().current('trips.*')}>Perjalanan</ResponsiveNavLink>
                            {user?.role?.name !== 'DRIVER' && (
                                <ResponsiveNavLink href={route('reports.index')} active={route().current('reports.*')}>Laporan</ResponsiveNavLink>
                            )}
                        </div>
                        <div className="border-t border-[#E2E8F0] px-4 pb-2 pt-4">
                            <div className="text-base font-semibold text-[#0F172A]">{user?.name}</div>
                            <div className="text-sm text-[#64748B]">{user?.email}</div>
                            <div className="mt-3 space-y-1">
                                <ResponsiveNavLink href={route('profile.edit')}>Profile</ResponsiveNavLink>
                                <ResponsiveNavLink method="post" href={route('logout')} as="button">Log Out</ResponsiveNavLink>
                            </div>
                        </div>
                    </div>
                </nav>

                {/* Flash messages */}
                {(flash?.success || flash?.error || flash?.info || flash?.warning) && (
                    <div className="mx-auto w-full max-w-7xl flex-shrink-0 px-4 pt-4 sm:px-6 lg:px-8">
                        {flash?.success && (
                            <div className="mb-2 flex flex-wrap items-center gap-x-3 gap-y-2 rounded-lg border border-[#BBF7D0] bg-[#F0FDF4] px-4 py-3 text-sm font-medium text-[#16A34A] animate-slide-up">
                                <span className="flex items-start gap-2.5">
                                    <svg className="mt-0.5 h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    {flash.success}
                                </span>

                                {flash?.whatsapp_link && (
                                    <a
                                        href={flash.whatsapp_link}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="inline-flex items-center gap-1.5 rounded-full bg-[#16A34A] px-3 py-1 text-xs font-semibold text-white transition hover:bg-[#15803D]"
                                    >
                                        <svg className="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.347-.397.52-.595.174-.198.232-.34.347-.567.116-.226.058-.42-.024-.567-.082-.148-.694-1.68-.95-2.3-.25-.605-.504-.586-.698-.596-.18-.01-.386-.008-.592-.008-.206 0-.54.078-.823.39-.283.31-1.075 1.048-1.075 2.56 0 1.512 1.1 2.976 1.253 3.182.153.206 2.157 3.4 5.317 4.646.742.32 1.322.51 1.775.654.844.276 1.612.237 2.219.144.677-.104 2.046-.836 2.334-1.644.288-.808.288-1.5.201-1.646-.086-.146-.314-.233-.61-.383zM12.05 21.5h-.01a9.4 9.4 0 01-4.99-1.55l-.36-.21-3.7.97.99-3.6-.23-.37a9.37 9.37 0 01-1.44-5.01c0-5.19 4.23-9.4 9.44-9.4a9.37 9.37 0 016.69 2.77 9.31 9.31 0 012.75 6.65c0 5.19-4.23 9.4-9.44 9.4zM20.4 3.65A11.29 11.29 0 0012.05.4C5.74.4.6 5.53.6 11.81c0 2 .53 3.95 1.53 5.68L.4 23.8l6.47-1.7a11.4 11.4 0 005.18 1.3h.01c6.31 0 11.45-5.13 11.45-11.41 0-3.05-1.19-5.92-3.11-8.04z" />
                                        </svg>
                                        Kirim Notifikasi ke WhatsApp
                                    </a>
                                )}
                            </div>
                        )}
                        {flash?.error && (
                            <div className="mb-2 flex items-start gap-2.5 rounded-lg border border-[#FECACA] bg-[#FEF2F2] px-4 py-3 text-sm font-medium text-[#DC2626] animate-slide-up">
                                <svg className="mt-0.5 h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                                {flash.error}
                            </div>
                        )}
                        {flash?.info && (
                            <div className="mb-2 flex items-start gap-2.5 rounded-lg border border-[#BFDBFE] bg-[#EFF6FF] px-4 py-3 text-sm font-medium text-[#2563EB] animate-slide-up">
                                <svg className="mt-0.5 h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M11.25 11.25h1.5v5.25M12 7.5h.008v.008H12V7.5zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                {flash.info}
                            </div>
                        )}
                        {flash?.warning && (
                            <div className="mb-2 flex items-start gap-2.5 rounded-lg border border-[#FDE68A] bg-[#FFFBEB] px-4 py-3 text-sm font-medium text-[#B45309] animate-slide-up">
                                <svg className="mt-0.5 h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.008v.008H12v-.008z" /></svg>
                                {flash.warning}
                            </div>
                        )}
                    </div>
                )}

                <main className="flex-1 overflow-y-auto animate-slide-up">{children}</main>
            </div>
        </div>
    );
}
