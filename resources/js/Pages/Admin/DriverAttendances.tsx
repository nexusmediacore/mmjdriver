import { router } from "@inertiajs/react";
import { useState } from "react";
import PhotoPreviewModal from "@/Components/PhotoPreviewModal";
import SectionPage from "@/Components/SectionPage";
import StatCard from "@/Components/UI/StatCard";
import {
    UserGroupIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    TruckIcon,
    ExclamationCircleIcon,
    PrinterIcon,
    CalendarIcon,
} from "@heroicons/react/24/outline";

interface AttendanceRecord {
    id: number;
    date: string;
    clock_in_at: string | null;
    clock_out_at: string | null;
    clock_in_photo_path: string | null;
    clock_out_photo_path: string | null;
    is_late: boolean;
    lateness_minutes: number;
    work_duration_minutes: number | null;
}

interface RecapRow {
    driver_id: number;
    driver_name: string;
    driver_status: string;
    records: AttendanceRecord[];
    summary: {
        present: number;
        late: number;
        clocked_out: number;
        total_minutes: number;
        lateness_minutes: number;
    };
}

interface Summary {
    total_drivers: number;
    present: number;
    on_time?: number;
    late?: number;
    clocked_out: number;
    on_trip?: number;
    absent: number;
}

interface Props {
    mode: "daily" | "weekly" | "monthly" | "yearly";
    anchor: string;
    period_label: string;
    period_start: string;
    period_end: string;
    rows: RecapRow[];
    summary: Summary;
    office?: {
        latitude: number;
        longitude: number;
        max_radius_meters: number;
        cutoff_time: string;
    };
    /** Dipakai saat driver membuka halaman sendiri (bukan rekap admin). */
    legacy?: boolean;
    attendances?: unknown[];
    date?: string;
}

const MODES: Array<{ value: Props["mode"]; label: string }> = [
    { value: "daily", label: "Harian" },
    { value: "weekly", label: "Mingguan" },
    { value: "monthly", label: "Bulanan" },
    { value: "yearly", label: "Tahunan" },
];

const duration = (minutes: number) => {
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return `${h}j ${m}m`;
};

const time = (value: string | null) =>
    value
        ? new Date(value).toLocaleTimeString("id-ID", {
              hour: "2-digit",
              minute: "2-digit",
          })
        : "-";

function PhotoLink({
    attendanceId,
    type,
    hasPhoto,
    setPreview,
}: {
    attendanceId: number;
    type: "in" | "out";
    hasPhoto: boolean;
    setPreview: (url: string | null, alt: string) => void;
}) {
    if (!hasPhoto) return null;
    const url = `${route("admin.driver-attendances.photo", {
        attendance: attendanceId,
    })}?type=${type === "out" ? "out" : "in"}`;
    return (
        <button
            type="button"
            onClick={() => setPreview(url, type === "out" ? "Foto Pulang" : "Foto Masuk")}
            className="ml-1 block"
        >
            <img
                src={url}
                alt={type === "out" ? "Foto Pulang" : "Foto Masuk"}
                className="w-8 h-8 rounded border border-[#E2E8F0] hover:shadow-md cursor-pointer"
                title={type === "out" ? "Foto Pulang" : "Foto Masuk"}
            />
        </button>
    );
}

