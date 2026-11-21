import { useEffect, useRef, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/UI/PageHeader';
import Panel from '@/Components/UI/Panel';
import StatusBadge from '@/Components/UI/StatusBadge';
import {
    CameraIcon,
    ArrowLeftOnRectangleIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    MapPinIcon,
    ExclamationTriangleIcon,
    TruckIcon,
} from '@heroicons/react/24/outline';

interface Attendance {
    id: number;
    date: string;
    clock_in_at: string | null;
    clock_out_at: string | null;
    has_clock_in_photo: boolean;
    has_clock_out_photo: boolean;
    is_late?: boolean;
    lateness_minutes?: number;
    clock_in_distance_meters?: number | null;
    notes: string | null;
    work_duration_minutes: number | null;
}

interface OfficeInfo {
    latitude: number;
    longitude: number;
    street_name?: string;
    max_radius_meters: number;
    cutoff_time: string;
}

interface Props {
    driver: {
        id: number;
        name: string;
        status: string;
        is_on_trip?: boolean;
        active_trip?: { trip_number: string; destination: string; status: string } | null;
    };
    today: Attendance | null;
    history: Attendance[];
    office?: OfficeInfo;
}

interface LocationDetails {
    lat?: number;
    lng?: number;
    streetName?: string;
}

function calculateDistanceMeters(lat1: number, lon1: number, lat2: number, lon2: number): number {
    const R = 6371000;
    const dLat = ((lat2 - lat1) * Math.PI) / 180;
    const dLon = ((lon2 - lon1) * Math.PI) / 180;
    const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos((lat1 * Math.PI) / 180) *
            Math.cos((lat2 * Math.PI) / 180) *
            Math.sin(dLon / 2) *
            Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return Math.round(R * c * 10) / 10;
}

const time = (value: string | null) => (value ? new Date(value).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '-');
const day = (value: string) => new Date(value).toLocaleDateString('id-ID', { dateStyle: 'medium' });

function duration(minutes: number | null) {
    if (minutes === null) return '-';
    return `${Math.floor(minutes / 60)}j ${minutes % 60}m`;
}

/**
 * Memberikan watermark timestamp, label identitas, koordinat GPS, serta nama jalan
 * ke sebuah gambar (HTMLImageElement) menggunakan HTML Canvas, kemudian mengembalikan berkas baru (File JPEG).
 */
async function watermarkImageFile(
    file: File,
    label: string = 'ABSENSI DRIVER',
    location?: LocationDetails
): Promise<File> {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const img = new Image();
            img.onload = () => {
                const canvas = document.createElement('canvas');
                // Batasi dimensi maksimum agar file ramah upload tapi tetap tajam
                const maxDim = 1280;
                let width = img.width;
                let height = img.height;
                if (width > maxDim || height > maxDim) {
                    if (width > height) {
                        height = Math.round((height * maxDim) / width);
                        width = maxDim;
                    } else {
                        width = Math.round((width * maxDim) / height);
                        height = maxDim;
                    }
                }

                canvas.width = width;
                canvas.height = height;

                const ctx = canvas.getContext('2d');
                if (!ctx) {
                    resolve(file);
                    return;
                }

                // 1. Gambar foto utama
                ctx.drawImage(img, 0, 0, width, height);

                // 2. Siapkan teks timestamp & lokasi
                const now = new Date();
                const dateStr = now.toLocaleDateString('id-ID', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                });
                const timeStr = now.toLocaleTimeString('id-ID', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                });
                const timestampText = `${dateStr} • ${timeStr} WIB`;

                const extraLines: string[] = [];
                if (location?.lat !== undefined && location?.lng !== undefined) {
                    extraLines.push(`Koordinat: ${location.lat.toFixed(6)}, ${location.lng.toFixed(6)}`);
                }
                if (location?.streetName) {
                    extraLines.push(`Lokasi: ${location.streetName}`);
                }

                // 3. Ukuran font proporsional terhadap tinggi gambar
                const fontSize = Math.max(16, Math.round(height * 0.028));
                const subFontSize = Math.max(12, Math.round(fontSize * 0.72));
                const locFontSize = Math.max(11, Math.round(fontSize * 0.65));
                const padding = Math.round(fontSize * 0.8);

                ctx.font = `bold ${fontSize}px sans-serif`;
                let maxWidth = Math.max(
                    ctx.measureText(timestampText).width,
                    ctx.measureText(label).width
                );

                ctx.font = `${locFontSize}px sans-serif`;
                extraLines.forEach((line) => {
                    const w = ctx.measureText(line).width;
                    if (w > maxWidth) maxWidth = w;
                });

                const bannerHeight =
                    fontSize +
                    subFontSize +
                    extraLines.length * (locFontSize + 4) +
                    padding * 2.5;

                // 4. Baris latar transparan gelap di bagian bawah untuk keterbacaan tinggi
                const bannerY = height - bannerHeight - padding;
                ctx.fillStyle = 'rgba(15, 23, 42, 0.76)';
                ctx.fillRect(padding, bannerY, maxWidth + padding * 2.5, bannerHeight);

                // Garis aksen kiri
                ctx.fillStyle = '#2563EB';
                ctx.fillRect(padding, bannerY, Math.max(4, Math.round(fontSize * 0.2)), bannerHeight);

                // 5. Teks watermark
                // Label (ABSEN MASUK / ABSEN PULANG • NAMA DRIVER)
                let textY = bannerY + padding * 0.6;
                ctx.fillStyle = '#FFFFFF';
                ctx.textBaseline = 'top';
                ctx.font = `bold ${fontSize}px sans-serif`;
                ctx.fillText(label, padding * 2, textY);

                // Timestamp waktu WIB (Kuning keemasan)
                textY += fontSize + 4;
                ctx.fillStyle = '#FCD34D';
                ctx.font = `${subFontSize}px monospace, sans-serif`;
                ctx.fillText(timestampText, padding * 2, textY);

                // Garis koordinat & nama jalan (Biru muda lembut)
                ctx.fillStyle = '#93C5FD';
                ctx.font = `${locFontSize}px sans-serif`;
                extraLines.forEach((line) => {
                    textY += locFontSize + 4;
                    ctx.fillText(line, padding * 2, textY);
                });

                // 6. Ekspor ke Blob JPEG
                canvas.toBlob(
                    (blob) => {
                        if (!blob) {
                            resolve(file);
                            return;
                        }
                        const stampedFile = new File(
                            [blob],
                            `attendance-stamped-${Date.now()}.jpg`,
                            { type: 'image/jpeg' }
                        );
                        resolve(stampedFile);
                    },
                    'image/jpeg',
                    0.88
                );
            };
            img.onerror = () => resolve(file);
            img.src = e.target?.result as string;
        };
        reader.onerror = () => resolve(file);
        reader.readAsDataURL(file);
    });
}

