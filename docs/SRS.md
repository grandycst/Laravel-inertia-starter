# SRS — Software Requirements Specification
## SIMPEG (Sistem Informasi Manajemen Kepegawaian)

Status: Draft rancangan awal
Terakhir diperbarui: 2026-07-23

---

## 1. Pendahuluan

### 1.1 Tujuan
Dokumen ini mendefinisikan kebutuhan fungsional dan non-fungsional untuk SIMPEG — sistem manajemen kepegawaian yang mencakup data pegawai, absensi, izin/cuti, lembur, kepangkatan, penilaian kinerja, dokumen, tunjangan, dan penggajian.

### 1.2 Model Produk
SIMPEG dibangun sebagai **produk generik yang dijual per instalasi**. Setiap perusahaan pelanggan mendapat instalasi (server & database) sendiri-sendiri, bukan satu platform SaaS bersama. Kustomisasi per klien dilakukan melalui modul **Pengaturan** (lihat §3.11) dan, bila perlu, kustomisasi kode saat implementasi. Sistem harus netral industri — dapat dipakai perusahaan swasta jenis apa pun, termasuk institusi pendidikan, tanpa istilah atau aturan yang terikat pada satu jenis organisasi.

### 1.3 Definisi & Istilah
| Istilah | Arti |
|---|---|
| Pegawai | Pengguna akhir yang absen, mengajukan izin/cuti/lembur |
| HRD/Admin | Pengelola data kepegawaian & approver tingkat akhir |
| Atasan | Approver tingkat pertama (kepala unit/divisi langsung) |
| Tenant/Instalasi | Satu deployment SIMPEG untuk satu perusahaan pelanggan |
| SPL | Surat Perintah Lembur |
| Tukin | Tunjangan Kinerja (dipotong berdasarkan kehadiran) |
| TER | Tarif Efektif Rata-rata (skema perhitungan PPh21 yang berlaku) |

### 1.4 Ruang Lingkup
Termasuk: autentikasi, data pegawai, absensi (GPS+selfie), izin & cuti, lembur, kepangkatan/jabatan, penilaian kinerja, dokumen & arsip, tunjangan, payroll penuh, pengaturan, notifikasi, laporan & dashboard.

Tidak termasuk (di luar cakupan v1): rekrutmen/ATS, e-learning/LMS, aset & inventaris, payroll multi-negara/multi-mata uang, eksekusi transfer bank otomatis (sistem hanya menghasilkan file transfer, eksekusi tetap manual oleh bendahara klien).

---

## 2. Deskripsi Umum

### 2.1 Peran Pengguna (Role)

| Role | Deskripsi | Lingkup Akses |
|---|---|---|
| Super Admin | Pengelola teknis instalasi (biasanya pihak vendor/IT internal klien) | Seluruh sistem, termasuk Pengaturan |
| HRD/Admin | Mengelola data pegawai, approval akhir, payroll | Seluruh data kepegawaian |
| Atasan/Kepala Unit | Approval tingkat pertama untuk bawahan langsung | Data unit kerjanya sendiri |
| Pegawai | Presensi, pengajuan izin/cuti/lembur, lihat data diri | Data pribadi saja |

### 2.2 Tampilan Sesuai Role
- HRD/Admin/Atasan → tampilan **desktop** (sidebar navigasi)
- Pegawai → tampilan **mobile-first** (bottom navigation: Beranda, Absensi, Pengajuan, Riwayat, Profil — "Pengajuan" adalah hub untuk Izin & Cuti, Lembur, Reimbursement, dan Perjalanan Dinas, lihat DESIGN.md §5.1)

Satu basis kode Laravel + Inertia + Vue melayani kedua tampilan; redirect ditentukan oleh role setelah login.

### 2.3 Asumsi & Batasan
- Semua pegawai berstatus Non-PNS/Kontrak (bukan skema PNS pemerintah) — lihat §3.9.
- Tidak ada pendaftaran mandiri (self-registration); akun dibuat oleh HRD/Admin.
- Aplikasi memerlukan HTTPS di lingkungan produksi (wajib untuk Geolocation API & akses kamera browser).
- Satu instalasi = satu perusahaan; tidak ada isolasi multi-tenant di level aplikasi.

---

## 3. Kebutuhan Fungsional per Modul

