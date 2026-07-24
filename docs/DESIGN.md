# DESIGN — Arsitektur & Rancangan Teknis
## SIMPEG (Sistem Informasi Manajemen Kepegawaian)

Status: Draft rancangan awal
Terakhir diperbarui: 2026-07-23

---

## 1. Arsitektur Umum

- **Model deployment:** satu instalasi (server + database) per perusahaan pelanggan. Tidak ada multi-tenancy di level aplikasi — isolasi antar klien terjadi karena tiap klien punya instalasi fisik sendiri.
- **Stack:** Laravel 13 (backend) + Inertia.js + Vue 3 (frontend), Tailwind CSS, MySQL 8.
- **Satu basis kode, dua pengalaman:** admin/HRD/atasan mendapat layout desktop (sidebar), pegawai mendapat layout mobile (bottom navigation). Ditentukan oleh role setelah login, bukan aplikasi terpisah.
- **Auth:** Laravel Fortify (email/password + 2FA opsional) + Laravel Socialite (Google OAuth, dibatasi ke email yang sudah terdaftar) + Spatie Laravel-Permission (role & permission). Rate limiting login (SRS FR-1.8) memakai throttle middleware bawaan Fortify (lockout progresif per akun/IP setelah beberapa kali percobaan gagal) — tidak perlu paket tambahan.
- **Direktori Pegawai dengan field terbatas (FR-17.4):** satu endpoint/API Resource yang sama untuk semua role, kolom yang dikembalikan disaring berdasarkan role pengguna yang login (mis. via Laravel API Resource conditional attribute) — bukan dua endpoint terpisah dengan query berbeda, supaya tidak ada logika ganda yang bisa lupa disinkronkan saat field baru ditambahkan ke Data Pegawai.
- **Kredensial integrasi dinamis:** `integration_credentials.client_id`/`client_secret` memakai Eloquent encrypted cast (bukan disimpan plain), dan dibaca langsung dari database saat request (mis. saat inisialisasi Socialite Google driver) — bukan lewat `config()` yang di-cache saat deploy. Ini supaya Super Admin ganti kredensial dari UI langsung berlaku tanpa perlu `php artisan config:cache` ulang atau restart server.

```
Browser (Vue + Inertia)
        │  (form submit / navigasi via Inertia, bukan REST API terpisah)
        ▼
Laravel App (Controllers, Form Requests, Policies)
        │
        ├── Queue (notifikasi, resize foto, generate PDF/laporan)
        ├── Storage (foto selfie, dokumen — disk lokal atau S3-compatible)
        └── MySQL (satu database per instalasi)
```

---

## 2. Struktur Frontend (resources/js)

```
resources/js/
├── layouts/
│   ├── AdminLayout.vue      → sidebar + topbar, dipakai HRD/Admin/Atasan
│   └── EmployeeLayout.vue   → bottom navigation, dipakai Pegawai (mobile-first)
├── pages/
│   ├── auth/                → Login (email/password + tombol Google)
│   ├── account/
│   │   └── Index.vue        → "Akun Saya" (FR-1.9) — password, 2FA, avatar, bahasa, notifikasi.
│   │                            Dipakai lintas role: diakses dari dropdown avatar di AdminLayout,
│   │                            dan dari bagian bawah tab Profil di EmployeeLayout.
│   ├── admin/
│   │   ├── Dashboard.vue
│   │   ├── Pegawai/{Index,Create,Edit}.vue
│   │   ├── Absensi/Index.vue
│   │   ├── JadwalShift/{Index,Roster}.vue   → hanya relevan bila metode penjadwalan = Shift rotasi
│   │   ├── Izin/{Index,Approval}.vue
│   │   ├── Lembur/{Index,Approval}.vue
│   │   ├── Reimbursement/{Index,Approval}.vue
│   │   ├── PerjalananDinas/{Index,Approval}.vue
│   │   ├── Kepangkatan/Index.vue
│   │   ├── Kinerja/Index.vue
│   │   ├── Dokumen/Index.vue
│   │   ├── Tunjangan/Index.vue
│   │   ├── Payroll/{Index,Detail,Slip}.vue
│   │   ├── StrukturOrganisasi/Index.vue   → org chart interaktif
│   │   ├── AuditLog/Index.vue             → khusus Super Admin/HRD, read-only
│   │   ├── Laporan/Index.vue
│   │   └── Pengaturan/{Perusahaan,Integrasi,Tunjangan,JadwalKerja,Cuti,Absensi,Gaji,Notifikasi,Bahasa}.vue
│   └── employee/
│       ├── Beranda.vue      → kartu absen, ringkasan jam kerja & sisa cuti
│       ├── Absensi.vue      → riwayat + tombol absen
│       ├── Pengajuan.vue    → hub (kartu Izin & Cuti / Lembur / Reimbursement / Perjalanan Dinas), bukan tab sejajar — lihat §5.1
│       ├── Riwayat.vue      → segmented Kehadiran (log harian per bulan) / Slip Gaji (daftar → detail breakdown)
│       ├── Direktori.vue    → diakses dari tab Profil (bukan item bottom-nav baru), field terbatas sesuai FR-17.4
│       └── Profil.vue       → termasuk form edit data non-kritikal (FR-2.9), pengajuan perubahan data sensitif (FR-2.10), dan tautan ke Direktori
└── components/               → StatTile, StatusChip, Card, DataTable, dst — dipakai lintas layout
```