function PrintButton({
    mode,
    anchor,
    periodLabel,
    periodStart,
    periodEnd,
    rows,
    summary,
}: {
    mode: Props["mode"];
    anchor: string;
    periodLabel: string;
    periodStart: string;
    periodEnd: string;
    rows: RecapRow[];
    summary: Summary;
}) {
    // Buka dokumen cetak khusus (print layout terpisah) berisi HANYA data
    // rekap sesuai periode terpilih — bukan seluruh tampilan halaman.
    const openPrintDocument = () => {
        const modeLabels: Record<Props["mode"], string> = {
            daily: "Harian",
            weekly: "Mingguan",
            monthly: "Bulanan",
            yearly: "Tahunan",
        };

        const periodTitle =
            mode === "daily"
                ? `Tanggal: ${periodStart}`
                : mode === "weekly"
                  ? `Periode Minggu: ${periodStart} s/d ${periodEnd}`
                  : mode === "monthly"
                    ? `Periode Bulan: ${periodLabel}`
                    : `Periode Tahun: ${periodLabel}`;

        const esc = (s: string) =>
            s.replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;");

        const rowsHtml = rows.length
            ? rows
                  .map((row) => {
                      const detail = row.records.length
                          ? row.records
                                .map(
                                    (r) =>
                                        `${esc(r.date)}: ${time(r.clock_in_at)} → ${time(r.clock_out_at)}` +
                                        (r.is_late
                                            ? ` (Telat ${r.lateness_minutes} m)`
                                            : ""),
                                )
                                .join("&lt;br&gt;")
                          : "-";

                      return `<tr>` +
                          `<td class="driver">${esc(row.driver_name)}</td>` +
                          `<td class="center">${row.summary.present}</td>` +
                          `<td class="center">${row.summary.late}</td>` +
                          `<td class="center">${row.summary.lateness_minutes > 0 ? `+${row.summary.lateness_minutes} m` : "-"}</td>` +
                          `<td class="center">${duration(row.summary.total_minutes)}</td>` +
                          `<td>${detail}</td>` +
                          `</tr>`;
                  })
                  .join("")
            : `<tr><td colspan="6" class="center empty">Belum ada data absensi pada periode ini.</td></tr>`;

        const printWindow = window.open("", "_blank", "width=1024,height=768");
        if (!printWindow) {
            alert(
                "Popup diblokir browser. Izinkan popup untuk halaman ini agar dokumen cetak dapat dibuka.",
            );
            return;
        }

        printWindow.document.write(`<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8" />
<title>Rekap Absensi Driver - ${esc(periodLabel)}</title>
<style>
    * { box-sizing: border-box; }
    body { font-family: "Segoe UI", Arial, sans-serif; margin: 24px 32px; color: #111827; }
    .header { text-align: center; border-bottom: 3px double #111827; padding-bottom: 10px; margin-bottom: 18px; }
    .header h1 { font-size: 16pt; margin: 0 0 4px; letter-spacing: 1px; }
    .header .sub { font-size: 10pt; color: #374151; margin: 1px 0; }
    .meta { margin-bottom: 14px; font-size: 10pt; }
    .meta table { width: 100%; border-collapse: collapse; }
    .meta td { padding: 2px 4px; vertical-align: top; }
    .meta td:first-child { width: 130px; font-weight: 600; }
    table.data { width: 100%; border-collapse: collapse; font-size: 9pt; }
    table.data th, table.data td { border: 1px solid #6B7280; padding: 5px 7px; }
    table.data th { background: #E5E7EB; text-align: center; font-weight: 700; }
    td.driver { font-weight: 600; }
    td.center { text-align: center; }
    td.empty { text-align: center; color: #6B7280; font-style: italic; padding: 14px; }
    .summary { margin-top: 14px; font-size: 10pt; }
    .summary table { border-collapse: collapse; }
    .summary th, .summary td { border: 1px solid #6B7280; padding: 4px 10px; font-size: 9pt; }
    .summary th { background: #E5E7EB; }
    .footer { margin-top: 26px; font-size: 9pt; color: #374151; display: flex; justify-content: space-between; }
    .signature { text-align: center; width: 200px; }
    .signature .space { height: 54px; }
    @page { size: A4 landscape; margin: 14mm; }
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }
    @media print { .no-print { display: none; } }
</style>
</head>
<body>
    <div class="header">
        <h1>REKAP ABSENSI DRIVER</h1>
        <div class="sub">Rekap ${modeLabels[mode]}</div>
        <div class="sub">${esc(periodTitle)}</div>
    </div>

    <div class="meta">
        <table>
            <tr><td>Jenis Rekap</td><td>: ${modeLabels[mode]}</td></tr>
            <tr><td>Periode</td><td>: ${esc(periodLabel)} (${esc(periodStart)} s/d ${esc(periodEnd)})</td></tr>
            <tr><td>Tanggal Cetak</td><td>: ${new Date().toLocaleString("id-ID")}</td></tr>
        </table>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th style="width:16%">Driver</th>
                <th style="width:8%">Hadir</th>
                <th style="width:10%">Terlambat</th>
                <th style="width:11%">Total Telat</th>
                <th style="width:12%">Total Jam Kerja</th>
                <th>Detail Absensi</th>
            </tr>
        </thead>
        <tbody>${rowsHtml}</tbody>
    </table>

    <div class="summary">
        <table>
            <tr>
                <th>Total Driver</th><th>Hadir</th><th>Tepat Waktu</th><th>Terlambat</th><th>Sedang Tugas</th><th>Belum Absen</th>
            </tr>
            <tr>
                <td class="center">${summary.total_drivers}</td>
                <td class="center">${summary.present}</td>
                <td class="center">${summary.on_time ?? 0}</td>
                <td class="center">${summary.late ?? 0}</td>
                <td class="center">${summary.on_trip ?? 0}</td>
                <td class="center">${summary.absent}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <div>Dicetak pada ${new Date().toLocaleString("id-ID")}</div>
        <div class="signature">
            <div>Disetujui oleh,</div>
            <div class="space"></div>
            <div>( .......................... )</div>
        </div>
    </div>

    <div class="no-print" style="text-align:center; margin-top: 18px;">
        <button onclick="window.print()" style="padding:8px 18px; font-size:10pt; cursor:pointer;">Cetak / Simpan PDF</button>
    </div>

    <script>
        window.onload = function () { window.focus(); };
    </script>
</body>
</html>`);
        printWindow.document.close();
        // Beri waktu render sebelum dialog cetak muncul.
        printWindow.focus();
        setTimeout(() => printWindow.print(), 350);
    };

    return (
        <button
            type="button"
            onClick={openPrintDocument}
            className="mmj-btn mmj-btn-primary px-3 py-2 text-xs"
        >
            <PrinterIcon className="h-4 w-4" />
            Print
        </button>
    );
}