### 3.1 Autentikasi & Otorisasi
- FR-1.1: Pengguna login dengan email + password.
- FR-1.2: Pengguna dapat login dengan Google OAuth, **hanya jika** email tersebut sudah terdaftar sebagai pegawai di sistem. Email Google yang tidak terdaftar ditolak dengan pesan yang jelas.
- FR-1.3: Tidak tersedia halaman registrasi publik.
- FR-1.4: Akun baru dibuat otomatis saat HRD menambahkan data pegawai; sistem mengirim email undangan set password.
- FR-1.5: Role Admin/HRD dapat mengaktifkan 2FA (wajib atau opsional, dikonfigurasi).
- FR-1.6: Otorisasi berbasis role & permission granular (mis. siapa boleh approve izin siapa) menggunakan sistem role-permission.
- FR-1.7: Pegawai wajib menyetujui kebijakan privasi (persetujuan pemrosesan data pribadi, termasuk foto selfie & lokasi GPS) pada login pertama, sebelum dapat mengakses fitur Absensi. Persetujuan dicatat dengan stempel waktu & versi kebijakan (lihat §3.14).
- FR-1.8: Sistem membatasi percobaan login gagal (rate limiting) — akun/IP dikunci sementara setelah melewati ambang batas, untuk mencegah serangan brute-force (lihat POLICIES.md §1).
- FR-1.9: Setiap pengguna — apa pun rolenya, termasuk Super Admin/HRD yang tidak memiliki data Pegawai terkait — punya halaman **Akun Saya** untuk: ubah password, kelola 2FA (aktifkan/nonaktifkan, lihat recovery code), ubah foto avatar, atur preferensi bahasa pribadi (FR-11.9), dan atur preferensi channel notifikasi pribadi (opt-out individual, lihat POLICIES.md §7). Halaman ini terpisah dari Data Pegawai (§3.2) karena mengatur identitas akun/login, bukan data kepegawaian.

### 3.2 Data Pegawai (Master Data)
- FR-2.1: CRUD data pegawai — biodata, kontak, status kepegawaian, unit kerja.
- FR-2.2: Riwayat pendidikan per pegawai.
- FR-2.3: Riwayat jabatan/mutasi per pegawai.
- FR-2.4: Struktur unit kerja/departemen dengan hierarki atasan-bawahan.
- FR-2.5: Field status kawin & jumlah tanggungan (dipakai modul Tunjangan Keluarga).
- FR-2.6: Pencarian & filter pegawai (nama, unit, status, jabatan) dengan paginasi.
- FR-2.7: Import data pegawai massal dari file Excel/CSV, dengan validasi baris (NIP/email duplikat, format tanggal, dll.) dan laporan error yang dapat diunduh sebelum data benar-benar disimpan.
- FR-2.8: Alur offboarding — HRD dapat mengubah status pegawai menjadi "Resign/Nonaktif" dengan tanggal efektif & alasan. Data pegawai tidak dihapus, hanya diarsipkan; akun login otomatis dinonaktifkan pada tanggal efektif.
- FR-2.9: Pegawai dapat mengubah **langsung tanpa approval** field non-kritikal miliknya sendiri: nomor HP, alamat domisili, kontak darurat. (Foto avatar diatur lewat Akun Saya, FR-1.9 — dipakai bersama untuk semua jenis akun, bukan spesifik data Pegawai.)
- FR-2.10: Perubahan field sensitif oleh pegawai (nomor rekening bank, nama, NIK) masuk sebagai **permintaan perubahan** berstatus pending, baru berlaku setelah diverifikasi & disetujui HRD — khusus nomor rekening bank, verifikasi ini wajib karena berkaitan langsung dengan tujuan transfer gaji (mitigasi risiko penipuan pengalihan gaji bila akun pegawai diretas).
- FR-2.11: Field lain (NIP, unit kerja, jabatan, status kepegawaian, gaji) hanya dapat diubah oleh HRD/Admin, tidak tersedia sama sekali di form self-service pegawai.
- FR-2.12: Pegawai baru dapat diberi status **Probation** (masa percobaan) dengan tanggal mulai & tanggal berakhir masa probation (default konfigurasi §3.11).
- FR-2.13: Sistem mengirim reminder ke HRD & atasan langsung H-14 sebelum masa probation berakhir, untuk keputusan konfirmasi menjadi pegawai tetap/kontrak atau pengakhiran hubungan kerja.
- FR-2.14: Hak cuti & jenis tunjangan tertentu dapat berlaku berbeda selama status Probation (dikonfigurasi HRD, lihat POLICIES.md).

