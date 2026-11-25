# MASTER.md

# SISTEM MANAJEMEN PERJALANAN DINAS & DRIVER DISPATCH

**Project Type:** Internal Enterprise Web Application
**Architecture:** Monolithic Laravel + Inertia.js + React
**Database:** MySQL
**Development Database:** XAMPP MySQL
**Frontend:** React + Inertia.js + Tailwind CSS
**Backend:** Laravel
**Mobile Strategy:** Responsive Web / PWA-ready
**Status:** Development
**Version:** 1.0.0

---

# 1. PROJECT PURPOSE

Sistem ini dibuat untuk mengelola seluruh proses:

* permintaan perjalanan dinas
* persetujuan perjalanan
* penjadwalan driver
* penjadwalan kendaraan
* dispatch driver
* penerimaan/penolakan tugas oleh driver
* pelaksanaan perjalanan
* bantuan loading barang
* Berita Acara
* notifikasi
* histori perjalanan
* laporan
* audit aktivitas

Tujuan utama sistem:

> Menghilangkan proses permintaan driver yang masih dilakukan secara manual melalui chat, telepon, atau komunikasi informal dan menggantinya dengan sistem terpusat yang memiliki workflow, jadwal, status, approval, histori, dan audit trail.

---

# 2. CORE PRINCIPLE

Sistem harus menjadi:

> **Single Source of Truth untuk seluruh aktivitas perjalanan dinas dan penggunaan driver/kendaraan perusahaan.**

Semua perjalanan harus tercatat.

Semua penugasan driver harus tercatat.

Semua perubahan status harus tercatat.

Semua aktivitas penting harus dapat diaudit.

---

# 3. TECHNOLOGY STACK

## Backend

Gunakan:

* Laravel
* PHP 8.2+
* Laravel Eloquent ORM
* Laravel Validation
* Laravel Policies
* Laravel Gates
* Laravel Notifications
* Laravel Queue
* Laravel Events/Listeners jika diperlukan

---

## Frontend

Gunakan:

* React
* Inertia.js
* Tailwind CSS
* Vite

Jangan menggunakan Vue.

Jangan menggunakan React Native untuk versi pertama.

---

## Database

Gunakan:

**MySQL**

Development database menggunakan:

**XAMPP MySQL**

Contoh konfigurasi:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=driver_dispatch
DB_USERNAME=root
DB_PASSWORD=
```

Credential tidak boleh di-hard-code.

---

## Realtime

Jika diperlukan gunakan:

* Laravel Reverb
* Laravel Broadcasting
* Laravel Echo

Realtime digunakan terutama untuk:

* perubahan status driver
* perubahan status perjalanan
* notifikasi dispatch
* dashboard dispatcher

---

## PDF

Gunakan:

* DomPDF atau library PDF Laravel yang stabil.

Digunakan untuk:

* Berita Acara
* laporan perjalanan
* dokumen administratif

---

# 4. DEVELOPMENT ENVIRONMENT

Development utama:

```text
OS              : Windows
Web Server      : XAMPP / PHP Artisan
Database        : MySQL melalui XAMPP
PHP             : 8.2+
Composer        : Latest compatible version
Node.js         : LTS
npm             : Compatible with Node.js
Git             : Git
Editor          : VS Code
```

Project tidak boleh memiliki dependency wajib terhadap:

* PostgreSQL
* MongoDB
* Firebase sebagai database utama
* Supabase sebagai database utama
* Docker sebagai requirement development
* DBeaver sebagai workflow database

Database development harus dapat dijalankan hanya dengan:

```text
XAMPP MySQL
+
Laravel
+
Node.js
+
Composer
```

---

# 5. ARCHITECTURE

Gunakan:

```text
React
   │
   ▼
Inertia.js
   │
   ▼
Laravel
   │
   ├── Controllers
   ├── Requests
   ├── Services
   ├── Policies
   ├── Models
   ├── Notifications
   ├── Jobs
   └── Events
   │
   ▼
Eloquent ORM
   │
   ▼