Rujukan visual (mockup interaktif dashboard admin & mobile bottom-nav) sudah dibuat sebagai artifact terpisah pada sesi perancangan; jadikan acuan styling saat membangun `AdminLayout.vue` dan `EmployeeLayout.vue`.

---

## 3. Skema Database (ERD Naratif)

### 3.1 Identitas & Struktur Organisasi
```
users
├── id, name, email, password (nullable), google_id (nullable)
├── avatar_path (nullable — satu sumber avatar untuk semua jenis akun, dipakai juga oleh Pegawai lewat relasi employee_id, bukan disimpan duplikat di employees)
├── locale (preferensi bahasa pribadi, FR-11.9 & FR-1.9)
├── two_factor_secret, role_id (via Spatie: model_has_roles)
└── employee_id (FK, nullable — HRD/Admin non-pegawai boleh tidak punya baris employee)

employees
├── id, user_id (FK), nip/employee_number, nama, tanggal_lahir, tanggal_bergabung
├── status_kawin, jumlah_tanggungan
├── status_kepegawaian (probation/tetap/kontrak/honorer)
   ("Masa Kerja" di UI adalah field hitungan dari tanggal_bergabung ke tanggal berjalan — bukan kolom tersimpan, dihitung saat render, dipakai juga oleh notifikasi hari jadi kerja FR-12.2)
├── tanggal_mulai_probation (nullable), tanggal_akhir_probation (nullable)
├── status_aktif (aktif/nonaktif), tanggal_resign (nullable), alasan_resign (nullable)
└── unit_id (FK), atasan_id (FK → employees.id, nullable)
   (foto profil ada di users.avatar_path, bukan di sini — lihat catatan di tabel users)

units
├── id, nama_unit, parent_unit_id (nullable, untuk hierarki)
└── kepala_unit_id (FK → employees.id)

> **Catatan resolusi "atasan":** ada dua sumber atasan yang sengaja dipisah. `units.kepala_unit_id` adalah kepala unit secara struktural (dipakai untuk tampilan org chart & default saat unit baru dibuat). `employees.atasan_id` adalah atasan langsung yang dipakai sebagai **approver pertama** untuk izin/cuti/lembur pegawai tersebut (SRS §3.4, §3.5). Umumnya nilainya sama dengan `kepala_unit_id` milik unitnya, tapi dibuat dapat di-override per pegawai untuk kasus matrix reporting atau saat kepala unit sendiri butuh approver ke atasan di atasnya (bukan ke dirinya sendiri). Saat pegawai baru dibuat/dipindah unit, `atasan_id` sebaiknya di-default-kan otomatis ke `kepala_unit_id` unit tersebut, lalu boleh diubah manual oleh HRD bila perlu.

employee_education   (riwayat pendidikan)
├── employee_id, jenjang, institusi, tahun_lulus

employee_documents    (dokumen & arsip)
├── employee_id, jenis_dokumen, file_path, tanggal_berlaku (nullable), tanggal_kedaluwarsa (nullable)

profile_change_requests   (perubahan field sensitif yang butuh verifikasi HRD — SRS FR-2.10)
├── employee_id, field, nilai_lama, nilai_baru
├── status (pending/approved/rejected), reviewed_by, reviewed_at
```