### 3.3 Absensi
- FR-3.1: Pegawai check-in/check-out melalui web/mobile browser.
- FR-3.2: Setiap check-in/out merekam koordinat GPS dan foto selfie.
- FR-3.3: Sistem menghitung jarak ke titik lokasi kerja terdaftar (Haversine) dan menandai status "Di Luar Radius" bila melebihi radius yang dikonfigurasi — bukan ditolak otomatis, tapi ditandai untuk ditinjau HRD.
- FR-3.4: Satu pegawai hanya dapat memiliki satu catatan absensi per hari (unique constraint).
- FR-3.5: Status absensi otomatis berubah menjadi "Izin/Cuti" jika ada leave_request yang disetujui pada tanggal tersebut (tidak perlu absen manual).
- FR-3.6: Jadwal kerja dapat diatur per unit atau per pegawai, dengan dua metode penjadwalan yang dapat dipilih per instalasi (§3.11):
  - **Tetap mingguan** — jadwal sama tiap minggu (mis. Senin–Jumat 08:00–17:00), cocok untuk kantor non-shift.
  - **Shift rotasi** — pegawai dijadwalkan lewat roster shift (Pagi/Siang/Malam/custom) yang dapat berubah tiap periode; lihat FR-3.9–3.12.
- FR-3.7: Rekap kehadiran harian/mingguan/bulanan, dapat difilter per unit/pegawai.
- FR-3.8: Metode absensi (GPS saja / GPS+selfie / fingerprint) dapat dikonfigurasi per instalasi (§3.11).
- FR-3.9: Durasi jam kerja efektif dihitung dari selisih jam masuk–pulang **dikurangi durasi istirahat** yang berlaku pada jadwal/shift hari itu — bukan selisih mentah.
- FR-3.10: HRD dapat mendefinisikan jenis shift (nama, jam mulai/selesai, durasi istirahat, penanda shift yang melewati tengah malam) dan menyusun roster (jadwal shift per pegawai per tanggal).
- FR-3.11: Validasi absen untuk pegawai berjadwal shift mengacu ke roster pada tanggal terkait, termasuk penanganan shift malam yang jam pulangnya jatuh di tanggal kalender berikutnya.
- FR-3.12: Pegawai dapat mengajukan tukar shift dengan pegawai lain; perubahan roster baru berlaku setelah disetujui atasan.
- FR-3.13: Jika browser menolak izin akses lokasi atau kamera saat pegawai mencoba absen, sistem menampilkan pesan yang jelas beserta instruksi mengaktifkan izin lewat pengaturan browser — bukan error generik atau tombol yang gagal diam-diam.
- FR-3.14: Untuk kasus perangkat yang tidak mendukung Geolocation/kamera sama sekali (perangkat lama, browser tidak didukung), HRD dapat menginput catatan kehadiran manual dengan keterangan alasan — sebagai jalur pengecualian, bukan alur normal.

### 3.4 Izin & Cuti
- FR-4.1: Pegawai mengajukan izin/cuti dengan jenis, rentang tanggal, alasan, dan lampiran opsional.
- FR-4.2: Sistem menampilkan sisa kuota cuti tahunan sebelum pengajuan.
- FR-4.3: Approval berjenjang: Atasan langsung → HRD (dapat dikonfigurasi jumlah tingkatannya).
- FR-4.4: Kuota cuti terpotong otomatis saat pengajuan disetujui.
- FR-4.5: Notifikasi status (disetujui/ditolak) dikirim ke pegawai pengaju.
- FR-4.6: Kalender cuti tim terlihat oleh Atasan (untuk menghindari bentrok jadwal).
- FR-4.7: Jenis cuti & kuota default dikonfigurasi per instalasi.
- FR-4.8: Sisa kuota Cuti Tahunan yang tidak terpakai pada akhir tahun mengikuti kebijakan carry-over yang dikonfigurasi per instalasi: hangus otomatis, atau dibawa ke tahun berikutnya dengan batas maksimum hari & batas waktu pemakaian yang dikonfigurasi (lihat POLICIES.md §3).
- FR-4.9: Proses penghitungan hangus/carry-over dijalankan otomatis lewat scheduled job pada tanggal pergantian periode kuota yang dikonfigurasi (default awal tahun kalender), hasilnya tercatat di Audit Log (§3.18).

