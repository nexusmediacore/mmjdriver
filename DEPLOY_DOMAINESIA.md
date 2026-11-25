
# Panduan & Aturan Deployment Otomatis ke DomaiNesia (cPanel)

Dokumen ini adalah instruksi standar bagi AI Coding Assistant saat pengguna meminta untuk melakukan **deploy ke DomaiNesia**.

## 1. Trigger Perintah

Jalankan alur di bawah ini secara berurutan setiap kali pengguna memberikan instruksi seperti:

- "deploy ke domainesia"
- "build dan upload ke hosting"
- "update aplikasi ke production"

## 2. Langkah Eksekusi Wajib

1. **Verifikasi Build Lokal:**
   - Jalankan perintah build produksi (`npm run build` untuk React/Vite).
   - **STOP** proses deploy jika ditemukan error pada saat proses *build* atau *linting*, dan segera perbaiki error tersebut terlebih dahulu.
2. **Cek File Kredensial & Skrip:**
   - Pastikan skrip `deploy-domainesia.mjs` sudah ada di root project. Jika belum ada, buatkan terlebih dahulu menggunakan template di bagian bawah dokumen ini dan install dependensi `basic-ftp` serta `dotenv` (`npm install --save-dev basic-ftp dotenv`).
   - Pastikan variabel `FTP_HOST`, `FTP_USER`, `FTP_PASSWORD`, dan `FTP_REMOTE_DIR` sudah dikonfigurasi di file `.env`. Jika belum lengkap, tanyakan kepada pengguna sebelum melanjutkan.
3. **Eksekusi Upload ke DomaiNesia:**
   - Jalankan perintah `node deploy-domainesia.mjs` di terminal.
   - Pantau log terminal hingga muncul pesan sukses. Jika terjadi *timeout* atau error koneksi FTPS, laporkan penyebabnya secara ringkas.

## 3. Aturan Keamanan (Strict Rules)

- **DILARANG** mencetak, menampilkan, atau membacakan isi file `.env` (terutama `FTP_PASSWORD`) ke output terminal maupun kolom chat.
- **DILARANG** mengunggah folder `node_modules`, `.git`, atau file `.env` lokal ke server DomaiNesia.
- Pastikan `.env` dan `deploy-domainesia.mjs` (jika berisi konfigurasi khusus) tercatat di dalam `.gitignore`.
- Untuk aplikasi Laravel + React, **JANGAN** menimpa file `.env` maupun folder `storage/` yang sudah ada di server production DomaiNesia.

---

## 4. Template Skrip `deploy-domainesia.mjs`

*(Gunakan template ini jika file belum tersedia di project)*

```javascript
import * as ftp from "basic-ftp";
import dotenv from "dotenv";
dotenv.config();

async function deploy() {
    const client = new ftp.Client();
    client.ftp.verbose = false; // Set true jika butuh debug detail

    const requiredEnv = ["FTP_HOST", "FTP_USER", "FTP_PASSWORD", "FTP_REMOTE_DIR"];
    for (const key of requiredEnv) {
        if (!process.env[key]) {
            console.error(`Error: Variabel ${key} belum diatur di file .env`);
            process.exit(1);
        }
    }

    try {
        console.log("Menghubungkan ke server FTPS DomaiNesia...");
        await client.access({
            host: process.env.FTP_HOST,
            user: process.env.FTP_USER,
            password: process.env.FTP_PASSWORD,
            secure: true,
            secureOptions: { rejectUnauthorized: false }
        });

        console.log(`Mengunggah hasil build ke ${process.env.FTP_REMOTE_DIR}...`);
        // Sesuaikan "./dist" dengan folder output build (misal "./public/build" untuk Laravel Vite)
        await client.uploadFromDir("./dist", process.env.FTP_REMOTE_DIR);

        console.log("✅ Deploy ke DomaiNesia berhasil!");
    } catch (err) {
        console.error("❌ Gagal melakukan deploy:", err.message);
        process.exit(1);
    } finally {
        client.close();
    }
}

deploy();
```