MySQL
```

Laravel menjadi pusat business logic.

Frontend tidak boleh menjadi sumber utama business rules.

---

# 6. ARCHITECTURE RULES

## 6.1 Controller

Controller harus tipis.

Jangan menaruh business logic kompleks di Controller.

Gunakan Service untuk proses seperti:

```text
TripAssignmentService
TripApprovalService
TripStatusService
DriverAvailabilityService
VehicleAvailabilityService
BeritaAcaraService
NotificationService
```

---

## 6.2 Validation

Gunakan Laravel Form Request.

Contoh:

```text
StoreTripRequest
UpdateTripRequest
AssignDriverRequest
AssignVehicleRequest
ApproveTripRequest
RejectTripRequest
StartTripRequest
CompleteTripRequest
```

Jangan melakukan validasi penting hanya di frontend.

---

## 6.3 Authorization

Gunakan:

* Policies
* Gates
* Permission system

Setiap resource harus memiliki authorization.

Jangan hanya mengandalkan:

```text
if user role == admin
```

untuk seluruh sistem.

---

# 7. USER ROLES

Minimal role:

```text
SUPERADMIN
ADMIN
DISPATCHER
APPROVER
EMPLOYEE
DRIVER
```

---

# 8. SUPERADMIN

Superadmin dapat:

* mengelola user
* mengelola role
* mengelola permission
* mengelola driver
* mengelola kendaraan
* mengelola departemen
* mengakses seluruh perjalanan
* melihat audit log
* konfigurasi sistem

Superadmin adalah role tertinggi.

---

# 9. ADMIN

Admin dapat:

* mengelola master data
* mengelola driver
* mengelola kendaraan
* mengelola departemen
* mengelola user sesuai permission
* melihat perjalanan
* membuat laporan
* mengelola Berita Acara

Admin tidak boleh mengubah konfigurasi keamanan inti Superadmin kecuali diberi permission khusus.

---

# 10. DISPATCHER

Dispatcher merupakan role operasional utama.

Dapat:

* melihat seluruh request yang sudah disetujui
* melihat driver tersedia
* melihat driver yang sedang bertugas
* melihat kendaraan tersedia
* assign driver
* assign kendaraan
* melihat konflik jadwal
* memonitor perjalanan
* melakukan dispatch
* menangani driver rejection
* melihat perjalanan aktif

---

# 11. APPROVER

Approver dapat:

* melihat pengajuan bawahannya
* approve
* reject
* memberikan catatan

Reject wajib memiliki alasan.

---

# 12. EMPLOYEE

Employee dapat:

* membuat pengajuan perjalanan
* melihat pengajuan sendiri
* melihat status
* membatalkan pengajuan sesuai aturan
* melihat histori
* melihat detail perjalanan miliknya

Employee tidak dapat:

* assign driver
* mengubah status driver
* mengubah data kendaraan
* mengakses data perjalanan yang tidak memiliki hubungan dengannya

---

# 13. DRIVER

Driver dapat:

* melihat profil
* mengubah availability sesuai aturan
* melihat tugas
* menerima tugas
* menolak tugas
* melihat detail perjalanan
* memulai perjalanan
* menyelesaikan perjalanan
* mengisi catatan
* mengkonfirmasi bantuan loading
* melihat histori perjalanan

Driver hanya dapat mengakses data yang berkaitan dengan dirinya.

---

# 14. DRIVER STATUS

Status driver:

```text
STANDBY
ASSIGNED
ACCEPTED
ON_TRIP
RETURNING
COMPLETED
OFF_DUTY
LEAVE
SICK
UNAVAILABLE
```

Status harus memiliki transition rules.

Contoh:

```text
STANDBY
    ↓
ASSIGNED
    ↓
ACCEPTED
    ↓
ON_TRIP
    ↓
COMPLETED
    ↓
STANDBY
```

Driver tidak boleh memiliki dua trip aktif yang waktunya bentrok.

---

# 15. TRIP STATUS

Status perjalanan:

```text
DRAFT
SUBMITTED
WAITING_APPROVAL
APPROVED
REJECTED
WAITING_DRIVER
DRIVER_ASSIGNED
DRIVER_ACCEPTED
DRIVER_REJECTED
READY_TO_DEPART
ON_TRIP
COMPLETED
CANCELLED
```

Setiap perubahan status harus dicatat dalam:

```text
trip_status_logs
```

---

# 16. TRIP REQUEST

Form perjalanan minimal:

```text
Nomor Pengajuan
Pemohon
Departemen
Tanggal
Jam Berangkat
Estimasi Jam Kembali
Tujuan
Alamat Tujuan
Keperluan
Jumlah Penumpang
Daftar Penumpang
Prioritas
Catatan
Lampiran
Membutuhkan Bantuan Loading
```

Priority:

```text
NORMAL
URGENT
VERY_URGENT
```

Penggunaan priority tinggi harus tercatat dalam audit log.

---

# 17. APPROVAL WORKFLOW

Workflow utama:

```text
EMPLOYEE
   ↓
SUBMIT REQUEST
   ↓
WAITING APPROVAL
   ↓
APPROVER
   ├── REJECT
   └── APPROVE
          ↓
     DISPATCHER
          ↓
     ASSIGN DRIVER
          ↓
     ASSIGN VEHICLE
          ↓
       DRIVER
       ├── ACCEPT
       └── REJECT
          ↓
      TRIP
```

---

# 18. DRIVER DISPATCH

Dispatcher harus dapat melihat:

```text
DRIVER
--------------------------------
🟢 Ahmad      STANDBY
🟢 Budi       STANDBY
🟡 Candra     ON_TRIP
🔴 Dedi       OFF_DUTY
🟢 Eko        STANDBY
```

Dispatcher dapat memilih driver.

Sebelum assignment sistem wajib melakukan:

```text
availability check
schedule conflict check
driver status check
vehicle compatibility check
loading requirement check
```

---

# 19. SCHEDULE CONFLICT

Sistem wajib mencegah benturan.

Contoh:

```text
Driver A

Trip 1
08:00 - 12:00

