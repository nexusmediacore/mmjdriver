# UI DESIGN & ANIMATION RULES

## 1. STATUS ATURAN

Dokumen ini adalah aturan wajib untuk seluruh implementasi UI/UX aplikasi.

Semua developer dan AI Coding Agent wajib mengikuti aturan ini.

Aturan ini berlaku untuk:

* halaman baru
* komponen baru
* modal
* popup
* dropdown
* notification
* toast
* command palette
* sidebar
* form
* table
* dashboard
* mobile interface
* loading state
* empty state
* error state
* success state
* animation
* transition
* interaction

Jika implementasi baru bertentangan dengan aturan ini, implementasi tersebut harus diperbaiki sebelum dianggap selesai.

---

# 2. LIGHT MODE ONLY

Aplikasi WAJIB menggunakan **LIGHT MODE ONLY**.

DILARANG:

* Dark mode
* Theme switcher
* Dark mode toggle
* Automatic dark mode
* `prefers-color-scheme: dark`
* Komponen khusus dark mode
* CSS dark theme
* Tailwind `dark:` variant untuk UI aplikasi

Jangan membuat:

```text
Light / Dark
```

Jangan membuat tombol:

```text
☀ Light
🌙 Dark
```

Seluruh aplikasi harus tetap menggunakan light mode.

---

# 3. DESIGN PRINCIPLE

Design aplikasi harus memiliki karakter:

* Clean
* Modern
* Professional
* Enterprise
* Minimal
* Responsive
* Consistent
* Fast
* Accessible

Aplikasi harus terasa seperti sistem internal perusahaan modern.

Hindari:

* animasi berlebihan
* gradient berlebihan
* glassmorphism berlebihan
* shadow berlebihan
* efek 3D
* efek neon
* background dekoratif yang mengganggu
* elemen yang bergerak tanpa tujuan

Prioritas:

```text
Usability
>
Readability
>
Performance
>
Consistency
>
Decoration
```

---

# 4. COLOR SYSTEM

Gunakan color token yang sudah ditentukan project.

## Primary

```text
Primary       #2563EB
Primary Hover #1D4ED8
Primary Light #DBEAFE
```

## Secondary

```text
Secondary       #64748B
Secondary Light #E2E8F0
```

## Neutral

```text
Gray 900 #0F172A
Gray 700 #334155
Gray 500 #64748B
Gray 300 #CBD5E1
Gray 100 #F1F5F9
```

## Semantic

```text
Success       #16A34A
Success Light #DCFCE7

Warning       #F59E0B
Warning Light #FEF3C7

Danger        #DC2626
Danger Light  #FEE2E2

Info          #0EA5E9
Info Light    #E0F2FE
```

## Background

```text
Page     #F8FAFC
Card     #FFFFFF
Surface  #F1F5F9
Sidebar  #FFFFFF
Hover    #EFF6FF
```

Jangan membuat warna baru hanya untuk dekorasi.

Jika membutuhkan warna baru, gunakan token yang sudah tersedia terlebih dahulu.

---

# 5. TYPOGRAPHY

Gunakan typography yang:

* mudah dibaca
* profesional
* memiliki hierarchy jelas
* konsisten

Hierarchy:

```text
Page Title
Section Title
Card Title
Body
Secondary Text
Caption
```

Jangan menggunakan terlalu banyak ukuran font.

Jangan menggunakan font dekoratif.

---

# 6. GLOBAL ANIMATION RULE

Semua animation harus:

* subtle
* cepat
* smooth
* purposeful

Animation tidak boleh menghambat pekerjaan user.

Gunakan:

```text
Fast   100-150ms
Normal 150-250ms
Slow   250-350ms
```

DILARANG menggunakan animation >500ms kecuali ada alasan UX yang jelas.

---

# 7. PAGE TRANSITION

Ketika berpindah halaman:

```text
opacity: 0 → 1
transform: translateY(6px) → translateY(0)
```

Duration:

```text
150-250ms
```

Tidak boleh menggunakan:

* zoom besar
* rotate
* bounce
* 3D transition

---

# 8. BUTTON RULE

