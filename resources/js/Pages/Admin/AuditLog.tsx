import SectionPage from '@/Components/SectionPage';
import DataTable from '@/Components/DataTable';

interface AuditLog {
    id: number;
    user: string;
    action: string;
    module: string;
    record_type: string;
    record_id: number;
    old_value: string;
    new_value: string;
    ip_address: string;
    created_at: string;
}

interface Props {
    logs: AuditLog[];
}

export default function AdminAuditLog({ logs }: Props) {
    const columns = [
        { key: 'created_at', label: 'Tanggal' },
        { key: 'user', label: 'User' },
        { key: 'action', label: 'Aksi' },
        { key: 'module', label: 'Modul' },
        { key: 'record_type', label: 'Record Type' },
        { key: 'record_id', label: 'Record ID' },
        { key: 'ip_address', label: 'IP Address' },
    ];

    return (
        <SectionPage title="Audit Log">
            <DataTable
                data={logs}
                columns={columns}
                searchPlaceholder="Cari log (aksi/modul/user/IP)... (tekan /)"
                emptyMessage="Tidak ada log"
            />
        </SectionPage>
    );
}