### 3.5 Lembur
- FR-5.1: Pegawai mengajukan lembur dengan tanggal, jam mulai/selesai, uraian tugas.
- FR-5.2: Dukungan lampiran dokumen (mis. SPL — Surat Perintah Lembur), wajib/opsional sesuai konfigurasi.
- FR-5.3: Mendukung dua pola pengajuan: pre-approval (sebelum lembur) atau post-submission (setelah lembur, dengan bukti).
- FR-5.4: Approval oleh atasan langsung.
- FR-5.5: Sistem menandai bila jam lembur yang diajukan tumpang tindih dengan jadwal kerja normal.
- FR-5.6: Durasi lembur dihitung otomatis dari selisih jam mulai/selesai, dikurangi durasi istirahat bila rentang lembur mencakup jam istirahat yang berlaku (konsisten dengan FR-3.9).
- FR-5.7: Lembur yang disetujui menjadi input bagi modul Tunjangan (uang lembur) dan Payroll.
- FR-5.8: Di tampilan mobile, Lembur diakses sebagai salah satu kartu dalam hub "Pengajuan" (bersama Izin & Cuti, Reimbursement, Perjalanan Dinas) — tidak menambah item bottom navigation (lihat DESIGN.md §5.1).
- FR-5.9: Pengajuan lembur hanya dapat disubmit bila durasi (hasil FR-5.6) mencapai **ambang minimum** yang dikonfigurasi per instalasi (mis. 60 menit); di bawah ambang, sistem menolak submit dengan pesan durasi belum mencukupi — berlaku sama untuk metode penjadwalan Tetap maupun Shift (FR-3.6).
- FR-5.10: Sistem menentukan otomatis jenis hari dari `overtimes.tanggal` — Hari Kerja, Hari Libur Mingguan, atau Hari Libur Nasional (dicocokkan ke `holidays` & pola hari kerja mingguan yang berlaku).
- FR-5.11: Nominal uang lembur dihitung dari **upah per jam** (gaji pokok ÷ jumlah jam kerja standar bulanan yang dikonfigurasi, default 173 jam) dikalikan **pengali bertingkat** sesuai jenis hari (FR-5.10) & rentang jam lembur ke berapa (mis. jam ke-1 vs jam ke-2 dst).
- FR-5.12: Nilai pengali tiap tingkatan/jenis hari, serta pembagi jam kerja standar bulanan, dapat dikonfigurasi bebas oleh HRD (§3.11) — default awal mengikuti pola umum ketenagakerjaan Indonesia, bukan nilai yang dipatok mati di kode.

### 3.6 Kepangkatan & Jabatan
- FR-6.1: Riwayat golongan/pangkat/grade per pegawai.
- FR-6.2: Riwayat mutasi jabatan (struktural/fungsional).
- FR-6.3: Reminder otomatis untuk kenaikan berkala (grade/gaji) mendekati jatuh tempo.

### 3.7 Penilaian Kinerja
- FR-7.1: Pencatatan target kinerja per periode (mis. SKP/e-kinerja) per pegawai.
- FR-7.2: Realisasi/capaian target diisi pegawai atau atasan.
- FR-7.3: Penilaian oleh atasan pada akhir periode dengan skor/catatan.
- FR-7.4: Riwayat penilaian kinerja dapat dilihat kembali oleh pegawai bersangkutan.

### 3.8 Dokumen & Arsip
- FR-8.1: Upload dokumen kepegawaian (SK, ijazah, sertifikat) per pegawai.
- FR-8.2: Dokumen yang memiliki masa berlaku (mis. sertifikat) mendapat reminder mendekati kedaluwarsa.
- FR-8.3: Kontrol akses dokumen — pegawai hanya melihat dokumennya sendiri, HRD melihat semua.

### 3.9 Tunjangan
- FR-9.1: Jenis tunjangan (jabatan, kinerja, keluarga, transport, dll.) didefinisikan bebas oleh HRD melalui Pengaturan, tidak hardcode di kode aplikasi.
- FR-9.2: Tunjangan Kinerja dihitung otomatis dari rekap kehadiran & izin bulan berjalan, dengan formula potongan yang dapat dikonfigurasi (mis. alpha -5%/hari).
- FR-9.3: Tunjangan Keluarga dihitung dari status kawin & jumlah tanggungan.
- FR-9.4: Hasil perhitungan tunjangan berstatus draft sebelum difinalisasi HRD.