Setiap button harus memiliki:

```text
Default
Hover
Active
Focus
Disabled
Loading
```

Hover:

```text
background/color transition
```

Active:

```text
scale sekitar 0.98
```

Loading:

```text
Spinner + text
```

Contoh:

```text
Simpan
↓
⟳ Menyimpan...
```

Button harus disabled selama request berlangsung untuk mencegah double submit.

---

# 9. CARD RULE

Card menggunakan:

```text
background: #FFFFFF
border: subtle
border-radius: consistent
shadow: subtle
```

Hover:

```text
translateY(-2px)
```

Shadow dapat meningkat sedikit ketika hover.

DILARANG:

* 3D card
* excessive shadow
* bouncing card
* floating card berlebihan

---

# 10. MODAL RULE

Semua modal harus menggunakan reusable component.

Modal wajib mendukung:

* close button
* ESC
* focus management
* responsive layout
* keyboard navigation
* optional backdrop click
* loading state
* error state

Opening animation:

```text
Backdrop:
opacity 0 → 1

Modal:
opacity 0 → 1
scale 0.97 → 1
translateY(8px) → 0
```

Duration:

```text
150-250ms
```

---

# 11. CONFIRMATION DIALOG

Action penting harus menggunakan confirmation dialog.

Contoh:

```text
Hapus Driver?

Data driver ini akan dihapus.

[ Batal ] [ Hapus ]
```

Action destructive menggunakan:

```text
Danger #DC2626
```

Action approval menggunakan:

```text
Success #16A34A
```

Action warning menggunakan:

```text
Warning #F59E0B
```

---

# 12. TOAST SYSTEM

Gunakan global Toast system.

Jenis:

```text
Success
Error
Warning
Info
```

Posisi:

Desktop:

```text
Top Right
```

Mobile:

```text
Top Center
```

Toast harus mendukung:

* icon
* title
* message
* close button
* auto dismiss
* stacking

Animation:

```text
opacity: 0 → 1
translateX(20px) → translateX(0)
```

---

# 13. LOADING SYSTEM

Jangan menampilkan halaman kosong ketika data sedang loading.

Gunakan:

```text
Skeleton Loading
```

Untuk:

* Dashboard
* Table
* Driver
* Vehicle
* Trip
* Reports
* Detail page

Skeleton harus subtle.

Gunakan spinner hanya jika memang lebih sesuai.

---

# 14. EMPTY STATE

Setiap list/table yang tidak mempunyai data harus memiliki Empty State.

Contoh:

```text
Tidak ada permintaan perjalanan

Belum ada permintaan perjalanan
yang tersedia.

[ + Buat Permintaan ]
```

DILARANG menampilkan blank page tanpa informasi.

---

# 15. DROPDOWN

Dropdown menggunakan:

```text
opacity: 0 → 1
scale: 0.98 → 1
translateY(-4px) → 0
```

Duration:

```text
120-180ms
```

---

# 16. SIDEBAR

Desktop:

* smooth expand/collapse
* active state
* tooltip ketika collapsed
* submenu animation

Mobile:

* drawer
* backdrop
* slide from left
* ESC close

Sidebar tidak boleh menggunakan dark mode.

---

# 17. COMMAND PALETTE

Command Palette wajib tersedia jika fitur navigasi cepat diperlukan.

Shortcut:

```text
Ctrl + K
```

Mac:

```text
Cmd + K
```

Command Palette harus mendukung:

* search
* keyboard navigation
* Arrow Up
* Arrow Down
* Enter
* ESC
* highlighted result
* recent action
* quick action

Contoh menu:

```text
Dashboard
Permintaan Perjalanan
Driver
Kendaraan
Approval
Berita Acara
Laporan
Pengaturan
```

Command Palette harus menggunakan LIGHT MODE.

---

# 18. STATUS BADGE

Gunakan status badge yang konsisten.

Contoh:

```text
Active
Pending
Approved
Rejected
On Trip
Completed
Cancelled
Draft
Urgent
Info
```

Warna harus mengikuti semantic color system.

Jangan membuat status dengan warna acak.

---

# 19. STATUS DRIVER

