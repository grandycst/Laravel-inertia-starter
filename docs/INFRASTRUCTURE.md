# INFRASTRUCTURE — Hosting, Server Requirement & CI/CD
## SIMPEG (Sistem Informasi Manajemen Kepegawaian)

Status: Draft rancangan awal
Terakhir diperbarui: 2026-07-23

> Karena SIMPEG dijual sebagai instalasi terpisah per klien (lihat DESIGN.md §1), dokumen ini dipakai dua arah: (1) sebagai spesifikasi server yang direkomendasikan ke tiap klien saat onboarding, dan (2) sebagai desain pipeline CI/CD untuk pengembangan kode inti — yang sifatnya **beda dari SaaS biasa**, karena tidak ada satu "production" tunggal yang di-deploy otomatis.

---

## 1. Spesifikasi Server per Skala Klien

| Skala | Pegawai | CPU | RAM | Storage aplikasi | Catatan |
|---|---|---|---|---|---|
| Kecil | <100 | 2 vCPU | 2 GB | 20 GB SSD | VPS kecil/shared hosting berbasis KVM cukup |
| Menengah *(target utama)* | 100–500 | 2–4 vCPU | 4 GB | 40 GB SSD | Sesuai batas skala yang dirancang di SRS §4 (Kebutuhan Non-Fungsional) |
| Besar | >500 | 4+ vCPU | 8 GB+ | 80 GB+ SSD | Pertimbangkan pisah server database dari server aplikasi |

Storage foto selfie & dokumen bertumbuh seiring waktu (lihat kebijakan retensi POLICIES.md §2) — pantau kapasitas secara berkala, bukan asumsi sekali alokasi di awal cukup selamanya.

---

## 2. Kebutuhan Software Server (Produksi — Linux, beda dari WAMP lokal)

| Komponen | Versi/Pilihan | Catatan |
|---|---|---|
| OS | Ubuntu 22.04/24.04 LTS | WAMP (SETUP.md) hanya untuk development lokal Windows, bukan produksi |
| Web server | Nginx (direkomendasikan) atau Apache | Konfigurasi harus menonaktifkan eksekusi PHP di folder `storage/` (DESIGN.md §11.2) |
| PHP | 8.3+ | Ekstensi sama seperti SETUP.md §1 |
| MySQL | 8.x, `default_storage_engine=InnoDB` | **Wajib dicek** — masalah yang sama seperti di WAMP (SETUP.md §3.2) bisa terjadi juga di server Linux bila provider hosting mengubah default |
| Node.js | 20+ | Hanya dibutuhkan saat **build** — tidak wajib terpasang permanen di server produksi bila build dilakukan di CI/lokal lalu hasil build (`public/build`) diunggah bersama kode |
| Supervisor | Terbaru | Menjaga `php artisan queue:work` tetap berjalan & otomatis restart bila crash |
| Cron | bawaan OS | Menjalankan Laravel Scheduler (§4) |

---

## 3. HTTPS/SSL