### 3.10 Payroll (Penggajian Penuh)
- FR-10.1: Skema gaji pokok ditentukan oleh grade internal perusahaan (bukan tabel baku PNS), termasuk dukungan tipe perhitungan bulanan/per jam/per unit kerja (mis. per sesi/SKS bagi institusi pendidikan).
- FR-10.2: Payroll bulanan menggabungkan gaji pokok + semua tunjangan (dari modul Tunjangan) + uang lembur (dari modul Lembur) dikurangi potongan (BPJS Kesehatan, BPJS Ketenagakerjaan, PPh21 skema TER, potongan lain).
- FR-10.3: Aturan pajak (tarif, PTKP) disimpan sebagai data terkonfigurasi, dapat diperbarui tanpa mengubah kode, karena berubah tiap tahun mengikuti regulasi.
- FR-10.4: Alur approval berlapis: HRD siapkan (draft) → Bendahara/Keuangan verifikasi → final.
- FR-10.5: Sistem menghasilkan slip gaji (PDF) yang dapat diunduh pegawai.
- FR-10.6: Sistem menghasilkan file transfer bank (format dapat disesuaikan) untuk diunggah manual — sistem **tidak** mengeksekusi transfer secara otomatis.
- FR-10.7: Audit log wajib untuk semua perubahan pada payroll_items berstatus final.
- FR-10.8: Payroll bulan pertama pegawai baru dihitung **prorata** berdasarkan jumlah hari kerja aktual sejak `tanggal_bergabung` terhadap total hari kerja bulan tersebut — bukan gaji penuh sebulan, konsisten dengan pola prorata yang sudah berlaku untuk resign di tengah periode (POLICIES.md §8).
- FR-10.9: Saat pegawai resign dengan sisa kuota Cuti Tahunan yang belum terpakai, sistem menghitung otomatis **uang pengganti cuti** (sisa hari × upah harian) sebagai komponen tambahan pada payroll periode terakhir. Upah harian dihitung dari gaji pokok dibagi jumlah hari kerja standar bulanan yang dikonfigurasi (default 21 hari, dapat diubah per instalasi).

### 3.11 Pengaturan (Settings)
Modul ini adalah kunci agar produk mudah dikustomisasi tiap kali dijual ke perusahaan baru, tanpa mengubah kode.
- FR-11.1: Profil perusahaan (nama, logo, alamat, NPWP/identitas usaha, zona waktu) untuk branding & keperluan payroll instalasi.
- FR-11.2: Konfigurasi jenis & aturan tunjangan.
- FR-11.3: Konfigurasi jadwal kerja/shift.
- FR-11.4: Konfigurasi jenis cuti & kuota default.
- FR-11.5: Konfigurasi metode absensi (GPS / GPS+selfie / fingerprint) dan radius lokasi kerja.
- FR-11.6: Konfigurasi grade/skema gaji.
- FR-11.7: Konfigurasi jumlah tingkatan approval (izin, lembur, payroll).
- FR-11.8: Konfigurasi channel notifikasi aktif (email & in-app selalu aktif secara default; WhatsApp Business API bersifat opsional, memerlukan kredensial API sendiri — lihat POLICIES.md untuk implikasi biaya).
- FR-11.9: Konfigurasi bahasa antarmuka (Indonesia/Inggris) sebagai default instalasi; pengguna individu dapat mengganti preferensi bahasanya sendiri di Profil.
- FR-11.10: Konfigurasi metode penjadwalan (Tetap mingguan / Shift rotasi), jenis-jenis shift & durasi istirahat default (FR-3.6, FR-3.9–3.10).
- FR-11.11: Konfigurasi durasi masa probation default & aturan hak cuti/tunjangan selama probation (FR-2.12–2.14).
- FR-11.12: Konfigurasi ambang minimum durasi lembur sebelum dapat diajukan (FR-5.9).
- FR-11.13: Konfigurasi tarif lembur bertingkat (pengali per jenis hari & rentang jam) dan pembagi upah per jam bulanan (FR-5.11–5.12).
- FR-11.14: Warna tema (primer & aksen) dapat dikustomisasi Super Admin, diterapkan otomatis ke seluruh tampilan admin maupun mobile pegawai — white-label ringan per instalasi.
- FR-11.15: Kredensial integrasi (Google OAuth Client ID/Secret, WhatsApp Business API) dikonfigurasi lewat form UI Super Admin, disimpan terenkripsi di database — bukan hanya lewat file `.env` server.
- FR-11.16: Setelah kredensial tersimpan, nilainya ditampilkan masked (•••••) di UI dengan opsi "Ganti"; nilai penuh tidak pernah ditampilkan ulang setelah disimpan.
- FR-11.17: Tiap metode login (Google OAuth, WhatsApp) punya toggle aktif/nonaktif tersendiri. Kalau nonaktif atau kredensialnya belum diisi, elemen terkait (mis. tombol "Login dengan Google") otomatis disembunyikan dari antarmuka, bukan ditampilkan lalu gagal saat dipakai.
- FR-11.18: Konfigurasi kebijakan carry-over cuti (hangus/dibawa, batas maksimum hari, batas waktu pemakaian) — FR-4.8.
- FR-11.19: Konfigurasi pembagi hari kerja standar bulanan untuk perhitungan upah harian (uang pengganti cuti, prorata) — FR-10.8–10.9.