Gunakan:

```text
🟢 Standby
🔵 Assigned
🟡 Waiting
🟠 On Trip
🟣 Returning
⚫ Off Duty
🔴 Unavailable
```

Status aktif boleh menggunakan subtle pulse.

Jangan membuat semua status berkedip.

---

# 20. DRIVER ASSIGNMENT

Assignment popup harus menampilkan:

```text
Driver
Status
Vehicle
Schedule
Conflict
Availability
```

Driver yang memiliki schedule conflict harus disabled.

Contoh:

```text
Budi Santoso
✓ Standby
Toyota Innova

Andi
⚠ Schedule Conflict
Disabled
```

Jangan memungkinkan dispatcher memilih driver yang sedang conflict.

---

# 21. APPROVAL UI

Approval harus menggunakan confirmation.

Approve:

```text
Success
```

Reject:

```text
Danger
```

Reject wajib dapat meminta alasan.

Setelah berhasil:

```text
✓ Berhasil
Perubahan berhasil disimpan.
```

---

# 22. DELETE UI

Delete action wajib:

```text
Confirmation Dialog
```

Jangan langsung menghapus data tanpa konfirmasi.

Gunakan soft delete jika secara teknis dan business logic memungkinkan.

---

# 23. NOTIFICATION CENTER

Notification Center harus mendukung:

```text
Unread
Read
Timestamp
Category
Action
```

Unread notification memiliki background berbeda secara subtle.

Jangan menggunakan animasi berlebihan.

---

# 24. FILTER

Filter panel harus mendukung:

```text
Status
Tanggal
Driver
Kendaraan
Departemen
Tujuan
```

Tampilkan jumlah filter aktif:

```text
Filter (3)
```

Button:

```text
Reset
Terapkan
```

Filter panel harus mempunyai transition yang smooth.

---

# 25. TABLE

Table wajib mendukung:

```text
Row hover
Selected row
Checkbox
Loading
Empty state
Pagination
Filter
Sorting
```

Hover tidak boleh terlalu mencolok.

---

# 26. MOBILE BOTTOM SHEET

Untuk mobile action tertentu dapat menggunakan Bottom Sheet.

Animation:

```text
translateY(100%) → translateY(0)
```

Gunakan untuk:

* driver actions
* filters
* quick actions
* contextual actions

---

# 27. TOOLTIP

Icon-only button wajib memiliki tooltip.

Contoh:

```text
Edit
Delete
View
Download
Filter
Refresh
```

Delay:

```text
300-500ms
```

Tooltip tidak boleh menghalangi interaction.

---

# 28. REFRESH

Refresh button hanya boleh berputar ketika request berlangsung.

```text
⟳
```

Setelah selesai:

```text
✓ Updated
```

Jangan membuat refresh icon berputar terus menerus.

---

# 29. SUCCESS ANIMATION

Success animation harus sederhana.

Contoh:

```text
✓
Berhasil
Data berhasil disimpan.
```

Jangan menggunakan confetti untuk setiap action.

Confetti hanya boleh digunakan untuk event penting seperti:

```text
Perjalanan berhasil diselesaikan
Berita Acara berhasil dibuat
```

---

# 30. ACCESSIBILITY

Semua UI harus mendukung:

* keyboard navigation
* visible focus
* ESC
* ARIA label
* semantic HTML
* readable contrast
* screen reader compatibility

Jangan mengandalkan warna saja untuk menyampaikan status.

---

# 31. REDUCED MOTION

Wajib mendukung:

```css
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }
}
```

---

# 32. COMPONENT REUSE

Jangan membuat modal/toast/dropdown baru untuk setiap halaman.

Gunakan reusable components.

Struktur yang direkomendasikan:

```text
resources/js/
└── Components/
    └── UI/
        ├── Modal.jsx
        ├── ConfirmDialog.jsx
        ├── Toast.jsx
        ├── Dropdown.jsx
        ├── Tooltip.jsx
        ├── BottomSheet.jsx
        ├── CommandPalette.jsx
        ├── Skeleton.jsx
        ├── EmptyState.jsx
        ├── LoadingButton.jsx
        └── StatusBadge.jsx
```