/**
 * Tangkap foto selfie langsung dari kamera perangkat (capture native via kamera)
 * kemudian otomatis membubuhkan watermark timestamp resmi sebelum disimpan/diunggah.
 * Fitur unggah file umum dihilangkan agar absensi wajib melalui foto kamera langsung dengan timestamp.
 */
function SelfieCapture({
    photo,
    onPhoto,
    error,
    driverName,
    modeLabel,
    location,
}: {
    photo: File | null;
    onPhoto: (file: File | null) => void;
    error?: string;
    driverName?: string;
    modeLabel?: string;
    location?: LocationDetails;
}) {
    const videoRef = useRef<HTMLVideoElement | null>(null);
    const streamRef = useRef<MediaStream | null>(null);
    const cameraInputRef = useRef<HTMLInputElement | null>(null);
    const [cameraActive, setCameraActive] = useState(false);
    const [cameraError, setCameraError] = useState<string | null>(null);
    const [preview, setPreview] = useState<string | null>(null);
    const [watermarking, setWatermarking] = useState(false);

    useEffect(() => {
        if (!photo) {
            setPreview(null);
            return;
        }
        const url = URL.createObjectURL(photo);
        setPreview(url);
        return () => URL.revokeObjectURL(url);
    }, [photo]);

    useEffect(() => () => stopCamera(), []);

    const startCamera = async () => {
        setCameraError(null);
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 1280 } },
                audio: false,
            });
            streamRef.current = stream;
            setCameraActive(true);
            if (videoRef.current) {
                videoRef.current.srcObject = stream;
                await videoRef.current.play();
            }
        } catch {
            // Fallback ke kamera bawaan browser/HP via input capture
            setCameraActive(false);
            if (cameraInputRef.current) {
                cameraInputRef.current.click();
            } else {
                setCameraError('Kamera tidak dapat diakses langsung. Silakan izinkan akses kamera pada peramban Anda.');
            }
        }
    };

    const stopCamera = () => {
        streamRef.current?.getTracks().forEach((track) => track.stop());
        streamRef.current = null;
        setCameraActive(false);
    };

    const applyWatermarkAndSet = async (rawFile: File) => {
        setWatermarking(true);
        try {
            const label = `${modeLabel ?? 'ABSENSI DRIVER'} • ${driverName ?? 'DRIVER'}`.toUpperCase();
            const stamped = await watermarkImageFile(rawFile, label, location);
            onPhoto(stamped);
        } finally {
            setWatermarking(false);
        }
    };

    const capture = () => {
        const video = videoRef.current;
        if (!video) return;

        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth || 720;
        canvas.height = video.videoHeight || 720;
        canvas.getContext('2d')?.drawImage(video, 0, 0, canvas.width, canvas.height);

        canvas.toBlob(
            async (blob) => {
                if (!blob) return;
                const rawFile = new File([blob], `selfie-${Date.now()}.jpg`, { type: 'image/jpeg' });
                stopCamera();
                await applyWatermarkAndSet(rawFile);
            },
            'image/jpeg',
            0.92
        );
    };

    const handleCameraFileChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;
        await applyWatermarkAndSet(file);
    };

    return (
        <div className="space-y-3">
            <div className="relative mx-auto aspect-square w-full max-w-xs overflow-hidden rounded-2xl border border-[#E2E8F0] bg-[#0F172A]">
                {preview ? (
                    <img src={preview} alt="Preview selfie bertanda timestamp" className="h-full w-full object-cover" />
                ) : (
                    <>
                        <video ref={videoRef} playsInline muted className={`h-full w-full object-cover ${cameraActive ? '' : 'hidden'}`} />
                        {!cameraActive && (
                            <div className="flex h-full w-full flex-col items-center justify-center gap-2 text-center text-white/80">
                                <CameraIcon className="h-10 w-10 text-[#60A5FA]" />
                                <p className="px-6 text-xs">Ambil foto selfie langsung dengan kamera</p>
                                <span className="inline-block rounded-full bg-white/10 px-2.5 py-0.5 text-[10px] text-[#FCD34D]">
                                    Watermark timestamp otomatis dibubuhkan
                                </span>
                            </div>
                        )}
                    </>
                )}

                {watermarking && (
                    <div className="absolute inset-0 flex items-center justify-center bg-black/60 text-xs font-semibold text-white">
                        Menerapkan timestamp pada foto...
                    </div>
                )}
            </div>

            <div className="flex flex-wrap items-center justify-center gap-2">
                {cameraActive ? (
                    <>
                        <button
                            type="button"
                            onClick={capture}
                            disabled={watermarking}
                            className="mmj-btn mmj-btn-primary px-4 py-2 text-sm"
                        >
                            <CameraIcon className="h-4 w-4" /> Ambil Foto & Beri Timestamp
                        </button>
                        <button
                            type="button"
                            onClick={stopCamera}
                            className="inline-flex items-center gap-1.5 rounded-lg border border-[#E2E8F0] bg-white px-3 py-2 text-sm font-semibold text-[#64748B]"
                        >
                            Batal
                        </button>
                    </>
                ) : (
                    <>
                        <button
                            type="button"
                            onClick={startCamera}
                            disabled={watermarking}
                            className="mmj-btn mmj-btn-primary px-4 py-2 text-sm"
                        >
                            <CameraIcon className="h-4 w-4" /> Buka Kamera
                        </button>

                        {/* Input capture khusus kamera bawaan (mobile/browser fallback) — BUKAN upload foto dari galeri */}
                        <input
                            ref={cameraInputRef}
                            type="file"
                            accept="image/*"
                            capture="user"
                            className="hidden"
                            onChange={handleCameraFileChange}
                        />

                        {photo && (
                            <button
                                type="button"
                                onClick={() => onPhoto(null)}
                                className="inline-flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-semibold text-[#DC2626]"
                            >
                                Foto Ulang
                            </button>
                        )}
                    </>
                )}
            </div>

            {cameraError && <p className="text-center text-xs text-[#B45309]">{cameraError}</p>}
            {error && <p className="text-center text-xs text-red-600">{error}</p>}
        </div>
    );
}