### 3.2 Absensi
```
work_locations
├── id, unit_id (nullable), nama_lokasi, latitude, longitude, radius_meter

work_schedules   (metode "Tetap mingguan")
├── id, unit_id atau employee_id, hari, jam_masuk, jam_pulang
└── durasi_istirahat_menit

shifts   (metode "Shift rotasi" — definisi jenis shift)
├── id, nama_shift, jam_mulai, jam_selesai
├── lintas_hari_berikutnya (bool — shift malam yang melewati tengah malam)
└── durasi_istirahat_menit

shift_schedules   (roster — shift aktual per pegawai per tanggal)
├── id, employee_id, shift_id, tanggal

shift_swap_requests
├── id, requester_employee_id, shift_schedule_id_requester
├── target_employee_id, shift_schedule_id_target
└── status, approved_by

attendances
├── id, employee_id, work_location_id, tanggal
├── jam_masuk, lat_masuk, lng_masuk, jarak_masuk_meter, foto_masuk (path)
├── jam_pulang, lat_pulang, lng_pulang, jarak_pulang_meter, foto_pulang (path)
├── status (hadir/telat/di_luar_radius/izin/cuti/alpha/dinas_luar)
└── UNIQUE(employee_id, tanggal)

holidays
├── tanggal, keterangan
```

### 3.3 Izin, Cuti & Lembur
```
leave_types
├── id, nama, kuota_default_per_tahun, perlu_lampiran (bool)
└── carry_over_maks_hari (nullable — null berarti hangus, angka berarti batas maksimum hari yang bisa dibawa)

leave_balances   (saldo cuti per pegawai per tahun — dasar perhitungan hangus/carry-over & uang pengganti resign)
├── employee_id, leave_type_id, tahun
├── kuota_awal, carry_over_masuk (dari tahun sebelumnya)
├── terpakai, sisa
└── carry_over_berlaku_sampai (tanggal, nullable)

leave_requests
├── id, employee_id, leave_type_id, tanggal_mulai, tanggal_selesai
├── alasan, lampiran_path, status (pending/approved/rejected)

leave_approvals   (jejak approval berjenjang)
├── leave_request_id, approver_id, urutan, status, catatan, approved_at

overtimes
├── id, employee_id, tanggal, jam_mulai, jam_selesai, durasi_jam
├── uraian_tugas, lampiran_spl_path
├── status, approved_by, jenis_kompensasi (uang_lembur/libur_pengganti)
└── validasi submit: durasi_jam harus ≥ setting `lembur_ambang_menit` (FR-5.9), dicek di server sebelum baris tersimpan — bukan hanya validasi di form frontend

overtime_rate_tiers   (tarif lembur bertingkat — FR-5.10–5.12)
├── id, jenis_hari (hari_kerja/hari_libur_mingguan/hari_libur_nasional)
├── jam_ke_dari, jam_ke_sampai (nullable = seterusnya, mis. "jam ke-2 dst")
└── pengali (decimal, mis. 1.5 / 2.0 / 3.0 / 4.0)

reimbursement_types
├── id, nama, butuh_bukti (bool), limit_nominal (nullable)

reimbursements
├── id, employee_id, reimbursement_type_id, tanggal, nominal
├── keterangan, bukti_path
├── status, approved_by

business_trips   (Perjalanan Dinas)
├── id, employee_id, tujuan, tanggal_berangkat, tanggal_kembali, keperluan
├── uang_saku, lampiran_sppd_path
├── status, approved_by
```

### 3.4 Kepangkatan & Kinerja
```
employee_positions   (riwayat jabatan/mutasi)
├── employee_id, jabatan, grade, tmt (tanggal mulai tugas), tanggal_berakhir (nullable)

performance_periods
├── id, nama_periode, tanggal_mulai, tanggal_selesai

performance_targets
├── employee_id, performance_period_id, uraian_target, bobot

performance_reviews
├── employee_id, performance_period_id, realisasi, skor, catatan_atasan, reviewed_by
```

### 3.5 Tunjangan & Payroll
```
allowance_types
├── id, nama, kategori (tetap/berbasis_kehadiran/berbasis_lembur), nominal_dasar
└── aturan_potongan (json — mis. {"alpha_pct_per_hari": 5, "telat_batas": 3})

employee_allowances
├── employee_id, allowance_type_id, periode (YYYY-MM)
├── nominal_dasar, total_potongan, nominal_akhir, status (draft/final)

salary_grades
├── id, nama_grade, nominal_gaji_pokok, tipe_perhitungan (bulanan/per_jam/per_unit)

tax_rules
├── tahun_berlaku, skema (TER), parameter (json)

payroll_periods
├── id, bulan, tahun, status (draft/diproses/final/dibayarkan)

payroll_items
├── id, employee_id, payroll_period_id
├── gaji_pokok, total_tunjangan, uang_lembur, total_potongan, gaji_bersih
├── status

payroll_item_details   (rincian baris untuk slip gaji)
├── payroll_item_id, jenis (tunjangan/potongan), nama_komponen, nominal
```