### 3.12 Notifikasi
- FR-12.1: Notifikasi ulang tahun — dikirim ke pegawai bersangkutan, rekan satu unit/atasan langsung, dan (opsional) HRD.
- FR-12.2: Notifikasi hari jadi kerja (work anniversary) dengan pola yang sama.
- FR-12.3: Notifikasi status pengajuan izin/cuti/lembur.
- FR-12.4: Notifikasi reminder dokumen mendekati kedaluwarsa.
- FR-12.5: Notifikasi reminder absen (belum check-in mendekati batas jam masuk).
- FR-12.6: Seluruh jenis notifikasi dapat diaktif/nonaktifkan per instalasi.
- FR-12.7: Notifikasi dikirim minimal lewat 2 channel bawaan — email & in-app (dalam aplikasi) — tanpa biaya integrasi tambahan.
- FR-12.8: Channel WhatsApp tersedia sebagai opsi tambahan (lihat FR-11.8) bagi klien yang bersedia menanggung biaya WhatsApp Business API.
- FR-12.9: Aplikasi mobile pegawai dapat di-install sebagai PWA (Progressive Web App) agar dapat menerima push notification dari browser tanpa memerlukan aplikasi native terpisah (lihat DESIGN.md untuk detail teknis).
- FR-12.10: Sistem menampilkan ajakan instalasi (install prompt) secara kontekstual — setelah pengguna login & sempat memakai aplikasi, bukan langsung di kunjungan pertama.
- FR-12.11: Di Android/Chrome, ajakan memicu dialog instalasi native browser. Di iOS/Safari yang tidak mendukung pemicu otomatis, sistem menampilkan instruksi manual bergambar (mis. "Tap ikon Share → Add to Home Screen") — dua jalur berbeda untuk dua platform, bukan satu tampilan yang dipaksakan sama.
- FR-12.12: Ajakan instalasi dapat ditutup (dismiss) oleh pengguna, dan tidak muncul lagi dalam periode tertentu setelah ditutup — bukan muncul berulang tiap sesi.

### 3.13 Laporan & Dashboard
- FR-13.1: Dashboard ringkasan (total pegawai, hadir hari ini, izin pending, keterlambatan) untuk Admin/HRD.
- FR-13.2: Laporan rekap kehadiran & izin per periode, dapat diekspor Excel/PDF.
- FR-13.3: Laporan payroll per periode untuk keperluan pembukuan.
- FR-13.4: Statistik kehadiran mingguan/bulanan dalam bentuk grafik.

### 3.14 Privasi & Persetujuan Data Pribadi
- FR-14.1: Sistem menyimpan teks kebijakan privasi yang dapat diperbarui HRD/Super Admin (mengikuti perubahan regulasi atau kebijakan internal klien).
- FR-14.2: Persetujuan pegawai (consent) dicatat dengan versi kebijakan yang disetujui & stempel waktu; bila kebijakan diperbarui, pegawai diminta menyetujui ulang.
- FR-14.3: Pegawai dapat mengajukan permintaan akses/koreksi/penghapusan data pribadinya (sesuai UU PDP), ditindaklanjuti HRD — kecuali data yang wajib dipertahankan untuk kepatuhan payroll/pajak.

### 3.15 Reimbursement & Klaim Biaya
- FR-15.1: Pegawai mengajukan klaim biaya (jenis, nominal, tanggal, keterangan) dengan bukti/struk terlampir.
- FR-15.2: Jenis klaim (kesehatan, transport, lainnya) beserta ada/tidaknya batas nominal maksimum dikonfigurasi HRD per instalasi.
- FR-15.3: Approval berjenjang mengikuti pola yang sama dengan Izin/Lembur (atasan → HRD).
- FR-15.4: Klaim yang disetujui masuk sebagai komponen tambahan pada payroll periode terkait (dibayarkan bersama gaji, atau dicatat terpisah sesuai kebijakan klien).

### 3.16 Perjalanan Dinas
- FR-16.1: Pegawai/HRD mengajukan perjalanan dinas (tujuan, tanggal berangkat & kembali, keperluan), dengan lampiran Surat Perintah Perjalanan Dinas (SPPD) opsional/wajib sesuai konfigurasi.
- FR-16.2: Approval oleh atasan langsung.
- FR-16.3: Perjalanan dinas yang disetujui otomatis menandai status Absensi pegawai pada rentang tanggal terkait menjadi "Dinas Luar" — validasi radius lokasi kerja (§3.3) tidak diterapkan pada status ini.
- FR-16.4: Uang saku/transport perjalanan dinas, bila ada, tercatat sebagai komponen tambahan pada payroll (mengikuti pola Reimbursement §3.15).

