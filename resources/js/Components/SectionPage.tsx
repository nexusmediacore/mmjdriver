import { ReactNode } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/UI/PageHeader';
import { Head } from '@inertiajs/react';

interface SectionPageProps {
    title: string;
    header?: string;
    description?: string;
    eyebrow?: string;
    actions?: ReactNode;
    meta?: ReactNode;
    children: ReactNode;
}

export default function SectionPage({ title, header, description, eyebrow, actions, meta, children }: SectionPageProps) {
    return (
        <AuthenticatedLayout header={header ?? title}>
            <Head title={title} />

            <div className="mmj-page-in py-8 sm:py-10">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <PageHeader
                        eyebrow={eyebrow ?? header ?? title}
                        title={title}
                        description={description}
                        actions={actions}
                        meta={meta}
                    />
                    {children}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
