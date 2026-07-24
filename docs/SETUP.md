# SETUP — Panduan Instalasi & Environment
## SIMPEG (Sistem Informasi Manajemen Kepegawaian)

Status: Draft rancangan awal
Terakhir diperbarui: 2026-07-23

> Karena SIMPEG dijual sebagai instalasi terpisah per klien (lihat DESIGN.md §1), dokumen ini dipakai berulang setiap kali onboarding perusahaan pelanggan baru.

---

## 1. Prasyarat

| Kebutuhan | Versi Minimum | Catatan |
|---|---|---|
| PHP | 8.3+ | Ekstensi: `pdo_mysql`, `curl`, `openssl`, `mbstring`, `fileinfo`, `gd` (untuk resize foto selfie) |
| Composer | 2.x | |
| Node.js | 20+ | Untuk build asset Vite/Vue |
| npm | bawaan Node | |
| MySQL | 8.x | Lihat §3 untuk konfigurasi wajib |
| Web server | Apache/Nginx dengan HTTPS di produksi | Wajib HTTPS untuk Geolocation API & kamera browser (lihat DESIGN.md §5.2) |

---

## 2. Instalasi Awal Project

```bash
# 1. Buat project Laravel 13 dengan starter kit Inertia + Vue
composer global require laravel/installer
laravel new nama-project --vue --database=mysql --pest --npm --no-boost --force -n

# 2. Masuk ke folder project
cd nama-project

# 3. Salin & sesuaikan environment
cp .env.example .env
php artisan key:generate
```

Sesuaikan `.env`:
```env
APP_NAME="SIMPEG - [Nama Perusahaan Klien]"
APP_URL=https://domain-klien.example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=simpeg_nama_klien
DB_USERNAME=root
DB_PASSWORD=
```

> **Kredensial Google OAuth & WhatsApp Business API TIDAK diisi di `.env`.** Sejak FR-11.15 (SRS), keduanya dikonfigurasi lewat halaman **Pengaturan > Integrasi** oleh Super Admin setelah instalasi berjalan, tersimpan terenkripsi di database (DESIGN.md §1 & §11.5) — supaya bisa diganti tanpa akses server. Langkah pengisiannya ada di checklist onboarding §4 poin 4.

```bash
# 4. Buat database
mysql -u root -e "CREATE DATABASE simpeg_nama_klien CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 5. Migrasi & seed data awal (role, permission, jenis cuti default, dst.)
php artisan migrate --seed

# 6. Build asset frontend
npm install
npm run build     # produksi
# atau
npm run dev        # development, hot-reload
```

---

## 3. Catatan Khusus Environment WAMP/Windows

Dua masalah berikut ditemukan saat setup pertama di lingkungan WAMP dan **wajib diperiksa** di tiap instalasi baru berbasis WAMP:

### 3.1 PHP curl tidak bisa verifikasi SSL
**Gejala:** `composer create-project` atau `laravel new` gagal mengunduh starter kit dengan error `SSL certificate problem: unable to get local issuer certificate`.

**Perbaikan** — unduh CA bundle resmi dan arahkan `php.ini`:
```ini
[curl]
curl.cainfo = "C:\wamp64\bin\php\phpX.X.X\cacert.pem"

[openssl]
openssl.cafile = "C:\wamp64\bin\php\phpX.X.X\cacert.pem"
```
Unduh `cacert.pem` dari `https://curl.se/ca/cacert.pem` ke folder PHP tersebut, lalu restart Apache/PHP.

### 3.2 Default storage engine MySQL = MyISAM
**Gejala:** migrasi gagal dengan error `Specified key was too long; max key length is 1000 bytes` saat membuat index unique (mis. `users_email_unique`).

**Penyebab:** WAMP secara default mengatur `default_storage_engine=MYISAM` di `my.ini`, padahal Laravel membutuhkan **InnoDB** (mendukung foreign key, transaksi, dan panjang index yang memadai untuk `utf8mb4`).

**Perbaikan** — pada `my.ini` MySQL:
```ini
default_storage_engine=InnoDB
```
Restart service MySQL setelah perubahan. **Perlu dikoordinasikan dulu** bila server tersebut juga melayani aplikasi lain, karena ini pengaturan level-server, bukan level-database.

---

## 4. Checklist Onboarding Klien Baru

Gunakan urutan ini setiap kali instalasi baru dibuat untuk perusahaan pelanggan:

1. [ ] Provisioning server/hosting + domain dengan HTTPS aktif (wajib, lihat §1)
2. [ ] Install project mengikuti §2, dengan nama database unik per klien
3. [ ] Login sebagai Super Admin awal (dibuat lewat seeder), lalu ke modul **Pengaturan**:
   - [ ] Profil perusahaan (nama, logo, alamat)
   - [ ] Jenis & aturan tunjangan (POLICIES.md §5)
   - [ ] Jadwal kerja/shift
   - [ ] Jenis cuti & kuota (POLICIES.md §3)
   - [ ] Metode absensi & titik lokasi kerja (`work_locations`) + radius (POLICIES.md §2)
   - [ ] Grade/skema gaji (`salary_grades`)
   - [ ] Jumlah tingkatan approval izin/lembur/payroll