Trip 2
10:00 - 14:00
```

Trip 2 harus ditolak dengan pesan:

```text
Driver tidak tersedia pada waktu tersebut karena memiliki perjalanan lain.
```

Hal yang sama berlaku untuk kendaraan.

---

# 20. VEHICLE MANAGEMENT

Master kendaraan:

```text
Plat Nomor
Merk
Model
Jenis
Tahun
Kapasitas Penumpang
Status
Driver Utama
Catatan
```

Status:

```text
AVAILABLE
ASSIGNED
ON_TRIP
MAINTENANCE
OUT_OF_SERVICE
```

---

# 21. LOADING ASSISTANCE

Driver memiliki konfigurasi:

```text
Bersedia Membantu Loading
YES
NO
```

Trip request memiliki:

```text
requires_loading_assistance
```

Jika request membutuhkan loading:

```text
requires_loading_assistance = true
```

sistem harus mempertimbangkan driver yang bersedia membantu loading.

---

# 22. PURCHASING WORKFLOW

Purchasing dapat mengajukan perjalanan yang membutuhkan:

```text
Pengambilan barang
Pengiriman barang
Loading
Unloading
Dokumen
Keperluan operasional
```

Sistem harus dapat menyimpan kebutuhan loading sebagai bagian dari trip.

---

# 23. DRIVER DASHBOARD

Dashboard driver harus mobile-friendly.

Tampilkan:

```text
Nama Driver
Status
Tugas Aktif
Tugas Berikutnya
Histori
```

Assignment:

```text
PERMINTAAN PENUGASAN

Tujuan
Tanggal
Jam
Pemohon
Departemen
Penumpang
Keperluan
Kendaraan

[ TERIMA ]
[ TOLAK ]
```

Reject wajib meminta alasan.

---

# 24. TRIP EXECUTION

Driver dapat:

```text
MULAI PERJALANAN
```

Sistem mencatat:

```text
started_at
started_by
```

Setelah perjalanan selesai:

```text
SELESAIKAN PERJALANAN
```

Sistem mencatat:

```text
completed_at
completed_by
completion_notes
```

Perjalanan tidak dapat diselesaikan sebelum dimulai.

---

# 25. NOTIFICATION

Gunakan Laravel Notifications.

Notification channel dapat berupa:

```text
Database
Browser
Email
WhatsApp integration
```

WhatsApp tidak wajib pada versi awal.

---

# 26. NOTIFICATION EVENT

Employee:

```text
Trip Submitted
Trip Approved
Trip Rejected
Driver Assigned
Trip Completed
```

Dispatcher:

```text
New Trip Request
Urgent Request
Driver Rejected
Schedule Conflict
Trip Completed
```

Driver:

```text
New Assignment
Schedule Changed
Assignment Cancelled
Trip Reminder
```

---

# 27. BERITA ACARA

Sistem harus dapat membuat BA otomatis.

Minimal:

```text
Nomor BA
Nomor Trip
Tanggal
Pemohon
Departemen
Driver
Kendaraan
Nomor Polisi
Tujuan
Keperluan
Waktu Berangkat
Waktu Kembali
Catatan
```

Output:

```text
PDF
```

Nomor BA harus unik.

---

# 28. REPORTING

Laporan perjalanan:

```text
Total perjalanan
Perjalanan per departemen
Perjalanan per driver
Perjalanan per kendaraan
Tujuan paling sering
Perjalanan urgent
```

Laporan driver:

```text
Jumlah perjalanan
Jumlah penolakan
Durasi perjalanan
Loading assistance
```

Laporan kendaraan:

```text
Jumlah perjalanan
Frekuensi penggunaan
Downtime
```

Export:

```text
PDF
Excel
```

---

# 29. SEARCH & FILTER

Minimal filter:

```text
Tanggal
Driver
Kendaraan
Departemen
Pemohon
Status
Tujuan
Prioritas
```

Gunakan pagination.

---

# 30. DASHBOARD DISPATCHER

Dashboard harus menampilkan:

```text
Total Request Hari Ini
Menunggu Approval
Menunggu Driver
Driver Standby
Driver On Trip
Kendaraan Available
Trip Aktif
Trip Selesai
Trip Urgent
```

Tambahkan:

* request terbaru
* jadwal hari ini
* driver availability
* vehicle availability
* conflict warning
* aktivitas terbaru

---

# 31. DASHBOARD ADMIN

Tampilkan:

```text
Total User
Total Driver
Total Kendaraan
Total Departemen
Total Trip
Trip Aktif
Trip Selesai
Trip Bulan Ini
```

---

# 32. DATABASE TABLES

Minimal:

```text
users
roles
permissions
departments

drivers
driver_status_logs

vehicles
vehicle_status_logs

trip_requests
trip_passengers
trip_approvals
trip_assignments
trip_status_logs

loading_requests

notifications

berita_acaras

attachments

audit_logs
```

---

# 33. DATABASE RULES

Gunakan:

* foreign keys
* indexes
* unique constraints
* timestamps
* soft deletes jika diperlukan

Jangan membuat database dengan foreign key yang tidak jelas.

Semua migration harus reversible jika memungkinkan.

Gunakan:

```text
php artisan migrate
php artisan migrate:rollback
```

sebagai bagian testing migration.

---

# 34. AUDIT LOG

Aktivitas penting harus disimpan.

Minimal:

```text
user_id
action
module
record_type
record_id
old_value
new_value
ip_address
user_agent
created_at
```

Contoh:

```text
Dispatcher Fiqhi
ASSIGN_DRIVER
Trip #TRIP-00021

Old:
driver_id = null

New:
driver_id = 15