### 3.6 Pengaturan & Notifikasi
```
company_profile      → singleton per instalasi
├── nama, logo_path, alamat, npwp, zona_waktu
└── warna_primary, warna_accent (hex — di-inject sebagai CSS custom property saat render, lihat §5.5)

integration_credentials   (Google OAuth, WhatsApp API — FR-11.15–11.17)
├── id, jenis (google_oauth/whatsapp_api)
├── client_id (encrypted), client_secret (encrypted)
└── aktif (bool)

settings              → key-value store generik untuk konfigurasi lain
                         (metode_absensi, jumlah_tingkat_approval, dst.)

notifications          → Laravel notifications table bawaan (polymorphic)

privacy_policy_versions
├── id, versi, isi_kebijakan, berlaku_sejak

employee_consents
├── employee_id, privacy_policy_version_id, disetujui_at

audit_logs   (jejak perubahan terpusat — SRS §3.18)
├── id, user_id (pelaku aksi)
├── auditable_type, auditable_id (polymorphic — mis. Employee, Attendance, PayrollItem, Setting, ModelHasRoles)
├── event (created/updated/deleted)
├── old_values (json), new_values (json)
├── ip_address, user_agent
└── created_at   (tidak ada updated_at/soft delete — baris log tidak pernah diubah, hanya ditambah)
```

**Mekanisme capture:** dipasang lewat model observer/event listener Eloquent (atau paket siap pakai seperti `spatie/laravel-activitylog`) pada model `Employee`, `Attendance`, `PayrollItem`, `Setting`, dan model role/permission — bukan ditulis manual di tiap controller, supaya tidak ada perubahan yang lolos tidak tercatat. Endpoint melihat audit log (`admin/AuditLog/Index.vue`) hanya dirender untuk role Super Admin/HRD sesuai FR-18.4, dengan filter per modul & rentang tanggal.

---

## 4. Alur Integrasi Antar-Modul (yang tidak terlihat dari daftar tabel saja)

0. **Resolusi approver**: setiap pengajuan izin/cuti/lembur menetapkan approver pertama dari `employees.atasan_id` milik pengaju (bukan langsung `units.kepala_unit_id`) — lihat catatan resolusi atasan di §3.1. Approver kedua & seterusnya (mis. HRD) ditentukan dari jumlah tingkatan approval yang dikonfigurasi (SRS FR-11.7), dicatat berurutan di `leave_approvals`.
0a. **Resolusi jadwal & durasi kerja efektif**: saat validasi absen/lembur, sistem menentukan jadwal yang berlaku dari `work_schedules` (metode Tetap) atau `shift_schedules` pada tanggal terkait (metode Shift, lihat §3.2) — bukan hardcode salah satu. Durasi kerja efektif = (jam pulang − jam masuk) − `durasi_istirahat_menit` dari jadwal/shift yang berlaku (SRS FR-3.9). Untuk shift malam (`lintas_hari_berikutnya = true`), jam pulang yang tercatat di tanggal kalender berikutnya tetap dipasangkan ke `attendances` tanggal shift dimulai, bukan dianggap alpha di hari itu maupun absen ganda di hari berikutnya.
1. **Izin approved → Absensi**: job harian menandai `attendances.status = 'izin'/'cuti'` untuk tanggal yang tercakup `leave_requests` berstatus approved, sehingga pegawai tidak perlu absen manual saat cuti dan tidak tercatat alpha.
2. **Absensi + Izin → Tunjangan Kinerja**: akhir bulan, job menghitung rekap kehadiran (`attendances` + `leave_requests`) lalu menerapkan `aturan_potongan` dari `allowance_types` untuk menghasilkan `employee_allowances` (status draft).
3. **Lembur approved → Tunjangan/Payroll**: `overtimes` yang disetujui dengan `jenis_kompensasi = uang_lembur` dihitung nominalnya dengan mencocokkan `jenis_hari` & rentang jam ke `overtime_rate_tiers` (upah per jam = `salary_grades.nominal_gaji_pokok` ÷ setting `upah_per_jam_pembagi`, default 173), lalu masuk sebagai komponen `uang_lembur` di `payroll_items` periode terkait.
4. **Tunjangan + Lembur + Grade → Payroll**: proses payroll bulanan menggabungkan `salary_grades` (gaji pokok), `employee_allowances` (final), `overtimes` (uang lembur), dikurangi potongan dari `tax_rules` dan BPJS, menghasilkan `payroll_items` + `payroll_item_details`.
5. **Dokumen kedaluwarsa & Ulang tahun → Notifikasi**: scheduled job harian mengecek `employee_documents.tanggal_kedaluwarsa` dan `employees.tanggal_lahir`/`tanggal_bergabung`, mengirim notifikasi ke pihak terkait (§SRS FR-12.1, FR-12.2, FR-12.4).
6. **Tutup tahun cuti → leave_balances**: job terjadwal pada tanggal pergantian periode kuota (default 1 Januari) menghitung `sisa` cuti tahun berjalan per pegawai, memindahkannya ke `carry_over_masuk` baris `leave_balances` tahun berikutnya (dibatasi `leave_types.carry_over_maks_hari`) atau menghanguskannya bila jenis cuti tidak mengizinkan carry-over — tercatat di Audit Log.
7. **Resign → Uang Pengganti Cuti & Payroll**: saat status pegawai diubah ke resign, sistem baca `sisa` di `leave_balances` tahun berjalan, kalikan dengan upah harian (`salary_grades.nominal_gaji_pokok` ÷ setting pembagi hari kerja bulanan), masukkan sebagai baris `payroll_item_details` pada `payroll_items` periode terakhir pegawai tersebut — di samping komponen gaji prorata (FR-10.8).

