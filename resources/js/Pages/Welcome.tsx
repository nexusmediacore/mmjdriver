import { Link } from '@inertiajs/react';

export default function Welcome() {
    return (
        <>
            <main className="mx-auto flex min-h-screen flex-col items-center justify-center p-8">
                <div className="mb-8 text-center">
                    <h1 className="text-4xl font-bold text-gray-900">
                        Driver Dispatch System
                    </h1>
                    <p className="mt-2 text-lg text-gray-600">
                        Sistem Manajemen Perjalanan Dinas &amp; Driver Dispatch
                    </p>
                </div>

                <div className="space-y-4">
                    <Link
                        href={route('login')}
                        className="block w-full rounded-md bg-indigo-600 px-6 py-3 text-center text-base font-medium text-white hover:bg-indigo-700"
                    >
                        Login
                    </Link>
                    <Link
                        href={route('register')}
                        className="block w-full rounded-md bg-white px-6 py-3 text-center text-base font-medium text-indigo-600 hover:bg-gray-50"
                    >
                        Register
                    </Link>
                </div>
            </main>
        </>
    );
}