### 3.17 Struktur Organisasi & Direktori Pegawai
- FR-17.1: Sistem menampilkan struktur organisasi secara visual (diagram hierarki) berdasarkan data `units` dan `kepala_unit_id` yang sudah ada di Data Pegawai (§3.2).
- FR-17.2: Setiap node pada diagram dapat diklik untuk melihat daftar pegawai di unit tersebut.
- FR-17.3: Tersedia juga tampilan **Direktori Pegawai** berbentuk daftar yang dapat dicari (nama/unit/jabatan) sebagai pelengkap diagram — memudahkan pegawai menemukan rekan kerja tanpa perlu menelusuri diagram.
- FR-17.4: Detail yang ditampilkan di direktori **dibatasi sesuai role**: Pegawai/Atasan hanya melihat field non-sensitif (foto, nama, jabatan, unit, kontak kerja bila tersedia — bukan email pribadi/telepon pribadi); HRD/Admin melihat profil lengkap sesuai kewenangan Data Pegawai (§3.2). Ini bukan halaman terpisah dengan query berbeda, melainkan satu halaman yang menyaring kolom yang dikembalikan berdasarkan role pengguna yang login.

### 3.18 Audit Log (Jejak Perubahan)
- FR-18.1: Sistem mencatat jejak perubahan (siapa, apa, kapan, nilai sebelum/sesudah) untuk data sensitif berikut: Data Pegawai (khususnya gaji, status kepegawaian, unit, rekening bank), Absensi (koreksi manual oleh HRD terhadap catatan yang sudah tersimpan), Payroll (melanjutkan FR-10.7 dengan mekanisme yang sama), Pengaturan (perubahan kebijakan/tarif/kuota — §3.11), dan manajemen role/permission pengguna.
- FR-18.2: Setiap entri mencatat: pengguna yang melakukan aksi, model & record yang diubah, nilai sebelum & sesudah, stempel waktu, dan alamat IP.
- FR-18.3: Audit log bersifat **read-only** melalui aplikasi — tidak ada fitur edit/hapus entri log oleh siapa pun, termasuk Super Admin.
- FR-18.4: Akses melihat audit log dibatasi ke Super Admin (seluruh modul) dan HRD (terbatas pada modul dalam kewenangannya — Data Pegawai, Absensi, Payroll, Pengaturan); Atasan dan Pegawai tidak memiliki akses.
- FR-18.5: Retensi audit log default mengikuti kebijakan di POLICIES.md, dapat dikonfigurasi per instalasi.

### 3.19 Keamanan Aplikasi

Berlaku lintas modul — bukan fitur yang dipakai langsung oleh pengguna, tapi pagar yang menjaga seluruh modul lain.

- FR-19.1: Setiap akses ke record individual (bukan hanya daftar/menu) divalidasi kepemilikan/lingkupnya di server melalui otorisasi berbasis resource (mis. Laravel Policy per model) — Pegawai hanya dapat mengakses record miliknya sendiri, Atasan hanya record dalam unit kerjanya, terlepas dari apa yang ditampilkan di UI. Mencegah akses lewat manipulasi ID/URL langsung (insecure direct object reference).
- FR-19.2: Berkas yang diunggah (foto selfie, dokumen kepegawaian, bukti reimbursement, lampiran SPPD/SPL) divalidasi tipe file (whitelist, bukan hanya ekstensi nama file — dicek MIME/konten) dan ukuran maksimum sebelum disimpan. Berkas disimpan di lokasi yang tidak dapat dieksekusi sebagai skrip dan tidak dapat diakses langsung tanpa otorisasi (FR sudah ada di NFR Keamanan).
- FR-19.3: Pengguna dapat melihat daftar sesi/perangkat aktif dan melakukan "Keluar dari semua perangkat" lewat Akun Saya (FR-1.9) — relevan bila perangkat hilang/dicuri.
- FR-19.4: Semua batasan field yang tampak di UI (mis. field yang tidak boleh diedit Pegawai, FR-2.9–2.11) **wajib** ditegakkan ulang lewat validasi di server (Form Request/Policy) — larangan di UI adalah kenyamanan pengguna, bukan batas keamanan.
- FR-19.5: Saat login via Google OAuth, sistem memverifikasi klaim `email_verified` dari Google sebelum mencocokkan email ke data pegawai terdaftar — mencegah celah bila ada akun dengan email belum terverifikasi.
- FR-19.6: Aplikasi mengirim header keamanan standar (Content-Security-Policy, X-Frame-Options, HSTS di produksi) pada setiap respons.
- FR-19.7: Dependency (Composer & npm) diperiksa kerentanannya secara berkala (`composer audit`, `npm audit`) sebagai bagian dari proses rilis (DESIGN.md §7), bukan hanya saat setup awal.
- FR-19.8: Backup database & storage (DESIGN.md §8) disimpan terenkripsi saat disimpan (encryption at rest), tidak hanya dilindungi lewat pembatasan akses lokasi penyimpanan.