---

## 5. Desain UI/UX

### 5.1 Prinsip
- **Admin/Desktop**: sidebar navigasi tetap, stat card ringkasan di atas, tabel data dengan paginasi & filter, chip status berwarna semantik (hijau=disetujui, kuning=menunggu, merah=ditolak).
- **Pegawai/Mobile**: bottom navigation 5 item tetap (Beranda, Absensi, **Pengajuan**, Riwayat, Profil) — dipilih karena selalu terjangkau ibu jari dan pola yang familiar dari aplikasi sehari-hari. Tab "Pengajuan" adalah **hub dua lapis**: layar utama berisi kartu (Izin & Cuti, Lembur, Reimbursement, Perjalanan Dinas) + riwayat gabungan, tap kartu untuk masuk ke form spesifik dengan tombol kembali — pola ini dipilih supaya penambahan jenis pengajuan baru di masa depan tidak memaksa bottom-nav bertambah item. Modul yang tumbuh serupa (Slip Gaji, Direktori Pegawai) memakai pola navigasi bersarang yang sama di dalam tab Riwayat/Profil, bukan menambah tab baru.
- **Palet warna & tipografi**: lihat referensi visual mockup (teal institusional + aksen emas, serif untuk judul/formal, sans-serif untuk UI, monospace untuk angka/jam).

### 5.2 Alur Absen (GPS + Selfie)
1. Tombol "Absen Masuk" memicu permintaan izin browser untuk Geolocation API dan MediaDevices (kamera).
2. Koordinat + foto selfie diambil di sisi klien, dikirim bersama timestamp ke server.
3. Server menghitung jarak ke `work_locations` terdekat (formula Haversine); validasi dilakukan di server, bukan dipercaya dari klien.
4. Hasil (status + jarak) disimpan; UI kartu Beranda memperbarui status secara langsung.

**Catatan wajib:** Geolocation API & akses kamera browser mensyaratkan HTTPS (secure context), kecuali di `localhost` saat development. Ini masuk syarat mutlak deployment produksi.

### 5.3 PWA & Push Notification
Tampilan mobile (`EmployeeLayout.vue`) dilengkapi `manifest.json` + service worker agar dapat di-*install* ke home screen seperti aplikasi native, dan menerima push notification browser (Web Push API) untuk reminder absen/status izin (SRS FR-12.9) — tanpa perlu membangun aplikasi native terpisah. HTTPS yang sudah menjadi syarat wajib (§5.2) juga menjadi prasyarat service worker.

**Install prompt (FR-12.10–12.12):**
- **Android/Chrome/Edge**: aplikasi menangkap event `beforeinstallprompt` (dicegah tampil otomatis lewat `event.preventDefault()`), disimpan, lalu dipicu manual saat pengguna menekan tombol "Pasang" pada banner kustom milik aplikasi — bukan mengandalkan mini-infobar bawaan browser yang waktu munculnya tidak terkontrol.
- **iOS/Safari**: `beforeinstallprompt` tidak tersedia sama sekali (keterbatasan platform, bukan bug). Deteksi iOS dilakukan lewat `navigator.userAgent` + cek `navigator.standalone`, lalu tampilkan banner berisi instruksi manual bergambar (ikon Share → "Add to Home Screen") sebagai pengganti dialog otomatis.
- Banner ditampilkan pertama kali setelah kondisi engagement minimal terpenuhi (mis. sudah login & buka Beranda), bukan langsung di layar pertama. Status "sudah ditutup" disimpan di `localStorage` dengan masa tenang (mis. 14 hari) sebelum ditawarkan lagi — dicek di client, tidak perlu bolak-balik ke server.