- **Let's Encrypt** (gratis, via Certbot, auto-renew) sebagai default — penting karena target klien kecil-menengah, jangan bebani mereka dengan biaya sertifikat SSL berbayar.
- HTTPS **wajib** aktif sebelum go-live (lihat SETUP.md §8 Checklist Keamanan Produksi #2) — tanpa ini, Geolocation API & akses kamera browser (fitur inti Absensi, DESIGN.md §5.2) tidak akan berfungsi sama sekali.

---

## 4. Queue Worker & Scheduler

Banyak fitur yang sudah dirancang bergantung pada proses latar belakang:

```bash
# Supervisor config (contoh) — /etc/supervisor/conf.d/simpeg-worker.conf
[program:simpeg-worker]
command=php /var/www/simpeg/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=2
```

```cron
# Crontab — jalankan scheduler tiap menit
* * * * * cd /var/www/simpeg && php artisan schedule:run >> /dev/null 2>&1
```

Job yang bergantung pada scheduler ini (referensi silang ke dokumen lain, supaya jelas kenapa cron **wajib** ada, bukan opsional):
- Tutup tahun cuti / hangus-carry over (DESIGN.md §4 poin 5a)
- Reminder dokumen kedaluwarsa & notifikasi ulang tahun/hari jadi kerja (DESIGN.md §4 poin 5)
- Rekap kehadiran bulanan untuk Tunjangan Kinerja (DESIGN.md §4 poin 2)
- Reminder masa probation berakhir (SRS FR-2.13)
- Backup otomatis (SETUP.md §6)

---

## 5. Strategi Storage

- Default: disk lokal server (`storage/app/private`, lihat DESIGN.md §11.2) — cukup untuk klien kecil-menengah.
- Untuk klien besar atau yang butuh redundansi lebih baik: object storage S3-compatible (mis. MinIO self-hosted atau layanan cloud) sebagai upgrade path — Laravel Filesystem sudah mendukung ini tanpa perlu ubah kode aplikasi, cukup ganti konfigurasi disk.

---

## 6. CI/CD — Beda dari SaaS Biasa

Karena model bisnis SIMPEG adalah **instalasi terpisah per klien** (DESIGN.md §1), CI/CD di sini **tidak auto-deploy ke satu production**. Yang benar:

- **CI (Continuous Integration)** — otomatis, jalan tiap push/PR ke branch utama. Tujuannya memastikan kode inti selalu sehat sebelum ditarik ke instalasi klien manapun.
- **CD di sini berarti Continuous *Delivery*, bukan *Deployment*** — hasil CI yang lulus menghasilkan rilis bertag (mis. `v1.3.0`) yang **siap ditarik manual** oleh tiap instalasi klien sesuai jadwal maintenance window masing-masing (SETUP.md §7) — bukan otomatis mendorong perubahan ke server klien tanpa sepengetahuan mereka.

### 6.1 Tahapan CI (tiap push/PR)

1. Install dependencies (`composer install`, `npm install`)
2. Analisis statis: `larastan`
3. Cek gaya kode: `laravel pint --test`
4. Audit kerentanan dependency: `composer audit`, `npm audit` (DESIGN.md §11.7)
5. Jalankan test suite: `php artisan test` (Pest, DESIGN.md §10) — termasuk test wajib untuk Payroll, Absensi, alur approval
6. Build asset frontend: `npm run build` — pastikan build tidak gagal

Jika salah satu tahap gagal, PR tidak boleh digabung ke branch utama.

### 6.2 Contoh Workflow (GitHub Actions)

```yaml
# .github/workflows/ci.yml
name: CI
on:
  push:
    branches: [main]
  pull_request:

jobs:
  test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: simpeg_testing
        ports: ["3306:3306"]
        options: >-
          --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: "8.3"
      - run: composer install --no-interaction --prefer-dist
      - run: composer audit
      - run: vendor/bin/pint --test
      - run: vendor/bin/phpstan analyse
      - uses: actions/setup-node@v4
        with:
          node-version: "20"
      - run: npm ci
      - run: npm audit --audit-level=high
      - run: npm run build
      - run: php artisan test --env=testing
```

### 6.3 Rilis (setelah CI lulus di branch utama)

1. Tag versi baru: `git tag v1.3.0 && git push --tags`
2. CI otomatis membuat draft release notes dari commit messages sejak tag sebelumnya
3. Rilis ini **tidak otomatis terpasang ke instalasi klien mana pun** — tiap klien di-update manual mengikuti SETUP.md §7, saat maintenance window yang dikoordinasikan dengan HRD klien tersebut

### 6.4 Environment

| Environment | Fungsi | Auto-deploy dari CI? |
|---|---|---|
| Local | Development di WAMP (SETUP.md) | Tidak berlaku |
| Staging | Server internal (bukan milik klien manapun), untuk uji coba rilis sebelum ditawarkan ke klien | Ya — tiap rilis ke branch utama yang lulus CI |
| Production (per klien) | Server masing-masing klien | **Tidak** — manual, terjadwal (§6.3) |

### 6.5 Rollback

Karena update berbasis git tag (SETUP.md §7), rollback = checkout tag versi sebelumnya. Bila rilis yang bermasalah menyertakan migrasi skema baru, `php artisan migrate:rollback` dijalankan **hanya** setelah backup database dipastikan ada (SETUP.md §6) — migrasi yang sudah mengubah data produksi (bukan hanya struktur) tidak selalu aman di-rollback otomatis, perlu ditinjau kasus per kasus.

---

## 7. Referensi Terkait
- Langkah instalasi & environment development: `SETUP.md`
- Strategi rilis & versioning (sisi aplikasi): `DESIGN.md` §7
- Checklist keamanan produksi: `SETUP.md` §8
- Strategi testing: `DESIGN.md` §10