2026-10-09 08:30:21
```

Audit log tidak boleh dapat dimanipulasi oleh Employee atau Driver.

---

# 35. SECURITY

Implementasikan:

* authentication
* authorization
* role based access control
* policy
* CSRF
* validation
* rate limiting
* secure upload
* MIME validation
* authorization setiap resource
* audit trail

Jangan mempercayai:

```text
ID dari URL
ID dari request body
Role dari frontend
Status dari frontend
```

Semua harus divalidasi server-side.

---

# 36. FILE UPLOAD

Attachment harus memiliki:

* allowed MIME types
* maximum file size
* generated filename
* secure storage
* authorization

Jangan menggunakan filename asli sebagai path penyimpanan langsung.

---

# 37. UI/UX

Design:

```text
Modern
Clean
Professional
Corporate
Responsive
Simple
Fast
```

Gunakan:

* cards
* badges
* status indicators
* modal
* confirmation dialogs
* toast
* searchable tables
* filters
* pagination
* loading state
* empty state
* error state

Hindari UI yang terlalu ramai.

---

# 38. COLOR STATUS

Gunakan semantic colors:

```text
Success → Green
Warning → Yellow
Danger → Red
Info → Blue
Neutral → Gray
```

Jangan menggunakan warna hanya sebagai dekorasi.

---

# 39. RESPONSIVE

Desktop:

```text
Admin
Dispatcher
Management
```

Mobile:

```text
Driver
Employee
```

Driver interface harus dapat digunakan dengan nyaman melalui HP.

---

# 40. PERFORMANCE

Wajib memperhatikan:

* eager loading
* pagination
* database indexes
* query optimization
* queue
* caching jika diperlukan

Hindari N+1 query.

Jangan mengambil seluruh tabel jika hanya membutuhkan 10 record.

---

# 41. TESTING

Gunakan Laravel testing.

Minimal:

## Authentication

```text
login
logout
unauthorized access
```

## Trip

```text
create
submit
approve
reject
assign driver
assign vehicle
accept
reject
start
complete
cancel
```

## Conflict

```text
driver conflict
vehicle conflict
```

## Authorization

```text
employee cannot access admin
driver cannot access another driver data
dispatcher can assign driver
admin can manage master
```

---

# 42. SEEDER

Seeder development harus menyediakan:

```text
1 Superadmin
1 Admin
2 Dispatcher
2 Approver
10 Employee
5 Driver
5 Vehicle
5 Department
```

Tambahkan dummy trip untuk testing.

Seeder tidak boleh digunakan sebagai pengganti data production.

---

# 43. PROJECT STRUCTURE

Struktur yang diharapkan:

```text
app/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Middleware/
│
├── Models/
│
├── Policies/
│
├── Services/
│
├── Notifications/
│
├── Jobs/
│
├── Events/
│
└── Listeners/

database/
├── migrations/
├── seeders/
└── factories/

resources/
├── js/
│   ├── Components/
│   ├── Layouts/
│   ├── Pages/
│   │   ├── Admin/
│   │   ├── Dispatcher/
│   │   ├── Employee/
│   │   ├── Driver/
│   │   └── Approver/
│   └── app.jsx
│
└── css/

routes/
├── web.php
└── console.php

storage/