### 5.4 Multi-bahasa (i18n)
Menggunakan mekanisme lokalisasi bawaan Laravel (file `lang/id/*.php` & `lang/en/*.php`) di sisi backend, dan library i18n Vue (mis. `vue-i18n`) di sisi frontend untuk string UI. Bahasa default instalasi diatur di Pengaturan (SRS FR-11.9); pengguna individu dapat menimpa preferensinya sendiri, disimpan di `users.locale`.

### 5.5 Warna Tema Kustom (White-label Ringan)
`company_profile.warna_primary` & `warna_accent` di-inject sebagai override CSS custom property (`--primary`, `--accent`, dst. — lihat token warna di §5.1) lewat `<style>` singkat di root layout Blade, dirender sekali per request dari data server, bukan hardcode di CSS statis. Karena seluruh komponen (AdminLayout maupun EmployeeLayout) sudah dibangun di atas token ini, mengganti dua nilai hex tersebut otomatis mengubah tampilan di seluruh aplikasi tanpa menyentuh komponen satu per satu.

---

## 6. Pertimbangan Performa (target 100–500 pegawai per instalasi)

- Index komposit `(employee_id, tanggal)` pada `attendances`, `(employee_id, status)` pada `leave_requests`.
- Proses berat (resize foto, kirim notifikasi, generate PDF slip gaji/laporan) dijalankan lewat queue (driver `database` cukup untuk skala ini, dapat naik ke Redis bila perlu).
- Statistik dashboard (stat card, grafik mingguan) di-cache & di-refresh terjadwal, bukan dihitung ulang tiap request.
- Foto selfie & dokumen disimpan di disk/storage, bukan BLOB database, dengan kebijakan retensi (lihat POLICIES.md).
- Paginasi wajib di semua tabel data admin (bawaan Laravel).
- Import data pegawai massal (FR-2.7) diproses lewat job antrean, bukan request sinkron — file besar (ratusan baris) tidak boleh membuat browser HRD timeout menunggu.

---

## 7. Strategi Rilis & Update Antar Instalasi

> Pipeline CI/CD teknis (contoh workflow, environment staging, spesifikasi server) ada di `INFRASTRUCTURE.md` — bagian ini fokus ke strategi versioning & proses update sisi aplikasi.

Karena model bisnisnya "jual per instalasi terpisah" (§1), perbaikan bug atau fitur baru tidak otomatis sampai ke semua klien seperti SaaS. Perlu disiplin rilis:

- Kode dikelola sebagai satu repository inti dengan **tag versi** (mis. `v1.2.0`) — kustomisasi spesifik klien sebaiknya dipisah ke branch/config, bukan menimpa kode inti, supaya update tetap bisa ditarik.
- Tiap instalasi klien mencatat versi yang terpasang (mis. di tabel `settings` atau file `VERSION`), sehingga saat ada laporan bug, jelas versi mana yang dipakai klien tersebut.
- Setiap rilis menyertakan **changelog** & migration baru dijalankan lewat `php artisan migrate` saat maintenance window terjadwal per klien, bukan otomatis tanpa sepengetahuan HRD klien (perubahan skema bisa berdampak ke data operasional harian).
- Perubahan yang bersifat breaking (mis. ubah struktur `payroll_items`) wajib diuji di lingkungan staging sebelum diterapkan ke instalasi produksi manapun.

## 8. Backup & Disaster Recovery

- **Database**: backup harian otomatis (`mysqldump` terjadwal via cron/Task Scheduler), retensi minimal 30 hari, disimpan di lokasi terpisah dari server aplikasi (mis. object storage/offsite).
- **Storage foto & dokumen**: dicadangkan terpisah dari database (volume lebih besar, frekuensi bisa harian/mingguan tergantung kapasitas), selaras dengan kebijakan retensi foto selfie di POLICIES.md.
- **.env & kredensial**: dicadangkan aman terenkripsi, terpisah dari backup data operasional (bukan di repository kode).
- Uji **restore** dilakukan berkala (mis. tiap triwulan) — backup yang belum pernah diuji restore-nya tidak dapat diasumsikan valid.
- Ini menjadi bagian dari checklist onboarding klien baru (lihat SETUP.md §4).

## 9. Keputusan Teknis Terkonfirmasi (log ringkas dari sesi perancangan)