---

## 4. Kebutuhan Non-Fungsional

| Kategori | Kebutuhan |
|---|---|
| Performa | Query kehadiran & izin ber-index (`employee_id`+`tanggal`); mendukung 100–500 pegawai tanpa degradasi pada jam sibuk (mis. 07:30–08:00 saat absen massal) |
| Skalabilitas | Proses berat (resize foto, kirim notifikasi, generate laporan/slip gaji) dijalankan lewat queue, bukan blocking request |
| Keamanan | HTTPS wajib di produksi; password di-hash; foto selfie & dokumen tidak dapat diakses publik tanpa otorisasi; audit log terpusat mencakup Payroll, Data Pegawai, Absensi, Pengaturan, dan manajemen role/permission (§3.18) |
| Kepatuhan | Pemrosesan data pribadi (foto selfie, lokasi GPS, data keluarga) mengikuti prinsip UU PDP (No. 27/2022) — persetujuan eksplisit, tujuan penggunaan jelas, hak akses/koreksi/penghapusan pegawai (lihat §3.14) |
| Ketersediaan data | Foto selfie disimpan di disk/storage (bukan BLOB database) dengan kebijakan retensi |
| Usability | Tampilan mobile dioptimalkan untuk operasi satu tangan (bottom navigation, maksimal 5 item utama). Setiap daftar/riwayat yang bisa kosong (notifikasi, riwayat, slip gaji, hasil pencarian) wajib punya tampilan *empty state* yang jelas (ikon + penjelasan singkat) — bukan dibiarkan kosong tanpa keterangan |
| Portabilitas | Instalasi harus dapat di-deploy ulang dengan cepat ke lingkungan klien baru (lihat SETUP.md) |
| Kompatibilitas browser | Mendukung browser modern dengan Geolocation API & MediaDevices API (Chrome, Safari, Edge versi terbaru) |

---

## 5. Matriks Role vs Modul

| Modul | Pegawai | Atasan | HRD/Admin | Super Admin |
|---|:---:|:---:|:---:|:---:|
| Data Pegawai (diri sendiri) | Edit terbatas (§3.2 FR-2.9–2.11) | Lihat | Kelola | Kelola |
| Data Pegawai (semua) | – | Lihat (unitnya) | Kelola | Kelola |
| Absensi | Input (diri) | Lihat (unitnya) | Kelola semua | Kelola semua |
| Izin & Cuti | Ajukan | Approve (unitnya) | Approve akhir | Kelola semua |
| Lembur | Ajukan | Approve (unitnya) | Approve akhir | Kelola semua |
| Kepangkatan | Lihat (diri) | Lihat (unitnya) | Kelola | Kelola |
| Kinerja | Isi realisasi | Nilai (unitnya) | Kelola & lihat semua | Kelola semua |
| Dokumen | Kelola (diri) | – | Kelola semua | Kelola semua |
| Tunjangan | Lihat (diri) | – | Kelola | Kelola |
| Reimbursement/Klaim | Ajukan | Approve (unitnya) | Approve akhir | Kelola semua |
| Perjalanan Dinas | Ajukan | Approve (unitnya) | Approve akhir | Kelola semua |
| Payroll | Lihat slip (diri) | – | Siapkan (draft) | Approve final |
| Struktur Organisasi | Lihat | Lihat | Kelola | Kelola |
| Pengaturan | – | – | Sebagian | Penuh |
| Audit Log | – | – | Terbatas (§3.18) | Penuh |
| Laporan | – | Laporan unitnya | Semua laporan | Semua laporan |

---

## 6. Referensi Terkait
- Arsitektur teknis & skema database lengkap: lihat `DESIGN.md`
- Kebijakan bisnis default (dapat dikustomisasi per klien): lihat `POLICIES.md`
- Panduan instalasi & konfigurasi environment: lihat `SETUP.md`