4. [ ] Login sebagai Super Admin → **Pengaturan > Integrasi** → isi Client ID/Secret Google OAuth bila klien ingin fitur login Google (tersimpan terenkripsi di database, bukan di `.env`, lihat catatan §2)
5. [ ] Isi teks kebijakan privasi (persetujuan data pribadi, lihat POLICIES.md §9) sesuai kebutuhan klien
6. [ ] Import data pegawai awal via fitur import Excel/CSV (unit kerja dulu, lalu pegawai) — periksa laporan error sebelum finalisasi
7. [ ] Kirim email undangan set password ke seluruh pegawai
8. [ ] Uji coba alur absen GPS+selfie dari perangkat mobile nyata (memastikan HTTPS & izin browser berfungsi)
9. [ ] Uji satu siklus penuh: absen → izin → approval → rekap tunjangan → payroll draft → payroll final → slip gaji
10. [ ] Jalankan `php artisan test` — pastikan lulus, khususnya test kalkulasi Payroll (DESIGN.md §10)
11. [ ] Aktifkan backup otomatis (lihat §6 di bawah) & catat versi rilis yang terpasang (lihat DESIGN.md §7)
12. [ ] Serah terima ke HRD klien + training singkat

---

## 5. Menjalankan untuk Development Lokal

```bash
php artisan serve        # backend, http://localhost:8000
npm run dev               # frontend dev server dengan hot-reload
php artisan queue:listen  # proses job (notifikasi, resize foto, generate PDF)
```

Untuk mengetes alur GPS+selfie di development tanpa HTTPS, gunakan `localhost` (browser modern memperlakukan `localhost` sebagai secure context) — jangan gunakan IP LAN (`192.168.x.x`) karena Geolocation/kamera API akan diblokir browser tanpa HTTPS.

---

## 6. Backup Otomatis (Praktis)

Contoh backup harian sederhana via cron/Task Scheduler — sesuaikan lokasi tujuan backup (idealnya offsite/object storage, bukan disk yang sama dengan aplikasi):

```bash
# Backup database (jalankan harian)
mysqldump -u root simpeg_nama_klien | gzip > backup/db-$(date +%Y%m%d).sql.gz

# Backup storage foto selfie & dokumen (jalankan mingguan, volume lebih besar)
tar -czf backup/storage-$(date +%Y%m%d).tar.gz storage/app/public
```

Retensi minimal 30 hari untuk backup database. Detail kebijakan & jadwal uji restore: lihat DESIGN.md §8.

## 7. Update/Patch Instalasi yang Sudah Berjalan

```bash
git fetch --tags
git checkout v1.3.0          # pindah ke versi rilis yang dituju
composer install --no-dev
composer audit                # cek kerentanan dependency PHP (DESIGN.md §11.7)
npm audit                      # cek kerentanan dependency JS
php artisan test              # pastikan seluruh test suite lulus sebelum lanjut (lihat DESIGN.md §10)
npm install && npm run build
php artisan migrate           # jalankan saat maintenance window, bukan jam sibuk absen
php artisan config:cache
php artisan route:cache
```

Selalu backup database (§6) sebelum menjalankan migrasi versi baru. Uji migrasi di staging dulu bila melibatkan perubahan skema `payroll_items` atau `attendances`. Strategi versioning lengkap: lihat DESIGN.md §7; strategi testing: lihat DESIGN.md §10.

---

## 8. Checklist Keamanan Produksi

Wajib diperiksa **sebelum** instalasi klien baru go-live (lihat juga POLICIES.md §13 & DESIGN.md §11):

1. [ ] `APP_DEBUG=false` di `.env` produksi — pesan error rinci tidak boleh terlihat pengguna akhir
2. [ ] HTTPS aktif & valid (bukan self-signed) di domain produksi
3. [ ] File `.env` tidak dapat diakses lewat URL publik (uji langsung: `https://domain/.env` harus 404/403)
4. [ ] Header keamanan (CSP, X-Frame-Options, HSTS) aktif — cek lewat `curl -I https://domain`
5. [ ] Folder `storage/` tidak dapat mengeksekusi PHP (uji: unggah file `.php` lewat fitur upload, akses langsung URL-nya harus gagal dieksekusi)
6. [ ] `composer audit` & `npm audit` bersih dari kerentanan tingkat tinggi/kritis
7. [ ] Backup otomatis (§6) aktif dan **terenkripsi**
8. [ ] Kredensial integrasi (Google OAuth, WhatsApp) diisi lewat UI Super Admin, bukan ditinggal di `.env` contoh
9. [ ] 2FA aktif untuk akun Super Admin & HRD sebelum akun-akun lain dibuat

## 9. Referensi Terkait
- Kebutuhan fungsional lengkap: `SRS.md`
- Arsitektur, skema database, dan struktur folder frontend: `DESIGN.md`
- Nilai default kebijakan yang perlu disesuaikan tiap klien: `POLICIES.md`
- Spesifikasi server produksi & pipeline CI/CD: `INFRASTRUCTURE.md`