Jika component sudah tersedia, WAJIB gunakan component tersebut daripada membuat duplikat.

---

# 33. DEPENDENCY RULE

Jangan menambahkan animation library hanya untuk animasi sederhana.

Prioritas:

```text
Existing project utilities
>
CSS transition
>
Existing animation library
>
New dependency
```

Dependency baru harus memiliki alasan teknis yang jelas.

---

# 34. PERFORMANCE

Animation tidak boleh menyebabkan:

* layout thrashing
* excessive repaint
* lag
* memory leak
* unnecessary re-render

Prioritaskan:

```text
transform
opacity
```

Hindari animation berat pada:

```text
width
height
top
left
```

jika dapat menggunakan:

```text
transform
```

---

# 35. NO FAKE INTERACTION

Semua UI interaction yang dibuat harus benar-benar berfungsi.

DILARANG membuat:

```text
Button palsu
Popup palsu
Loading palsu
Toast palsu
Search palsu
Command Palette palsu
```

Jika backend/API belum tersedia, jangan berpura-pura bahwa action berhasil.

Gunakan state yang sesuai dan laporkan dependency yang belum tersedia.

---

# 36. RESPONSIVE

Semua component harus diuji pada:

```text
Desktop
Laptop
Tablet
Mobile
```

Minimum perhatian:

```text
360px
768px
1024px
1280px
1440px+
```

Tidak boleh ada:

* horizontal overflow yang tidak diperlukan
* modal keluar layar
* button terpotong
* table menghancurkan layout
* sidebar menutupi konten tanpa kontrol

---

# 37. IMPLEMENTATION RULE

Sebelum membuat UI baru:

1. Periksa apakah component sudah tersedia.
2. Gunakan component yang sudah ada.
3. Gunakan color token.
4. Gunakan spacing token.
5. Gunakan typography system.
6. Gunakan animation rule.
7. Pastikan responsive.
8. Pastikan accessibility.
9. Pastikan tidak ada dark mode.
10. Pastikan tidak merusak business logic.

---

# 38. AI CODING AGENT RULE

AI Coding Agent WAJIB:

1. Membaca `MASTER.md`.
2. Membaca aturan UI ini.
3. Memeriksa struktur component yang sudah ada.
4. Tidak membuat duplicate component tanpa alasan.
5. Tidak mengubah design system tanpa persetujuan.
6. Tidak menambahkan dark mode.
7. Tidak mengubah color palette secara sembarangan.
8. Tidak menambahkan dependency besar tanpa alasan.
9. Tidak menghapus fitur existing.
10. Tidak mengubah business logic hanya untuk kebutuhan visual.

Jika requirement baru bertentangan dengan aturan ini:

```text
STOP
↓
Identifikasi konflik
↓
Jelaskan konflik
↓
Jangan mengubah aturan
↓
Tunggu keputusan developer
```

---

# 39. DEFINITION OF DONE

UI dianggap selesai hanya jika:

* [ ] Light mode only
* [ ] Color system sesuai
* [ ] Typography konsisten
* [ ] Animation smooth
* [ ] Modal bekerja
* [ ] Toast bekerja
* [ ] Loading state tersedia
* [ ] Empty state tersedia
* [ ] Error state tersedia
* [ ] Responsive
* [ ] Keyboard accessible
* [ ] Reduced motion support
* [ ] Tidak ada console error
* [ ] Tidak ada duplicate component
* [ ] Tidak ada dark mode
* [ ] Tidak ada fake interaction
* [ ] Build berhasil

---

# 40. FINAL DESIGN STANDARD

Semua UI baru harus terlihat:

**Clean + Modern + Professional + Fast + Consistent + Light Mode**

Jangan mengejar animasi sebanyak mungkin.

Tujuan utama adalah:

```text
User tahu apa yang terjadi.
User tahu apa yang harus dilakukan.
User tidak menunggu animasi.
User tidak bingung dengan warna.
User tidak kehilangan konteks.
```

Jika animasi tidak meningkatkan UX, jangan gunakan animasi tersebut.