export default function DriverAttendance({ driver, today, history, office }: Props) {
    const [mode, setMode] = useState<'in' | 'out'>(today?.clock_in_at && !today?.clock_out_at ? 'out' : 'in');
    const [coords, setCoords] = useState<{ lat?: number; lng?: number }>({});
    const [streetName, setStreetName] = useState<string>(office?.street_name ?? 'Jl. Angkasa, Pekanbaru');
    const [gpsLoading, setGpsLoading] = useState<boolean>(true);
    const [gpsError, setGpsError] = useState<string | null>(null);

    type AttendanceForm = { photo: File | null; notes: string; lat: number | null; lng: number | null };

    const { data, setData, post, processing, errors, reset } = useForm<AttendanceForm>({
        photo: null,
        notes: '',
        lat: null,
        lng: null,
    });

    const officeLat = office?.latitude ?? 0.5345679;
    const officeLng = office?.longitude ?? 101.4258138;
    const defaultStreet = office?.street_name ?? 'Jl. Angkasa, Pekanbaru';
    const maxRadius = office?.max_radius_meters ?? 100;

    const resolveStreetName = async (lat: number, lng: number) => {
        // Cek jika sangat dekat dengan kantor, langsung gunakan nama jalan kantor
        const dist = calculateDistanceMeters(lat, lng, officeLat, officeLng);
        if (dist <= maxRadius) {
            setStreetName(defaultStreet);
            return;
        }

        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 4000);
            const res = await fetch(
                `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`,
                { signal: controller.signal }
            );
            clearTimeout(timeoutId);
            if (res.ok) {
                const json = await res.json();
                const road =
                    json.address?.road ||
                    json.address?.pedestrian ||
                    json.address?.suburb ||
                    json.address?.city ||
                    defaultStreet;
                setStreetName(road);
            } else {
                setStreetName(defaultStreet);
            }
        } catch {
            setStreetName(defaultStreet);
        }
    };

    const requestGps = () => {
        if (!navigator.geolocation) {
            setGpsError('Perangkat Anda tidak mendukung geolokasi GPS.');
            setGpsLoading(false);
            return;
        }
        setGpsLoading(true);
        setGpsError(null);
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const lat = Number(pos.coords.latitude.toFixed(6));
                const lng = Number(pos.coords.longitude.toFixed(6));
                setCoords({ lat, lng });
                setData((prev) => ({ ...prev, lat, lng }));
                setGpsLoading(false);
                resolveStreetName(lat, lng);
            },
            (err) => {
                let msg = 'Gagal mendeteksi lokasi GPS.';
                if (err.code === err.PERMISSION_DENIED) {
                    msg = 'Izin akses lokasi ditolak. Harap izinkan akses lokasi (GPS) pada browser/perangkat Anda.';
                } else if (err.code === err.POSITION_UNAVAILABLE) {
                    msg = 'Sinyal lokasi GPS tidak tersedia.';
                } else if (err.code === err.TIMEOUT) {
                    msg = 'Waktu pencarian lokasi habis. Silakan coba lagi.';
                }
                setGpsError(msg);
                setGpsLoading(false);
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    };

    useEffect(() => {
        requestGps();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const currentDistance =
        coords.lat !== undefined && coords.lng !== undefined
            ? calculateDistanceMeters(coords.lat, coords.lng, officeLat, officeLng)
            : null;

    const isWithinRadius = currentDistance !== null && currentDistance <= maxRadius;

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const endpoint = mode === 'in' ? route('driver.attendance.clock-in') : route('driver.attendance.clock-out');
        post(endpoint, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset('photo', 'notes');
                setMode(mode === 'in' ? 'out' : 'in');
            },
        });
    };

    const alreadyIn = Boolean(today?.clock_in_at);
    const alreadyOut = Boolean(today?.clock_out_at);
    const bothDone = alreadyIn && alreadyOut;

    return (
        <AuthenticatedLayout header="Absensi Driver">
            <Head title="Absensi Driver" />

            <div className="py-8 sm:py-10">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <PageHeader
                        eyebrow="Kehadiran"
                        title="Absensi Harian"
                        description="Lakukan absensi masuk dan pulang dengan melampirkan foto selfie."
                        meta={<StatusBadge status={driver.status} variant="driver" />}
                    />

                    {/* Banner Pemberitahuan jika Driver Sedang Bertugas / Dalam Perjalanan */}
                    {driver.is_on_trip && (
                        <div className="mb-6 flex items-start gap-3 rounded-xl border border-[#93C5FD] bg-[#EFF6FF] p-4 text-[#1E40AF]">
                            <TruckIcon className="mt-0.5 h-6 w-6 flex-shrink-0 text-[#2563EB]" />
                            <div className="text-sm">
                                <p className="font-bold">Status: Sedang Dalam Perjalanan / Bertugas</p>
                                <p className="mt-0.5 text-xs text-[#1E3A8A]">
                                    Anda sedang aktif menjalankan tugas perjalanan{' '}
                                    {driver.active_trip ? (
                                        <span className="font-semibold">
                                            (Trip #{driver.active_trip.trip_number} - {driver.active_trip.destination})
                                        </span>
                                    ) : (
                                        ''
                                    )}
                                    . Tidak masalah jika belum dapat melakukan absensi saat ini karena masih dalam perjalanan.
                                </p>
                            </div>
                        </div>
                    )}

                    {/* Info Ketentuan Absensi */}
                    <div className="mb-6 rounded-xl border border-[#E2E8F0] bg-[#F8FAFC] p-4">
                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 text-xs text-[#475569]">
                            <div className="flex items-start gap-2">
                                <ClockIcon className="mt-0.5 h-4 w-4 text-[#2563EB] flex-shrink-0" />
                                <div>
                                    <span className="font-semibold text-[#0F172A]">Batas Masuk Tepat Waktu:</span> Pukul 07:45 WIB. Lewat dari pukul 07:45 WIB dinyatakan <strong>TERLAMBAT</strong>.
                                </div>
                            </div>
                            <div className="flex items-start gap-2">
                                <MapPinIcon className="mt-0.5 h-4 w-4 text-[#2563EB] flex-shrink-0" />
                                <div>
                                    <span className="font-semibold text-[#0F172A]">Area Absensi:</span> Wajib berada dalam radius maksimal <strong>100 meter</strong> dari kantor PT. Marita Makmur Jaya.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div className="rounded-xl border border-[#E2E8F0] bg-white p-4 shadow-sm">
                            <p className="text-xs font-semibold uppercase tracking-wide text-[#94A3B8]">Absen Masuk</p>
                            <p className="mt-1 text-xl font-bold text-[#0F172A]">{time(today?.clock_in_at ?? null)}</p>
                            {alreadyIn ? (
                                <div className="mt-1 flex flex-wrap items-center gap-1.5">
                                    <span className="inline-flex items-center gap-1 text-xs font-semibold text-[#16A34A]">
                                        <CheckCircleIcon className="h-4 w-4" /> Tercatat
                                    </span>
                                    {today?.is_late ? (
                                        <span className="inline-flex items-center rounded-full bg-[#FEE2E2] px-2 py-0.5 text-[10px] font-semibold text-[#DC2626]">
                                            Terlambat {today.lateness_minutes ? `(+${today.lateness_minutes} mnt)` : ''}
                                        </span>
                                    ) : (
                                        <span className="inline-flex items-center rounded-full bg-[#DCFCE7] px-2 py-0.5 text-[10px] font-semibold text-[#16A34A]">
                                            Tepat Waktu
                                        </span>
                                    )}
                                </div>
                            ) : (
                                <span className="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-[#94A3B8]">
                                    <XCircleIcon className="h-4 w-4" /> Belum absen
                                </span>
                            )}
                        </div>
                        <div className="rounded-xl border border-[#E2E8F0] bg-white p-4 shadow-sm">
                            <p className="text-xs font-semibold uppercase tracking-wide text-[#94A3B8]">Absen Pulang</p>
                            <p className="mt-1 text-xl font-bold text-[#0F172A]">{time(today?.clock_out_at ?? null)}</p>
                            {alreadyOut ? (
                                <span className="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-[#16A34A]">
                                    <CheckCircleIcon className="h-4 w-4" /> Tercatat
                                </span>
                            ) : (
                                <span className="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-[#94A3B8]">
                                    <XCircleIcon className="h-4 w-4" /> Belum absen
                                </span>
                            )}
                        </div>
                        <div className="rounded-xl border border-[#E2E8F0] bg-white p-4 shadow-sm">
                            <p className="text-xs font-semibold uppercase tracking-wide text-[#94A3B8]">Durasi Kerja</p>
                            <p className="mt-1 text-xl font-bold text-[#0F172A]">{duration(today?.work_duration_minutes ?? null)}</p>
                        </div>
                    </div>

                    <Panel
                        title={mode === 'in' ? 'Absen Masuk' : 'Absen Pulang'}
                        description="Wajib ambil foto selfie melalui kamera (foto otomatis dibubuhi tanda timestamp)."
                        icon={<CameraIcon className="h-4 w-4" />}
                    >
                        {bothDone ? (
                            <div className="flex flex-col items-center gap-2 py-8 text-center">
                                <CheckCircleIcon className="h-10 w-10 text-[#16A34A]" />
                                <p className="font-semibold text-[#0F172A]">Absensi hari ini sudah lengkap</p>
                                <p className="text-sm text-[#64748B]">Terima kasih, sampai jumpa besok.</p>
                            </div>
                        ) : (
                            <form onSubmit={submit} className="space-y-4">
                                {!bothDone && (
                                    <div className="flex gap-2">
                                        <button
                                            type="button"
                                            onClick={() => setMode('in')}
                                            disabled={alreadyIn}
                                            className={`flex-1 rounded-lg border px-3 py-2 text-sm font-semibold transition ${
                                                mode === 'in'
                                                    ? 'border-[#2563EB] bg-[#EFF6FF] text-[#2563EB]'
                                                    : 'border-[#E2E8F0] bg-white text-[#64748B]'
                                            } ${alreadyIn ? 'cursor-not-allowed opacity-50' : ''}`}
                                        >
                                            Absen Masuk
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => setMode('out')}
                                            disabled={!alreadyIn || alreadyOut}
                                            className={`flex-1 rounded-lg border px-3 py-2 text-sm font-semibold transition ${
                                                mode === 'out'
                                                    ? 'border-[#8B5CF6] bg-[#F5F0FF] text-[#8B5CF6]'
                                                    : 'border-[#E2E8F0] bg-white text-[#64748B]'
                                            } ${!alreadyIn || alreadyOut ? 'cursor-not-allowed opacity-50' : ''}`}
                                        >
                                            Absen Pulang
                                        </button>
                                    </div>
                                )}

                                <SelfieCapture
                                    photo={data.photo}
                                    onPhoto={(file) => setData('photo', file)}
                                    error={errors.photo}
                                    driverName={driver.name}
                                    modeLabel={mode === 'in' ? 'Absen Masuk' : 'Absen Pulang'}
                                    location={{
                                        lat: coords.lat,
                                        lng: coords.lng,
                                        streetName: streetName,
                                    }}
                                />

                                <div>
                                    <label className="mmx-label">Catatan (opsional)</label>
                                    <textarea
                                        rows={2}
                                        value={data.notes}
                                        onChange={(e) => setData('notes', e.target.value)}
                                        className="mt-1 block w-full rounded-lg border border-[#E2E8F0] px-3 py-2 text-sm focus:border-[#2563EB] focus:outline-none focus:ring-4 focus:ring-[#2563EB]/10"
                                        placeholder="Contoh: langsung menuju garasi kendaraan"
                                    />
                                </div>

                                {/* Status GPS & Radius Indicator */}
                                <div className="rounded-lg border border-[#E2E8F0] bg-[#F8FAFC] p-3 text-xs">
                                    <div className="flex items-center justify-between">
                                        <span className="font-semibold text-[#0F172A] flex items-center gap-1.5">
                                            <MapPinIcon className="h-4 w-4 text-[#2563EB]" /> Status Lokasi Driver
                                        </span>
                                        <button
                                            type="button"
                                            onClick={requestGps}
                                            className="text-[11px] font-semibold text-[#2563EB] hover:underline"
                                        >
                                            Perbarui GPS
                                        </button>
                                    </div>

                                    {gpsLoading ? (
                                        <p className="mt-1.5 text-[#64748B]">Mencari koordinat lokasi GPS Anda...</p>
                                    ) : gpsError ? (
                                        <div className="mt-1.5 flex items-start gap-1.5 text-[#DC2626]">
                                            <ExclamationTriangleIcon className="mt-0.5 h-4 w-4 flex-shrink-0" />
                                            <span>{gpsError}</span>
                                        </div>
                                    ) : currentDistance !== null ? (
                                        <div className="mt-2 space-y-1">
                                            <div className="flex items-center justify-between">
                                                <span className="text-[#64748B]">Jarak dari kantor:</span>
                                                <span className={`font-bold ${isWithinRadius ? 'text-[#16A34A]' : 'text-[#DC2626]'}`}>
                                                    {currentDistance < 1000
                                                        ? `${currentDistance.toFixed(1)} meter`
                                                        : `${(currentDistance / 1000).toFixed(2)} km`}
                                                </span>
                                            </div>
                                            <div className="flex items-center justify-between">
                                                <span className="text-[#64748B]">Radius diizinkan:</span>
                                                <span className="font-medium text-[#0F172A]">Maksimal {maxRadius} meter</span>
                                            </div>
                                            <div className="flex items-center justify-between">
                                                <span className="text-[#64748B]">Nama Jalan / Lokasi:</span>
                                                <span className="font-medium text-[#0F172A]">{streetName}</span>
                                            </div>
                                            {!isWithinRadius && (
                                                <p className="mt-1 font-semibold text-[#DC2626]">
                                                    Peringatan: Anda berada di luar radius kantor (&gt;100m). Absensi akan ditolak sistem.
                                                </p>
                                            )}
                                        </div>
                                    ) : (
                                        <p className="mt-1.5 text-[#64748B]">Lokasi GPS belum tersedia.</p>
                                    )}

                                    {errors.lat && <p className="mt-1 text-red-600">{errors.lat}</p>}
                                    {errors.lng && <p className="mt-1 text-red-600">{errors.lng}</p>}
                                    {(errors as Record<string, string>).location && (
                                        <p className="mt-1 font-semibold text-[#DC2626]">{(errors as Record<string, string>).location}</p>
                                    )}
                                </div>

                                <button
                                    type="submit"
                                    disabled={processing || !data.photo || !isWithinRadius}
                                    className="mmj-btn mmj-btn-primary w-full justify-center py-2.5 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {mode === 'in' ? (
                                        <>
                                            <CameraIcon className="h-4 w-4" /> Kirim Absen Masuk
                                        </>
                                    ) : (
                                        <>
                                            <ArrowLeftOnRectangleIcon className="h-4 w-4" /> Kirim Absen Pulang
                                        </>
                                    )}
                                </button>
                                {!isWithinRadius && (
                                    <p className="text-center text-[11px] text-[#DC2626]">
                                        Tombol absensi aktif ketika posisi Anda berada dalam radius 100 meter dari kantor PT. Marita Makmur Jaya.
                                    </p>
                                )}
                            </form>
                        )}
                    </Panel>

                    <div className="mt-6">
                        <Panel title="Riwayat Absensi" description="30 hari terakhir" icon={<CheckCircleIcon className="h-4 w-4" />}>
                            {history.length === 0 ? (
                                <p className="py-6 text-center text-sm text-[#94A3B8]">Belum ada riwayat absensi.</p>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="mmj-table w-full">
                                        <thead>
                                            <tr>
                                                <th className="text-start">Tanggal</th>
                                                <th className="text-start">Masuk</th>
                                                <th className="text-start">Pulang</th>
                                                <th className="text-start">Durasi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {history.map((row) => (
                                                <tr key={row.id}>
                                                    <td>{day(row.date)}</td>
                                                    <td>{time(row.clock_in_at)}</td>
                                                    <td>{time(row.clock_out_at)}</td>
                                                    <td>{duration(row.work_duration_minutes)}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </Panel>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
