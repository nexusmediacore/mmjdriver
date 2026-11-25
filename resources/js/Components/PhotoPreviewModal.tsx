import { XMarkIcon } from '@heroicons/react/24/outline';
import Modal from '@/Components/UI/Modal';

interface PhotoPreviewModalProps {
    show: boolean;
    onClose: () => void;
    photoUrl: string | null;
    photoAlt: string;
}

export default function PhotoPreviewModal({
    show,
    onClose,
    photoUrl,
    photoAlt,
}: PhotoPreviewModalProps) {
    if (!show || !photoUrl) return null;

    return (
        <Modal show={show} onClose={onClose} maxWidth="2xl">
            <div className="flex flex-col items-center gap-4">
                <button
                    type="button"
                    onClick={onClose}
                    aria-label="Tutup pratinjau foto"
                    className="absolute top-4 right-4 rounded-lg p-2 text-[#64748B] hover:bg-[#F1F5F9] hover:text-[#0F172A] transition"
                >
                    <XMarkIcon className="h-5 w-5" />
                </button>
                <img
                    src={photoUrl}
                    alt={photoAlt}
                    className="max-w-2xl max-h-[70vh] object-contain rounded-lg border border-[#E2E8F0] shadow-lg"
                    title={photoAlt}
                />
            </div>
        </Modal>
    );
}
