# LMS Diofanu

Learning Management System (LMS) ringan dan modern terinspirasi Moodle, dibangun dengan **Laravel 12**, **Blade**, **Tailwind CSS**, **Vite**, dan **MySQL**. Seluruh antarmuka berbahasa Indonesia, zona waktu **Asia/Jakarta**, dengan peran **Admin**, **Guru**, dan **Murid**.

> Proyek ini berjalan penuh (bukan mockup): autentikasi, RBAC, kelas, materi, tugas, kuis, nilai, pengumuman, diskusi, kalender, notifikasi, panel admin, dan sistem percakapan/pesan dengan otorisasi di setiap aksi.

---

## Daftar Isi

1. [Fitur](#1-fitur)
2. [Persyaratan Sistem](#2-persyaratan-sistem)
3. [Instalasi Lokal](#3-instalasi-lokal)
4. [Konfigurasi `.env`](#4-konfigurasi-env)
5. [Akun Demo](#5-akun-demo)
6. [Rumus Nilai Akhir](#6-rumus-nilai-akhir)
7. [Sistem Pesan (Polling, Tanpa WebSocket)](#7-sistem-pesan-polling-tanpa-websocket)
8. [Antrean (Queue) dan Scheduler](#8-antrean-queue-dan-scheduler)
9. [Struktur Proyek](#9-struktur-proyek)
10. [Keamanan](#10-keamanan)
11. [Pengujian](#11-pengujian)
12. [Deployment Produksi](#12-deployment-produksi)
13. [Panduan cPanel / Shared Hosting](#13-panduan-cpanel--shared-hosting)
14. [Checklist Produksi](#14-checklist-produksi)
15. [Pemecahan Masalah](#15-pemecahan-masalah)

---

## 1. Fitur

**Umum**

- Autentikasi Breeze (login, registrasi, lupa password, reset password) berbahasa Indonesia.
- RBAC berbasis kolom `users.role` (`admin`, `teacher`, `student`) + middleware `role`/`active` + **policy** (dicek di backend, bukan sekadar menyembunyikan tombol).
- Dasbor berbeda untuk Admin / Guru / Murid.
- Pencarian global (`/cari`), notifikasi, kalender akademik, halaman error ramah (403/404/500).

**Kelas & Konten**

- Kelas: buat/ubah/arsip/hapus, kode kelas & tautan undangan, gabung/keluar, tanpa duplikasi keanggotaan.
- Materi: topik, jadwal terbit, lampiran privat, penandaan selesai (completion).
- Tugas: tenggat, bobot, rubrik, penanganan terlambat, penilaian + umpan balik + revisi.
- Kuis: pilihan ganda & jawaban singkat, batas waktu, batas percobaan, penilaian otomatis PG, kunci jawaban disembunyikan dari murid, penilaian manual jawaban singkat.

**Nilai & Laporan**

- Buku nilai per kelas, nilai akhir berbobot (lihat [§6](#6-rumus-nilai-akhir)), ekspor **CSV**.
- Murid hanya melihat nilai miliknya sendiri.

**Komunikasi**

- Pengumuman, diskusi (thread + balasan, sematkan/kunci), kalender, notifikasi.
- Percakapan: DM murid–murid (dikontrol pengaturan sekolah + satu kelas yang sama), guru–murid, dan grup kelas.
- Daftar percakapan dengan avatar, pratinjau, waktu, lencana belum dibaca, dan pencarian.
- Riwayat chat dengan pemisah tanggal, status terkirim/dibaca, balas (reply), emoji, lampiran (validasi tipe + ukuran), paginasi.
- Indikator “sedang mengetik” hanya ditampilkan bila data polling benar-benar tersedia (lihat [§7](#7-sistem-pesan-polling-tanpa-websocket)).
- Blokir & laporan pesan (ditinjau admin).

**Panel Admin**

- Pengguna, mata pelajaran, tahun ajaran, pengaturan sekolah, laporan, laporan pesan, log aktivitas (audit log).
- Admin **tidak** bisa mengubah password pengguna secara langsung (hanya mengirim tautan reset).

---

## 2. Persyaratan Sistem

| Komponen | Versi |
| --- | --- |
| PHP | **8.4+** (ekstensi: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd` opsional) |
| Composer | 2.x |
| MySQL | **8.0+** (bukan MariaDB) |
| Node.js / npm | 18+ (dikembangkan dengan Node 26 & Vite 6) |
| Web server | Apache / Nginx / `php artisan serve` (lokal) |

---

## 3. Instalasi Lokal

```bash
# 1. Salin konfigurasi environment
cp .env.example .env          # Windows: copy .env.example .env

# 2. Dependency PHP
composer install

# 3. Dependency JS & aset produksi
npm install
npm run build                 # menghasilkan public/build/*

# 4. Kunci aplikasi
php artisan key:generate

# 5. Buat database MySQL (contoh)
mysql -uroot -p -e "CREATE DATABASE lms_diofanu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 6. Sesuaikan DB di .env (lihat §4), lalu migrasi + data demo
php artisan migrate --seed

# 7. Link storage publik (dipakai avatar)
php artisan storage:link

# 8. Jalankan server
php artisan serve             # http://127.0.0.1:8000
```

Server pengembangan alternatif (tanpa artisan serve):

```bash
php -S 127.0.0.1:8010 -t public
```

### Cara A — Laravel Herd (disarankan, sudah dipakai proyek ini)

Herd menyediakan PHP 8.4, nginx, dan DNS `*.test` sehingga Anda tidak perlu `artisan serve`.

```powershell
# 1. (Pertama kali) daftarkan folder proyek ke Herd — jalankan dari folder induk
herd park "C:\Users\Admin\Documents\Project"
#    -> situs: http://lms-diofanu.test   (nama = nama folder)

# 2. Pastikan versi PHP & ekstensi
herd php -v          # PHP 8.4.x
herd php -m          # pastikan ada pdo_mysql

# 3. Jalankan perintah Laravel memakai PHP milik Herd
herd php artisan migrate --seed
herd php artisan storage:link

# 4. Buka aplikasi
herd open            # atau buka http://lms-diofanu.test
```

Konfigurasi yang menyesuaikan Herd:

| Kunci | Nilai |
| --- | --- |
| `APP_URL` | `http://lms-diofanu.test` |
| `DB_HOST` / `DB_PORT` | `127.0.0.1` / `3306` (MySQL Anda sendiri, atau MySQL bawaan Herd) |

Perintah bermanfaat Herd:

| Perintah | Fungsi |
| --- | --- |
| `herd sites` / `herd links` | Daftar situs terdaftar |
| `herd restart` | Restart layanan (nginx + DNS) bila domain `.test` belum terbuka |
| `herd isolate 8.4` | Kunci versi PHP untuk folder ini |
| `herd secure lms-diofanu` | Pasang sertifikat HTTPS tepercaya untuk `lms-diofanu.test` |
| `herd log` | Lihat log server |

> Bila `lms-diofanu.test` tidak bisa dibuka (“could not be resolved”), jalankan `herd restart` — Herd akan menulis entri domain ke file `hosts`.

Selalu jalankan `npm run build` setiap mengubah file di `resources/views`, `resources/css`, atau `resources/js` (Tailwind memindai view saat build).
> Bila tampilan terlihat basi setelah perubahan template, bersihkan cache view: `php artisan view:clear`.

---

## 4. Konfigurasi `.env`

Variabel yang paling sering diubah:

| Kunci | Nilai contoh | Keterangan |
| --- | --- | --- |
| `APP_NAME` | `LMS Diofanu` | Nama aplikasi |
| `APP_URL` | `http://lms-diofanu.test` (lokal Herd) / `https://lms.sekolah.sch.id` | **Wajib** disesuaikan dengan domain produksi |
| `APP_DEBUG` | `false` | `true` hanya di lokal |
| `APP_ENV` | `production` | Saat deploy |
| `APP_TIMEZONE` | `Asia/Jakarta` | Sudah bawaan |
| `DB_CONNECTION` | `mysql` | |
| `DB_HOST` / `DB_PORT` | `127.0.0.1` / `3306` | |
| `DB_DATABASE` | `lms_diofanu` | |
| `DB_USERNAME` / `DB_PASSWORD` | `root` / `***` | |
| `SESSION_DRIVER` | `database` | Tabel `sessions` dibuat migrasi |
| `CACHE_STORE` | `database` | |
| `QUEUE_CONNECTION` | `database` | Tidak wajib dijalankan (lihat §8) |
| `MAIL_MAILER` | `log` (lokal) / `smtp` (produksi) | Untuk tautan reset password |
| `FILESYSTEM_DISK` | `local` | Lampiran disimpan **privat** di `storage/app/private` dan disajikan lewat controller |

Setelah mengubah `.env`:

```bash
php artisan config:clear     # atau config:cache pada produksi
php artisan cache:clear
```

---

## 5. Akun Demo

Dihasilkan oleh `database/seeders/DemoSeeder.php`. **Semua password: `password`** (fiktif, hanya untuk demo).

| Email | Peran | Nama |
| --- | --- | --- |
| `admin@lms.test` | Admin | Budi Santoso |
| `guru@lms.test` | Guru | Siti Aminah |
| `guru2@lms.test` | Guru | Andi Wijaya |
| `murid1@lms.test` | Murid | Ahmad Fauzi |
| `murid2@lms.test` | Murid | Dewi Lestari |
| `murid3@lms.test` | Murid | Rizky Pratama |
| `murid4@lms.test` | Murid | Nabila Zahra |

Data demo memuat 2 kelas, anggota kelas, materi, tugas, kuis + percobaan, nilai, pengumuman, diskusi, agenda kalender, notifikasi, dan beberapa percakapan contoh.

Reset total data demo:

```bash
php artisan migrate:fresh --seed
```

---

## 6. Rumus Nilai Akhir

Dihitung oleh `app/Services/GradeCalculator.php`; bobot disimpan di tabel `grade_weights` dan bisa diubah lewat `POST /kelas/{kelas}/nilai/bobot` (form di halaman buku nilai):

```
Nilai Akhir = (Σ Wajib_nilai × bobot_kategori) / (Σ bobot_kategori_terpakai) × 100
```

- Bobot disimpan di tabel `grade_weights`. Bawaan: **Tugas = 60**, **Kuis = 40** (ubah lewat form “Bobot Nilai” di halaman buku nilai kelas).
- Kategori **kosong** (belum ada komponen bernilai) **tidak diikutkan**, sehingga bobot tersisa dinormalisasi ulang.
- Jika belum ada komponen bernilai, nilai akhir = `null` (ditampilkan “Belum ada nilai”).
- Contoh: hanya Tugas yang sudah dinilai → nilai akhir = rata-rata tugas (bobot 60 disalin ulang ke 100).

Ekspor CSV buku nilai: menu **Nilai → Ekspor CSV** (`/kelas/{id}/nilai/ekspor`).

---

## 7. Sistem Pesan (Polling, Tanpa WebSocket)

Aplikasi ini **tidak** memakai Laravel Reverb/WebSocket, sehingga **tidak ada klaim “real-time” yang berlebihan**:

| Endpoint | Fungsi |
| --- | --- |
| `GET /percakapan/{id}/pantau?after={id}` | Mengambil pesan baru sejak `after`, plus `unread_total` dan `server_time` |
| `GET /percakapan/{id}/pesan` | Riwayat (paginasi) |
| `POST /percakapan/{id}/kirim` | Mengirim pesan/emoji/lampiran |
| `POST /percakapan/{id}/baca` | Menandai sudah dibaca |

- Halaman percakapan melakukan **polling berkala** ke endpoint tersebut. Tidak butuh proses tambahan di server.
- Indikator “sedang mengetik” hanya dikirim/ditampilkan apabila data polling benar-benar tersedia; bila tidak, label tersebut tidak muncul (tidak dipalsukan).
- Format respons JSON dibuat kompatibel agar kelak bisa ditambahkan Reverb/Broadcasting tanpa mengubah kontrak frontend.
- Otorisasi: **hanya peserta aktif** percakapan yang boleh membaca/mengirim (admin pun tidak bisa membuka percakapan orang lain), blokir berlaku dua arah, DM murid dibatasi pengaturan `allow_student_private_message` dan harus satu kelas.

---

## 8. Antrean (Queue) dan Scheduler

- Kode aplikasi saat ini **tidak memakai job antrean** (tidak ada kelas `ShouldQueue`), jadi **worker queue tidak wajib** untuk fitur inti.
- Jika kelak Anda menambahkan job/mail antrean, jalankan di produksi:

  ```bash
  php artisan queue:work --sleep=3 --tries=3 --max-time=3600
  ```

  (Gunakan Supervisor/systemd di Linux, atau Task Scheduler di Windows.)

- **Scheduler** (`php artisan schedule:run` tiap menit via cron) juga **tidak wajib** saat ini — `routes/console.php` hanya berisi perintah bawaan. Tetap disarankan menyiapkan cron agar siap untuk tugas terjadwal berikutnya:

  ```cron
  * * * * * cd /path/to/lms && php artisan schedule:run >> /dev/null 2>&1
  ```

- Mail reset password memakai `MAIL_MAILER=log` secara bawaan (log di `storage/logs/laravel.log`). Untuk produksi isi `MAIL_MAILER=smtp` + kredensial SMTP sekolah.

---

## 9. Struktur Proyek

```
app/
  Http/Controllers/     → controller kecil, satu tanggung jawab
  Http/Requests/        → validasi Form Request
  Models/               → 26 model Eloquent (soft delete, relasi, index)
  Policies/             → 13 policy (otorisasi per aksi)
  Services/             → GradeCalculator, QuizGrader, AttachmentService,
                          ConversationService, AuditLogger, dll.
  Support/              → Role enum, helper
database/migrations/    → 10 migrasi (FK, index, unique, timestamps, soft delete)
database/seeders/       → DatabaseSeeder + DemoSeeder
resources/views/        → Blade + komponen (x-card, x-modal, x-table, x-badge, …)
resources/views/components/layouts/ → layout app & guest
routes/web.php          → seluruh route bernama (named routes)
tests/Feature/          → pengujian autentikasi, RBAC, keanggotaan, materi,
                          pengumpulan, privasi nilai, messaging, kuis
```

Pendekatan: MVC + service layer, route bernama, komponen Blade dapat digunakan ulang, factory untuk data uji, eager loading untuk menghindari N+1.

---

## 10. Keamanan

- CSRF token pada seluruh form (`@csrf`), output di-escape (`{{ }}`).
- Validasi di backend lewat Form Request (termasuk validasi berkas: tipe, ekstensi, ukuran maksimal 10 MB).
- Penyimpanan lampiran **privat** dengan **nama acak** pada disk `local`; unduhan wajib login + pengecekan otorisasi (anti-IDOR) di `AttachmentController`.
- Password di-hash (bcrypt), rate limiting pada login & endpoint sensitif.
- Policy/gate di setiap rute penting; murid tidak dapat melihat/mengubah nilai orang lain.
- `APP_DEBUG=false` di produksi agar stack trace, `.env`, dan API key tidak bocor.
- Kunci jawaban kuis tidak pernah dikirim ke klien sebelum diizinkan guru.

---

## 11. Pengujian

```bash
php artisan test
```

Status terakhir: **98 tests, 230 assertions — lulus semua (SQLite in-memory via `phpunit.xml`)**.

Cakupan: autentikasi, akses berbasis peran, keanggotaan kelas, akses materi, pengumpulan tugas, privasi nilai, percakapan/pesan (privasi & otorisasi), upaya kuis (penilaian otomatis, kunci jawaban tersembunyi), profil.

---

## 12. Deployment Produksi

```bash
# 1. Salin proyek ke server, lalu
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 2. Environment
cp .env.example .env
php artisan key:generate
#    -> edit APP_ENV=production, APP_DEBUG=false, APP_URL, DB, MAIL

# 3. Database
php artisan migrate --seed        # atau migrate saja bila tanpa data demo

# 4. Storage & cache
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan cache:clear

# 5. Permission (Linux)
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

**Document root** harus menunjuk ke folder `public/` (bukan root proyek).

**HTTPS**: pasang sertifikat (Let's Encrypt/ISP), paksa redirect HTTP → HTTPS, dan set `APP_URL=https://...`.

**Cron (Linux)** — minimal scheduler, opsional queue:

```cron
* * * * * cd /path/to/lms && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /path/to/lms && php artisan queue:work --stop-when-empty >> /dev/null 2>&1
```

Jangan asumsikan cron/queue/WebSocket aktif — semua fitur inti (termasuk pesan) tetap berfungsi tanpa keduanya.

---

## 13. Panduan cPanel / Shared Hosting

1. **Build aset di lokal** (`npm run build`) lalu unggah seluruh proyek, termasuk folder `public/build`.
2. Upload ke `public_html` (atau subfolder), lalu:
   - Pindahkan isi folder `public/` ke document root (`public_html`).
   - Pindahkan folder aplikasi lain (`app`, `bootstrap`, `config`, `database`, `resources`, `routes`, `storage`, `vendor`, `.env`, dst.) **di luar** document root (mis. `~/lms-app`).
   - Edit `public_html/index.php`:

     ```php
     require __DIR__.'/../lms-app/vendor/autoload.php';
     $app = require_once __DIR__.'/../lms-app/bootstrap/app.php';
     ```
3. Buat database MySQL di cPanel (MySQL Databases → User Privileges: ALL).
4. Sesuaikan `.env` (DB, `APP_URL` domain Anda) lalu jalankan lewat Terminal cPanel:

   ```bash
   php artisan migrate --seed
   php artisan storage:link
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```

5. Wajib HTTPS (AutoSSL/Let's Encrypt).
6. `.htaccess` bawaan Laravel ada di `public/.htaccess`; aktifkan mod_rewrite bila diperlukan.
7. Pesan tetap berjalan dengan **polling** (tanpa WebSocket/Reverb), jadi tidak perlu konfigurasi tambahan di shared hosting.

---

## 14. Checklist Produksi

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://domain-anda`
- [ ] Password database kuat, user DB berhak **hanya** pada DB aplikasi
- [ ] `php artisan migrate` sukses; data demo dihapus bila tidak diperlukan (`users` demo, dsb.)
- [ ] `storage:link` dijalankan; `storage/` & `bootstrap/cache/` writable oleh web server
- [ ] `config:cache`, `route:cache`, `view:cache` dijalankan
- [ ] Aset ter-build (`public/build` ada) dan versi hashed sesuai
- [ ] HTTPS aktif + redirect HTTP → HTTPS
- [ ] SMTP dikonfigurasi & uji kirim email reset password
- [ ] Cron scheduler (dan queue bila dipakai) aktif
- [ ] Backup rutin database + `storage/app`
- [ ] Log dipantau: `storage/logs/laravel.log` (set `LOG_LEVEL=warning` di produksi)
- [ ] Uji ulang login 3 peran, unggah lampiran, kirim pesan, dan ekspor CSV

---

## 15. Pemecahan Masalah

| Gejala | Solusi |
| --- | --- |
| Halaman kosong/500 | `php artisan config:clear && php artisan view:clear`; cek `storage/logs/laravel.log` |
| `No application encryption key` | `php artisan key:generate` |
| Gagal koneksi DB | Cek `DB_*` di `.env`, pastikan MySQL (bukan MariaDB) berjalan |
| Tampilan tidak berubah | `npm run build` lalu `php artisan view:clear` |
| 419 Page Expired | Session kedaluwarsa — login ulang; bila tabel `sessions` kosong/rusak jalankan `php artisan migrate` dan `php artisan cache:clear` |
| Lampiran 404 | Jalankan `php artisan storage:link`; cek file ada di `storage/app/private/...` |
| Email reset tidak terkirim | Bawaan `MAIL_MAILER=log` — lihat `storage/logs/laravel.log`; di produksi isi SMTP |
| Codec/mojibake teks | Pastikan koneksi DB `utf8mb4` (bawaan) |

---

## Lisensi

Open-source (MIT) untuk kerangka Laravel. Konten demo bersifat fiktif.