tests/
├── Feature/
└── Unit/
```

Struktur dapat berkembang jika kebutuhan sistem bertambah.

---

# 44. DEVELOPMENT PHASE

Pengembangan wajib dilakukan bertahap.

## PHASE 1 — FOUNDATION

Implementasikan:

* Laravel setup
* React
* Inertia
* Tailwind
* MySQL
* Authentication
* Roles
* Permissions
* User
* Department
* Driver
* Vehicle
* Dashboard
* Migration
* Seeder

---

## PHASE 2 — TRIP REQUEST

Implementasikan:

* create trip
* edit trip
* submit trip
* validation
* attachment
* trip list
* trip detail
* status

---

## PHASE 3 — APPROVAL

Implementasikan:

* approval
* rejection
* approval history
* notification

---

## PHASE 4 — DISPATCH

Implementasikan:

* driver availability
* vehicle availability
* assignment
* conflict detection
* dispatch dashboard

---

## PHASE 5 — DRIVER

Implementasikan:

* driver dashboard
* assignment notification
* accept
* reject
* rejection reason
* status update

---

## PHASE 6 — TRIP EXECUTION

Implementasikan:

* start trip
* complete trip
* trip status history
* driver history

---

## PHASE 7 — LOADING

Implementasikan:

* loading requirement
* driver loading preference
* loading assignment
* loading history

---

## PHASE 8 — BERITA ACARA

Implementasikan:

* BA generation
* numbering
* PDF
* download
* history

---

## PHASE 9 — REPORTING

Implementasikan:

* dashboard analytics
* trip report
* driver report
* vehicle report
* department report
* PDF
* Excel

---

## PHASE 10 — REALTIME

Implementasikan jika diperlukan:

* Laravel Reverb
* realtime driver status
* realtime dispatch
* realtime notifications

---

## PHASE 11 — SECURITY & PERFORMANCE

Lakukan:

* security audit
* authorization audit
* query optimization
* N+1 check
* validation audit
* file upload audit
* permission audit
* performance testing

---

# 45. DEVELOPMENT RULE

AI CODING AGENT WAJIB mengikuti:

```text
ANALYZE
↓
PLAN
↓
IMPLEMENT
↓
TEST
↓
VERIFY
↓
DOCUMENT
```

Jangan langsung mengubah banyak file tanpa memahami struktur project.

---

# 46. EXISTING CODE PROTECTION

Jika project sudah memiliki kode:

**JANGAN menghapus atau mengganti kode existing secara sembarangan.**

Sebelum mengubah:

1. baca file terkait
2. pahami dependency
3. cari penggunaan fungsi/class
4. identifikasi risiko
5. buat perubahan minimal
6. jalankan test

Jika perubahan besar diperlukan, jelaskan alasan sebelum melakukan perubahan.

---

# 47. NO FAKE COMPLETION

AI tidak boleh mengatakan:

```text
Feature completed
```

jika:

* backend belum dibuat
* migration belum dibuat
* frontend belum terhubung
* validation belum ada
* authorization belum ada
* test belum dilakukan

"UI terlihat bagus" bukan berarti fitur selesai.

---

# 48. NO HARDCODE BUSINESS DATA

Jangan hard-code:

```text
driver
vehicle
department
status
user
trip
```

Data tersebut harus berasal dari database.

Configuration yang memang statis boleh berada di:

```text
config/
.env
```

---

# 49. BUSINESS LOGIC

Business logic penting harus berada di backend.

Contoh:

```text
CanDriverAcceptTrip
CanDriverStartTrip
CanDriverCompleteTrip
CanAssignDriver
CanAssignVehicle
HasScheduleConflict
CanApproveTrip
CanCancelTrip
```

Frontend hanya memberikan UI.

Backend adalah sumber kebenaran.

---

# 50. TRANSACTION

Gunakan database transaction untuk proses kritis.

Contoh:

```text
Assign Driver
+
Assign Vehicle
+
Create Assignment Log
+
Update Trip Status
+
Create Notification
```

Jika proses utama gagal, data tidak boleh berada dalam kondisi setengah jadi.

---

# 51. CONCURRENCY

Sistem harus memperhatikan race condition.

Contoh:

Dispatcher A memilih Driver X.

Pada waktu yang sama Dispatcher B juga memilih Driver X.

Sistem harus melakukan pengecekan availability saat transaksi assignment dilakukan.

Tidak cukup hanya melakukan pengecekan saat halaman dibuka.

---

# 52. STATUS TRANSITION

Jangan mengizinkan status berubah sembarangan.

Contoh:

```text
COMPLETED → WAITING_APPROVAL
```

tidak diperbolehkan melalui workflow normal.

Gunakan service khusus untuk status transition.

---

# 53. API / DATA CONTRACT

Jika menggunakan endpoint/API internal, dokumentasikan:

```text
Endpoint
Method
Authentication
Authorization
Request
Response
Validation
Error
```

Jangan membuat API yang tidak diperlukan.

---

# 54. ERROR HANDLING

User harus menerima pesan yang mudah dimengerti.

Jangan tampilkan SQL error mentah.

Contoh buruk:

```text
SQLSTATE[23000]
```

Contoh baik:

```text
Driver tersebut sudah memiliki perjalanan pada waktu tersebut.
Silakan pilih driver lain.
```

Error teknis tetap dicatat di log server.

---

# 55. LOGGING

Gunakan Laravel logging.

Catat error penting:

```text
authentication failure
authorization failure
assignment failure
notification failure
PDF generation failure
database failure
```

Jangan menyimpan password atau secret ke log.

---

# 56. DOCUMENTATION

Project harus memiliki:

```text
README.md
ARCHITECTURE.md
DATABASE.md
SECURITY.md
DEPLOYMENT.md
CHANGELOG.md
```

Dokumentasi harus diperbarui jika architecture berubah.

---

# 57. REQUIRED COMMANDS

Basic setup:

```bash
composer install

npm install

php artisan key:generate

php artisan migrate

php artisan db:seed

npm run dev

php artisan serve
```

Testing:

```bash
php artisan test
```

Production build:

```bash
npm run build
```

Jika Reverb digunakan:

```bash
php artisan reverb:start
```

Jika queue digunakan:

```bash
php artisan queue:work
```

---

# 58. GIT RULES

Gunakan Git.

Commit harus menggambarkan perubahan.

Contoh:

```text
feat: add trip request workflow
feat: add driver dispatch
fix: prevent overlapping driver assignment
fix: validate vehicle availability
refactor: extract trip assignment service
test: add trip conflict tests
```

Jangan menggunakan commit:

```text
update
fix
test
aaa
final
final2
final-benar
```

---

# 59. ENVIRONMENT SECURITY

`.env` tidak boleh di-commit.

Gunakan:

```text
.env.example
```

Jangan menyimpan:

* database password
* API key
* secret
* token
* credential

di source code.

---

# 60. ACCEPTANCE CRITERIA

Sistem dianggap benar-benar selesai apabila:

```text
[ ] Employee dapat membuat trip request
[ ] Employee dapat melihat status
[ ] Approver dapat approve
[ ] Approver dapat reject
[ ] Dispatcher dapat melihat request
[ ] Dispatcher dapat melihat driver standby
[ ] Dispatcher dapat assign driver
[ ] Dispatcher dapat assign vehicle
[ ] Sistem mendeteksi driver conflict
[ ] Sistem mendeteksi vehicle conflict
[ ] Driver menerima notification
[ ] Driver dapat accept
[ ] Driver dapat reject
[ ] Driver wajib memberikan alasan saat reject
[ ] Driver dapat start trip
[ ] Driver dapat complete trip
[ ] Loading assistance berjalan
[ ] BA dapat dibuat
[ ] BA dapat di-export PDF
[ ] Report dapat dibuat
[ ] Role authorization berjalan
[ ] Audit log berjalan
[ ] Responsive
[ ] Migration berhasil
[ ] Seeder berhasil
[ ] Test berhasil
[ ] Production build berhasil
```

---

# 61. DEFINITION OF DONE

Sebuah fitur hanya boleh dianggap **DONE** jika:

```text
Requirement
    ↓