| Keputusan | Pilihan Final |
|---|---|
| Backend | Laravel 13 |
| Frontend | Inertia + Vue 3 (bukan Livewire) |
| Model deployment | Instalasi terpisah per klien, bukan SaaS multi-tenant |
| Metode absensi | Web/mobile browser, GPS + selfie |
| Status kepegawaian | Non-PNS/Kontrak (skema gaji fleksibel, bukan tabel PNS) |
| Payroll | Cakupan penuh (gaji pokok, tunjangan, potongan pajak/BPJS, slip gaji, file transfer) |
| Login | Email/password + Google OAuth, keduanya dibatasi ke email yang sudah terdaftar HRD |
| Database | MySQL |
| PDF (slip gaji/laporan) | Dompdf — prioritas ringan/kompatibel hosting klien kecil-menengah (§12.2) |
| Peta lokasi kerja | Leaflet + OpenStreetMap — gratis, tanpa API key per klien (§12.2) |

---

## 10. Strategi Testing

Framework testing: **Pest** (sudah termasuk starter kit, lihat SETUP.md §2).

| Area | Jenis Test | Prioritas |
|---|---|---|
| Kalkulasi Payroll (gaji pokok + tunjangan + lembur − potongan pajak/BPJS) | Unit test dengan kasus tepi (resign di tengah bulan/pro-rata, tunjangan nol, potongan maksimum) | **Wajib**, tidak boleh dilewati sebelum rilis apa pun yang menyentuh `payroll_items` |
| Formula potongan Tunjangan Kinerja dari rekap kehadiran | Unit test | Wajib |
| Perhitungan radius GPS (Haversine) untuk Absensi | Unit test | Wajib |
| Alur approval berjenjang (Izin/Lembur/Reimbursement/Dinas) | Feature test — pastikan urutan approver & perubahan status benar | Wajib |
| Login (email/password, Google OAuth, rate limiting) | Feature test | Wajib |
| Import data pegawai (Excel/CSV) | Feature test dengan file contoh valid & tidak valid | Disarankan |
| Generate slip gaji PDF & file transfer bank | Feature test — validasi format output, bukan hanya "tidak error" | Wajib untuk modul Payroll |

**Aturan rilis:** perubahan yang menyentuh skema atau logika `payroll_items`, `attendances`, atau alur approval wajib lulus seluruh test suite terkait sebelum di-tag sebagai versi rilis (lihat §7 Strategi Rilis). Modul Payroll mendapat perhatian test paling ketat karena kesalahan hitung berdampak langsung ke uang yang diterima pegawai.

---

## 11. Keamanan Aplikasi (SRS §3.19)

### 11.1 Otorisasi level data (bukan hanya level fitur)
Role menentukan modul apa yang terlihat di navigasi (level fitur), tapi **tiap request ke record individual** (mis. `GET /attendances/482`) tetap wajib lolos pengecekan kepemilikan/lingkup lewat Laravel Policy per model (`AttendancePolicy`, `LeaveRequestPolicy`, `PayrollItemPolicy`, dst.):
- Pegawai: hanya record dengan `employee_id` miliknya sendiri
- Atasan: hanya record milik pegawai dalam `unit_id` yang sama dengan miliknya
- HRD/Super Admin: sesuai matriks role SRS §5

Ini mencegah *insecure direct object reference* — pegawai tidak bisa lihat slip gaji orang lain hanya dengan mengubah ID di URL, walau ID-nya bisa ditebak.

### 11.2 Keamanan berkas unggahan
- Validasi tipe file berdasarkan **konten/MIME**, bukan hanya ekstensi nama file (Laravel `mimes`/`mimetypes` rule) — mencegah file `.php` yang di-rename jadi `.jpg`.
- Batas ukuran maksimum per jenis unggahan (foto selfie, dokumen, bukti reimbursement, lampiran SPPD/SPL) dikonfigurasi, bukan hardcode.
- Disimpan di disk **privat** (`storage/app/private` atau setara), disajikan lewat route terautentikasi dengan header `Content-Disposition`, bukan lewat `storage/app/public` yang bisa diakses langsung.
- Folder penyimpanan dikonfigurasi agar tidak dapat mengeksekusi skrip (mis. via `.htaccess`/konfigurasi Nginx yang menonaktifkan eksekusi PHP di folder storage).

### 11.3 Sesi & manajemen perangkat
Memakai session driver `database` (bukan `file`, supaya bisa di-query & dicabut). Halaman Akun Saya (FR-1.9) menampilkan daftar sesi aktif (perangkat/browser, waktu aktif terakhir) dengan tombol "Keluar dari semua perangkat" yang menghapus seluruh baris sesi pengguna tersebut kecuali sesi berjalan saat ini.