export default function AdminDriverAttendances({
    mode,
    anchor,
    period_label,
    period_start,
    period_end,
    rows,
    summary,
    office,
}: Props) {
    const [selectedMode, setSelectedMode] = useState<Props["mode"]>(mode);
    const [selectedAnchor, setSelectedAnchor] = useState(anchor);
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const [previewAlt, setPreviewAlt] = useState<string>("");

    const applyFilter = (nextMode: Props["mode"], nextAnchor: string) => {
        setSelectedMode(nextMode);
        setSelectedAnchor(nextAnchor);
        router.get(
            route("admin.driver-attendances"),
            { mode: nextMode, anchor: nextAnchor },
            { preserveState: true, preserveScroll: true },
        );
    };

    const columns = [
        "Driver",
        "Hadir",
        "Terlambat",
        "Total Telat",
        "Total Jam Kerja",
        "Detail",
    ];

    return (
        <SectionPage
            title="Rekap Absensi Driver"
            eyebrow="Monitoring"
            description={`Periode: ${period_label} (${period_start} s/d ${period_end})`}
            actions={
                <div className="flex flex-wrap items-center gap-2">
                    <div className="flex rounded-lg border border-[#E2E8F0] bg-white p-1">
                        {MODES.map((m) => (
                            <button
                                key={m.value}
                                type="button"
                                onClick={() =>
                                    applyFilter(m.value, selectedAnchor)
                                }
                                className={`rounded px-3 py-1.5 text-xs font-semibold transition ${
                                    selectedMode === m.value
                                        ? "bg-[#2563EB] text-white"
                                        : "text-[#64748B] hover:bg-[#F1F5F9]"
                                }`}
                            >
                                {m.label}
                            </button>
                        ))}
                    </div>
                    <input
                        type="date"
                        value={selectedAnchor}
                        onChange={(e) =>
                            applyFilter(selectedMode, e.target.value)
                        }
                        className="rounded-lg border border-[#E2E8F0] px-3 py-2 text-sm focus:border-[#2563EB] focus:outline-none focus:ring-4 focus:ring-[#2563EB]/10"
                    />
                    <PrintButton
                        mode={selectedMode}
                        anchor={selectedAnchor}
                        periodLabel={period_label}
                        periodStart={period_start}
                        periodEnd={period_end}
                        rows={rows}
                        summary={summary}
                    />
                </div>
            }
        >
            {/* Kebijakan Kantor Info Box */}
            <div className="mb-6 rounded-xl border border-[#BFDBFE] bg-[#EFF6FF] p-4 text-xs text-[#1E40AF]">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div className="flex items-center gap-2 font-medium">
                        <ClockIcon className="h-4 w-4 text-[#2563EB]" />
                        <span>
                            Batas Masuk Tepat Waktu:{" "}
                            <strong>
                                {office?.cutoff_time ?? "07:30 WIB"}
                            </strong>
                        </span>
                    </div>
                    <div className="flex items-center gap-2 font-medium">
                        <CalendarIcon className="h-4 w-4 text-[#2563EB]" />
                        <span>
                            Periode: <strong>{period_label}</strong>
                        </span>
                    </div>
                </div>
            </div>

            <div className="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                <StatCard
                    tone="blue"
                    title="Total Driver"
                    value={summary.total_drivers}
                    icon={<UserGroupIcon className="h-5 w-5" />}
                />
                <StatCard
                    tone="green"
                    title="Hadir"
                    value={summary.present}
                    icon={<CheckCircleIcon className="h-5 w-5" />}
                />
                <StatCard
                    tone="green"
                    title="Tepat Waktu"
                    value={summary.on_time ?? 0}
                    icon={<ClockIcon className="h-5 w-5" />}
                />
                <StatCard
                    tone="amber"
                    title="Terlambat"
                    value={summary.late ?? 0}
                    icon={<ExclamationCircleIcon className="h-5 w-5" />}
                />
                <StatCard
                    tone="purple"
                    title="Sedang Tugas"
                    value={summary.on_trip ?? 0}
                    icon={<TruckIcon className="h-5 w-5" />}
                />
                <StatCard
                    tone="slate"
                    title="Belum Absen"
                    value={summary.absent}
                    icon={<XCircleIcon className="h-5 w-5" />}
                />
            </div>

            {/* Tabel rekap */}
            <div className="overflow-x-auto rounded-xl border border-[#E2E8F0] bg-white print:border-0">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b-2 border-[#E2E8F0] bg-[#F8FAFC] text-left text-xs uppercase tracking-wide text-[#64748B]">
                            {columns.map((col) => (
                                <th
                                    key={col}
                                    className="px-4 py-3 font-semibold"
                                >
                                    {col}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length === 0 ? (
                            <tr>
                                <td
                                    colSpan={columns.length}
                                    className="px-4 py-8 text-center text-sm text-[#94A3B8]"
                                >
                                    Belum ada absensi pada periode ini.
                                </td>
                            </tr>
                        ) : (
                            rows.map((row) => (
                                <tr
                                    key={row.driver_id}
                                    className="border-b border-[#F1F5F9] hover:bg-[#F8FAFC]"
                                >
                                    <td className="px-4 py-3 font-medium text-[#0F172A]">
                                        {row.driver_name}
                                    </td>
                                    <td className="px-4 py-3">
                                        {row.summary.present}
                                    </td>
                                    <td className="px-4 py-3">
                                        {row.summary.late}
                                    </td>
                                    <td className="px-4 py-3">
                                        {row.summary.lateness_minutes > 0
                                            ? `+${row.summary.lateness_minutes} m`
                                            : "-"}
                                    </td>
                                    <td className="px-4 py-3">
                                        {duration(row.summary.total_minutes)}
                                    </td>
                                    <td className="px-4 py-3">
                                        {row.records.length > 0 ? (
                                            <ul className="space-y-1 text-xs text-[#475569]">
                                                {row.records.map((r, i) => (
                                                    <li
                                                        key={i}
                                                        className="whitespace-nowrap"
                                                    >
                                                        {r.date}:{" "}
                                                        {time(r.clock_in_at)}
                                                        <PhotoLink
                                                            attendanceId={r.id}
                                                            type="in"
                                                            hasPhoto={Boolean(
                                                                r.clock_in_photo_path,
                                                            )}
                                                            setPreview={(url, alt) => {
                                                                setPreviewUrl(url);
                                                                setPreviewAlt(alt);
                                                            }}
                                                        />
                                                        {" → "}
                                                        {time(r.clock_out_at)}
                                                        <PhotoLink
                                                            attendanceId={r.id}
                                                            type="out"
                                                            hasPhoto={Boolean(
                                                                r.clock_out_photo_path,
                                                            )}
                                                            setPreview={(url, alt) => {
                                                                setPreviewUrl(url);
                                                                setPreviewAlt(alt);
                                                            }}
                                                        />
                                                        {r.is_late && (
                                                            <span className="ms-1 rounded bg-[#FEE2E2] px-1.5 py-0.5 text-[10px] font-semibold text-[#DC2626]">
                                                                Telat{" "}
                                                                {
                                                                    r.lateness_minutes
                                                                }
                                                                m
                                                            </span>
                                                        )}
                                                    </li>
                                                ))}
                                            </ul>
                                        ) : (
                                            <span className="text-xs text-[#94A3B8]">
                                                Tidak ada data
                                            </span>
                                        )}
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            {/* Layout cetak */}
            <div className="hidden print:block print:mt-4">
                <p className="text-xs text-[#64748B]">
                    Dicetak: {new Date().toLocaleString("id-ID")}
                </p>
            </div>

            {/* Modal pratinjau foto absensi */}
            <PhotoPreviewModal
                show={previewUrl !== null}
                onClose={() => {
                    setPreviewUrl(null);
                    setPreviewAlt("");
                }}
                photoUrl={previewUrl}
                photoAlt={previewAlt}
            />
        </SectionPage>
    );
}