Database
    ↓
Migration
    ↓
Model
    ↓
Backend
    ↓
Validation
    ↓
Authorization
    ↓
Frontend
    ↓
Error Handling
    ↓
Testing
    ↓
Verification
    ↓
Documentation
```

Jika salah satu bagian penting belum ada, statusnya:

```text
IN PROGRESS
```

bukan:

```text
DONE
```

---

# 62. AI CODING AGENT BEHAVIOR

Jika menemukan error:

```text
1. Reproduce
2. Identify root cause
3. Fix
4. Test
5. Verify
6. Report
```

Jangan menutupi error dengan:

* menghapus fitur
* menonaktifkan validation
* menggunakan dummy data
* hard-code response
* mengubah requirement

---

# 63. PRIORITY

Jika terjadi konflik antara beberapa kebutuhan, gunakan prioritas:

```text
1. Security
2. Data Integrity
3. Business Logic
4. Correctness
5. Maintainability
6. Performance
7. UX
8. Visual Design
```

Tampilan bagus tidak boleh mengorbankan keamanan atau integritas data.

---

# 64. FINAL ARCHITECTURE

Target architecture:

```text
                    ┌────────────────────────┐
                    │       EMPLOYEE         │
                    └───────────┬────────────┘
                                │
                                ▼
                    ┌────────────────────────┐
                    │     TRIP REQUEST       │
                    └───────────┬────────────┘
                                │
                                ▼
                    ┌────────────────────────┐
                    │        APPROVAL         │
                    └───────────┬────────────┘
                                │
                                ▼
                    ┌────────────────────────┐
                    │       DISPATCHER        │
                    └───────────┬────────────┘
                                │
                    ┌───────────┴───────────┐
                    ▼                       ▼
             ┌─────────────┐         ┌─────────────┐
             │   DRIVER    │         │   VEHICLE   │
             └──────┬──────┘         └──────┬──────┘
                    └───────────┬───────────┘
                                ▼
                    ┌────────────────────────┐
                    │    TRIP EXECUTION      │
                    └───────────┬────────────┘
                                │
                    ┌───────────┴────────────┐
                    ▼                        ▼
             ┌─────────────┐          ┌─────────────┐
             │ BERITA ACARA│          │   REPORTING  │
             └─────────────┘          └─────────────┘
```

---

# 65. FIRST TASK

Ketika AI Coding Agent pertama kali membaca file `MASTER.md`, lakukan **PHASE 1 — FOUNDATION**.

Jangan langsung mengerjakan seluruh sistem.

Langkah pertama:

```text
1. Inspect existing project
2. Inspect installed dependencies
3. Inspect Laravel version
4. Inspect PHP version
5. Inspect Node version
6. Inspect database configuration
7. Inspect existing migrations
8. Inspect existing React/Inertia structure
9. Identify conflicts with MASTER.md
10. Create implementation plan
```

Setelah analisis selesai:

```text
STOP
```

dan tampilkan:

```text
PROJECT STATUS
ARCHITECTURE STATUS
DATABASE STATUS
DEPENDENCY STATUS
CONFLICTS
PHASE 1 PLAN
FILES THAT WILL BE CREATED
FILES THAT WILL BE MODIFIED
RISKS
```

Jangan melakukan destructive changes sebelum project structure dipahami.

---

# 66. IMPORTANT

`MASTER.md` adalah **source of truth arsitektur project**.

AI Coding Agent tidak boleh mengubah isi `MASTER.md` secara otomatis.

Jika ada requirement baru yang bertentangan dengan dokumen ini:

```text
STOP
→ identifikasi konflik
→ jelaskan konflik
→ jangan mengubah MASTER.md
→ tunggu keputusan developer
```

---

# 67. FITUR YANG TERSEDIA SAAT INI (IMPLEMENTED)

Bagian ini mendokumentasikan fitur yang **sudah diimplementasikan** pada codebase saat ini.
Status: Development (belum production).

## 67.1 Stack & Dependency Aktif

```text
Backend   : Laravel 13.x, PHP 8.2 (dijalankan via XAMPP)
Frontend  : React + Inertia.js + Tailwind CSS + Vite
Database  : MySQL (XAMPP)
PDF       : Barryvdh DomPDF
API Mobile: Laravel Sanctum (token)
WhatsApp  : Fonnte / Wablas (gateway) atau tautan wa.me (fallback)
```

---

## 67.2 Authentication & Session

```text
[x] Register
[x] Login / Logout
[x] Lupa password / reset password
[x] Verifikasi email
[x] Konfirmasi password
[x] Ubah password
[x] Kelola profil (nama, email, telepon, dll.)
```

---

## 67.3 Role & Hak Akses

Role yang tersedia (seed `RolePermissionSeeder`):

```text
SUPERADMIN, ADMIN, DISPATCHER, APPROVER, EMPLOYEE, DRIVER
```

Permission master (seed) tersedia untuk: manage_users, manage_roles,
manage_departments, manage_drivers, manage_vehicles, manage_trips,
dispatch_trips, approve_trips, create_trips, view_reports, view_audit_log,
generate_ba.

Otorisasi aktual ditegakkan melalui `TripRequestPolicy` dan pengecekan role
di controller (bukan hanya `if role == admin`).

---

## 67.4 Admin (SectionController)

```text
[x] User Management        — list, tambah, ubah, hapus
[x] Driver Management      — list, tambah, ubah, hapus, ubah status, atur kendaraan
[x] Vehicle Management     — list, tambah, ubah, hapus, atur driver
[x] Department Management  — list, tambah, ubah, hapus, sinkronisasi anggota
[x] Berita Acara           — daftar & hapus
[x] Audit Log              — lihat 100 aktivitas terakhir
[x] Settings WhatsApp      — pilih driver, token, endpoint
```

Catatan:
- Driver hanya dapat diubah status manual ke STANDBY / SICK / LEAVE.
- Departemen BOD tidak dapat dihapus.
- User yang masih punya trip/approval tidak dapat dihapus.

---

## 67.5 Trip Request (TripController + TripService)

```text
[x] Buat trip (BUSINESS_TRIP / GUEST_PICKUP)
[x] Nomor trip otomatis  : TRIP-<tahun>-<urutan> (reset per tahun)
[x] Daftar trip          : pagination + sorting + filter per role
[x] Detail trip          : penumpang, approval, assignment, status log,
                            loading request, berita acara, attachment
