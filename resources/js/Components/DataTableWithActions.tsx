import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { PencilIcon, TrashIcon, PlusIcon } from '@heroicons/react/24/outline';
import DataTable from '@/Components/DataTable';
import FormModal from '@/Components/FormModal';
import { usePage } from '@inertiajs/react';

interface Column<T> {
    key: string;
    label: string;
    render?: (row: T) => React.ReactNode;
}

interface DataTableWithActionsProps<T extends Record<string, any>> {
    data: T[];
    columns: Column<T>[];
    emptyMessage?: string;
    emptyDescription?: string;
    resourceName: string;
    formFields: Array<{
        name: string;
        label: string;
        type?: 'text' | 'email' | 'password' | 'number' | 'select' | 'textarea' | 'date';
        options?: Array<{ value: string; label: string }>;
        required?: boolean;
    }>;
    storeRoute: string;
    updateRoute: (row: T) => string;
    deleteRoute: (row: T) => string;
    includePassword?: boolean;
    /** override search keys; defaults to all column keys */
    searchKeys?: string[];
    searchPlaceholder?: string;
    /**
     * Tombol aksi per baris (Edit / Hapus / aksi khusus).
     * Menerima baris + helper openEditModal dan handleDelete.
     * Saat dipakai, jangan sertakan kolom "Aksi" di `columns`.
     */
    renderActions?: (row: T, helpers: { edit: () => void; remove: () => void }) => React.ReactNode;
    /**
     * Field khusus untuk mode EDIT. Bila tidak diisi, memakai `formFields`.
     * Gunakan ini untuk membatasi edit hanya pada field tertentu.
     */
    editFormFields?: DataTableWithActionsProps<T>['formFields'];
}

export default function DataTableWithActions<T extends Record<string, any>>({
    data,
    columns,
    emptyMessage = 'Tidak ada data',
    emptyDescription,
    resourceName,
    formFields,
    storeRoute,
    updateRoute,
    deleteRoute,
    includePassword = false,
    searchKeys,
    searchPlaceholder,
    renderActions,
    editFormFields,
}: DataTableWithActionsProps<T>) {
    const [modalOpen, setModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState<'add' | 'edit'>('add');
    const [currentRow, setCurrentRow] = useState<T | null>(null);
    const user = usePage().props.auth.user as { role?: { name: string } | null };
    const isSuperAdmin = user?.role?.name === 'SUPERADMIN';

    const openAddModal = () => {
        setModalMode('add');
        setCurrentRow(null);
        setModalOpen(true);
    };

    const openEditModal = (row: T) => {
        setModalMode('edit');
        setCurrentRow(row);
        setModalOpen(true);
    };

    const closeModal = () => {
        setModalOpen(false);
        setCurrentRow(null);
        setModalMode('add');
    };

    const handleDelete = (row: T) => {
        if (confirm(`Hapus ${resourceName} ini?`)) {
            router.delete(deleteRoute(row), {
                preserveState: false,
            });
        }
    };

    // Sisipkan kolom "Aksi" bila halaman mengirim renderActions
    const tableColumns: Column<T>[] = renderActions
        ? [
              ...columns.filter((c) => c.label.toLowerCase() !== 'aksi' && c.key !== 'id'),
              {
                  key: '__actions__',
                  label: 'Aksi',
                  render: (row: T) => renderActions(row, {
                      edit: () => openEditModal(row),
                      remove: () => handleDelete(row),
                  }),
              },
          ]
        : columns;

    if (!isSuperAdmin) {
        return (
            <DataTable
                data={data}
                columns={tableColumns}
                emptyMessage={emptyMessage}
                emptyDescription={emptyDescription}
                searchKeys={searchKeys}
                searchPlaceholder={searchPlaceholder}
            />
        );
    }

    return (
        <div className="space-y-4">
            <DataTable
                data={data}
                columns={tableColumns}
                emptyMessage={emptyMessage}
                emptyDescription={emptyDescription}
                searchKeys={searchKeys}
                searchPlaceholder={searchPlaceholder}
                toolbarActions={
                    <button
                        type="button"
                        onClick={openAddModal}
                        className="mmj-btn mmj-btn-primary"
                    >
                        <PlusIcon className="h-4 w-4" />
                        Tambah {resourceName}
                    </button>
                }
            />

            <FormModal
                key={`${modalMode}-${currentRow?.id ?? 'new'}`}
                title={modalMode === 'add' ? `Tambah ${resourceName}` : `Edit ${resourceName}`}
                fields={modalMode === 'add' ? formFields : (editFormFields ?? formFields)}
                getSubmitUrl={() =>
                    modalMode === 'add' || !currentRow ? storeRoute : updateRoute(currentRow)
                }
                method={modalMode === 'add' || !currentRow ? 'post' : 'put'}
                isOpen={modalOpen}
                onClose={closeModal}
                data={currentRow ?? {}}
                includePassword={includePassword}
            />
        </div>
    );
}