### 11.4 Validasi server sebagai batas keamanan sesungguhnya
Semua pembatasan field yang tampak "disembunyikan" di UI (mis. field non-editable untuk Pegawai, FR-2.9–2.11) **wajib** diterapkan ulang di Form Request/Policy sisi server. UI yang menyembunyikan tombol/field adalah kenyamanan pengguna, bukan kontrol akses — permintaan API yang dibuat manual (lewat DevTools/Postman) harus tetap ditolak server bila melanggar aturan yang sama.

### 11.5 Google OAuth — verifikasi email
Sebelum mencocokkan email dari Google ke data pegawai terdaftar (SRS FR-1.2), sistem memeriksa klaim `email_verified` dari payload Google OAuth (tersedia lewat Laravel Socialite) — email yang belum terverifikasi Google ditolak, tidak diperlakukan sama dengan email terverifikasi.

### 11.6 Header keamanan HTTP
Middleware menambahkan header berikut di setiap respons produksi: `Content-Security-Policy` (membatasi sumber skrip/gaya ke domain sendiri), `X-Frame-Options: DENY` (mencegah clickjacking), `Strict-Transport-Security` (memaksa HTTPS di kunjungan berikutnya). Dikonfigurasi sekali di level middleware global, bukan per-controller.

### 11.7 Manajemen kerentanan dependency
`composer audit` dan `npm audit` dijalankan sebagai bagian dari proses rilis (§7 Strategi Rilis) — bukan hanya sekali saat setup pertama. Kerentanan tingkat tinggi/kritis pada dependency wajib ditinjau sebelum rilis dilanjutkan ke instalasi klien.

### 11.8 Enkripsi backup
Backup database (§8) dienkripsi saat disimpan (mis. `gpg`/enkripsi bawaan penyedia object storage), bukan hanya dilindungi lewat pembatasan akses folder — supaya kebocoran file backup saja tidak otomatis membocorkan seluruh data.

---

## 12. Daftar Paket & Library

### 12.1 Sudah bawaan starter kit Laravel 13 Vue (tidak perlu ditambah)
`inertiajs/inertia-laravel`, `laravel/fortify`, `laravel/wayfinder`, `laravel/pint`, `larastan/larastan`, `pest`, `shadcn-vue`, Tailwind, TypeScript.

### 12.2 Ditambah manual sesuai kebutuhan SIMPEG

| Kebutuhan | Paket | Alasan |
|---|---|---|
| Login Google | `laravel/socialite` | FR-1.2, belum termasuk starter kit |
| Role & permission | `spatie/laravel-permission` | Role/permission granular (§5 SRS) |
| Audit log | `spatie/laravel-activitylog` | Mekanisme capture via model observer (SRS §3.18) |
| Import Excel pegawai | `maatwebsite/excel` | FR-2.7 |
| PDF slip gaji & laporan | `barryvdh/laravel-dompdf` | Ringan, jalan di shared hosting — prioritas kompatibilitas server klien kecil-menengah di atas presisi visual maksimum |
| Push notification (PWA) | `laravel-notification-channels/webpush` | FR-12.9 |
| PWA (manifest + service worker) | `vite-plugin-pwa` | Terintegrasi ke build Vite yang sudah ada |
| Grafik dashboard | `vue-chartjs` (Chart.js) | Ringan, mudah disesuaikan ke token warna kustom (§5.5) |
| Date range picker | `@vuepic/vue-datepicker` | Form Izin/Cuti/Lembur/Dinas |
| Ikon | `lucide-vue-next` | Pasangan umum shadcn-vue, gaya line-icon konsisten dengan mockup |
| Peta lokasi kerja | `leaflet` + `vue-leaflet` (tile OpenStreetMap) | Radius GPS di Pengaturan (§3.6) — gratis, tanpa API key/billing per klien, penting untuk model jual-ke-banyak-instalasi |
| WhatsApp Business API | *(tanpa paket tetap — custom notification channel via `Http` facade)* | Provider WhatsApp bervariasi per klien (Meta Cloud API langsung, Twilio, dst.), lebih fleksibel sebagai channel notifikasi kustom daripada dikunci ke satu paket/vendor |

### 12.3 Catatan kompatibilitas Dompdf
Karena presisi rendering CSS Dompdf lebih terbatas dari Browsershot, slip gaji dirancang dengan layout sederhana (tabel & blok, bukan flexbox/grid kompleks) supaya tetap rapi di kedua mesin — kalau suatu saat klien enterprise butuh presisi visual penuh (mengikuti warna tema §5.5 secara akurat), instalasi klien itu bisa di-upgrade ke `spatie/laravel-pdf` tanpa mengubah data/skema, hanya mengganti driver render PDF.