[x] Submit trip
[x] Approve / Reject (reject wajib alasan)
[x] Assign driver + kendaraan
[x] Driver terima / tolak tugas
[x] Mulai perjalanan
[x] Selesaikan perjalanan
[x] Edit / perbarui trip (hanya DRAFT, oleh pemohon atau Admin/SuperAdmin;
    SuperAdmin dapat mengedit semua field dalam status apa pun termasuk COMPLETED;
    non-DRAFT CARGO_PICKUP oleh SuperAdmin hanya mengubah loading_support_driver_ids)
[x] SuperAdmin edit trip non-Draft (semua field, semua status)
[x] Loading support driver selection untuk CARGO_PICKUP non-Draft oleh SuperAdmin
[x] Driver dapat membuat trip BOD (hanya departemen BOD, driver sendiri)
[x] Backfill Berita Acara untuk trip COMPLETED lama (`php artisan berita-acara:backfill`)
[x] Preview foto absensi masuk & pulang dalam modal (SuperAdmin/Admin/Dispatcher)
[x] Driver tidak dapat submit trip request (hanya sampai DRAFT)
[x] BoD departemen: input hanya tanggal & waktu berlaku (kode & nama terkunci)
[x] Hapus trip (soft delete)
[x] Deteksi konflik jadwal driver
[x] Deteksi konflik jadwal kendaraan
[x] Cek kompatibilitas driver–kendaraan
[x] Cek kesediaan loading (requires_loading_assistance)
```

Alur prioritas:
- Employee / Admin departemen hanya dapat **mengusulkan** prioritas
  (`requested_priority` + `urgency_notes`).
- SuperAdmin menetapkan prioritas final saat approve.

Trip BOD (Board of Directors):
- Otomatis disetujui tanpa approval.
- Driver dipilih langsung saat membuat trip.
- Assignment + kirim permintaan ke WhatsApp driver otomatis.

Pencatatan:
- Setiap perubahan status trip dicatat ke `trip_status_logs`.
- Aksi penting dicatat ke `audit_logs` (create, submit, approve, reject,
  assign, accept, reject driver, start, complete, dll.).

---

## 67.6 Approval (Approver)

```text
[x] Pending approvals (per departemen approver)
[x] Riwayat trip departemen (COMPLETED / CANCELLED / REJECTED)
```

---

## 67.7 Dispatcher

```text
[x] Driver availability
[x] Vehicle availability
[x] Active trips
[x] Schedule 7 hari ke depan
```

---

## 67.8 Driver

Web:
```text
[x] Dashboard (tugas aktif & tugas berikutnya)
[x] My Tasks (daftar tugas)
[x] Trip History
[x] Terima / tolak / mulai / selesai trip
```

Mobile API (Sanctum, prefix `/api/driver`):
```text
[x] Login / refresh / logout / register device
[x] Profil driver (me)
[x] Tasks: list, detail, accept, reject, start, complete, update lokasi
[x] Vehicle: lihat & ubah status
[x] Notifications: list & tandai dibaca
```

---

## 67.9 Berita Acara

```text
[x] Generate BA (hanya untuk trip COMPLETED)
[x] Nomor BA unik : BA-<tahun>-<urutan>
[x] Download PDF
[x] Daftar BA (admin) & hapus
```

---

## 67.10 Reporting

```text
[x] Laporan perjalanan dengan filter rentang tanggal
[x] Statistik: total, selesai, dibatalkan, pending, penumpang, urgent
[x] Rekap per departemen / driver / kendaraan
[x] Export PDF
```

Belum tersedia: export Excel.

---

## 67.11 Notifikasi

```text
[x] Database notification
[x] Trip Submitted, Trip Approved, Trip Rejected
[x] Trip Created (ke SuperAdmin), Driver Assigned
[x] WhatsApp: auto via Fonnte/Wablas, fallback tautan wa.me, mode log
```

Konfigurasi WhatsApp dapat diatur dari halaman Settings (driver, token,
endpoint) dan tersimpan di tabel `settings`.

---

## 67.12 Skema Database Aktif

```text
users, roles, permissions, role_has_permissions, departments
drivers, driver_status_logs, driver_vehicle
vehicles, vehicle_status_logs
trip_requests, trip_passengers, trip_approvals, trip_assignments,
trip_status_logs
loading_requests
berita_acaras
attachments
audit_logs
notifications
settings
personal_access_tokens
jobs, cache
```

---

## 67.13 Testing

Test fitur yang tersedia antara lain:

```text
TripWorkflowTest, TripPriorityApprovalTest, TripNumberGenerationTest,
TripIndexSortingTest, TripDepartmentValidationTest,
BodTripAutoApprovalTest, DriverVehicleCompatibilityTest,
DriverStatusRestrictionTest, DriverStatusAfterCompleteTest,
DriverFromUserFlowTest, DriverFormAvailableUsersTest,
DriverAssignmentWhatsAppTest, AvailableDriverUsersApiTest,
DepartmentManagementTest, StoreDriverDuplicateLicenseTest,
ReportPdfTest, ReportByVehicleModelTest,
WhatsAppNotificationTest, WhatsAppFallbackTest, WhatsAppAutoSendTest,
AuthorizationTest, AuthenticationTest, ProfileTest,
UserRoleDropdownTest, UserFormValidationTest, UserFormCreateTest,
DriverPageAccessTest, DriverMyTasksPageAccessTest, DriverMyTasksErrorTest
```

---

## 67.14 Belum Diimplementasikan (Gap Saat Ini)

```text
[ ] Cancel trip (service ada, route/UI belum diekspos)
[ ] Upload attachment (tabel ada, route/UI belum ada)
[ ] Export Excel laporan
[ ] Realtime (Laravel Reverb/Echo)
[ ] Pengelolaan loading request (baru dibuat, belum ada alur lengkap)
[ ] PWA / mobile app native
```

Backfill Berita Acara untuk trip COMPLETED lama sudah tersedia via:
`php artisan berita-acara:backfill`

---

## 67.15 FITUR BARU YANG BARU DIMPLEMENTASI (Terbaru)

Berikut fitur yang baru ditambahkan ke sistem:

```text
1. SuperAdmin Edit Trip Semua Status
   - SuperAdmin dapat mengedit semua field trip dalam status apa pun
     (termasuk COMPLETED), bukan hanya DRAFT.
   - Backend: TripController::update(), TripService::updateTrip(),
     UpdateTripRequest::rules() (departure_time tanpa after_or_equal:now
     untuk SuperAdmin).
   - Frontend: Tombol edit muncul untuk SuperAdmin pada trip non-Draft
     di Trips/Index.tsx; Trips/Edit.tsx memuat loading_support_driver_ids
     dan bod_link dari data trip.

2. Loading Support Driver Selection untuk CARGO_PICKUP Non-Draft
   - SuperAdmin dapat mengubah loading_support_driver_ids pada trip
     CARGO_PICKUP meski di luar status DRAFT.
   - Audit log mencatat is_superadmin_edit.

3. BoD Departemen: Input Hanya Tanggal & Waktu Berlaku
   - Kode dan nama departemen BOD terkunci (tidak dapat diubah).
   - Form tambah/edit departemen menambahkan effective_date dan
     effective_time.
   - Backend: SectionController::storeDepartment()/updateDepartment()
     memaksa code=CODE_BOD dan name='Board of Directors'.

4. Driver Tidak Dapat Submit Trip Request
   - Employee/DRIVER tidak dapat mengajukan submit trip request;
     trip hanya sampai status DRAFT.
   - Backend: TripController::submit() blokir EMPLOYEE.
   - Frontend: Trips/Show.tsx canSubmit hanya SUPERADMIN/ADMIN.

5. Driver Dapat Buat Trip BOD
   - Role DRIVER diizinkan membuat trip (hanya departemen BOD,
     memilih diri sendiri sebagai driver).
   - Backend: StoreTripRequest, TripRequestPolicy, TripService.
   - Frontend: Driver/Dashboard.tsx tombol "Ajukan Perjalanan BoD".

6. Backfill Berita Acara untuk Trip COMPLETED Lama
   - Artisan command: php artisan berita-acara:backfill
   - Membuat record Berita Acara untuk semua trip COMPLETED yang
     belum memiliki record (idempoten).

7. Preview Foto Absensi dalam Modal
   - SuperAdmin/Admin/Dispatcher dapat melihat foto absensi masuk
     dan pulang driver dalam modal (bukan buka tab baru).
   - Komponen baru: resources/js/Components/PhotoPreviewModal.tsx.
   - Diintegrasi ke Admin/DriverAttendances.tsx.

8. Audit Log untuk SuperAdmin Edit Trip
   - Setiap edit trip oleh SuperAdmin dicatat ke audit_logs dengan
     flag is_superadmin_edit.
```

---

---

# END OF MASTER.md
