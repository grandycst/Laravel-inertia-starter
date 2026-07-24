# POLICIES — Kebijakan Bisnis Default
## SIMPEG (Sistem Informasi Manajemen Kepegawaian)

Status: Draft rancangan awal
Terakhir diperbarui: 2026-07-23

> Semua nilai di dokumen ini adalah **default pabrik (factory default)**. Karena SIMPEG dijual sebagai instalasi terpisah per klien (lihat DESIGN.md §1 & §7), nilai-nilai ini **wajib dapat diubah tiap klien** melalui modul Pengaturan (SRS §3.11) tanpa mengubah kode. Dokumen ini juga berfungsi sebagai checklist pertanyaan yang perlu ditanyakan ke tiap klien baru saat onboarding.

---

## 1. Kebijakan Akun & Keamanan

- Tidak ada pendaftaran mandiri (self-registration). Akun hanya dibuat oleh HRD/Admin.
- Login mendukung email/password dan Google OAuth — keduanya hanya berlaku untuk email yang sudah terdaftar sebagai pegawai.
- Password minimum 8 karakter, kombinasi huruf & angka (mengikuti default Laravel Fortify, dapat diperketat).
- Percobaan login gagal dibatasi default **5 kali**, lalu akun/IP dikunci sementara selama **15 menit** sebelum bisa mencoba lagi (lockout progresif) — mitigasi serangan brute-force (SRS FR-1.8).
- 2FA wajib untuk role HRD/Admin dan Super Admin; opsional untuk Pegawai dan Atasan.
- Kredensial integrasi (Google OAuth, WhatsApp API) hanya dapat diubah oleh **Super Admin** — bukan HRD, karena kesalahan/penyalahgunaan di sini berdampak ke seluruh sistem login, bukan cuma satu modul.
- Kredensial yang tersimpan tidak pernah ditampilkan penuh lagi setelah disimpan (masked, opsi "Ganti" saja) — mencegah kebocoran lewat screenshot/shoulder-surfing di layar admin.
- Perubahan kredensial integrasi masuk cakupan Audit Log (§12) — siapa mengganti, kapan, meski nilai lama/baru tidak ditampilkan dalam bentuk plain di log (cukup dicatat "client_secret diperbarui").
- Sesi login otomatis berakhir setelah periode tidak aktif (default 2 jam) — dapat dikonfigurasi.
- Foto selfie & dokumen kepegawaian tidak dapat diakses melalui URL publik tanpa otorisasi (disajikan lewat route terautentikasi, bukan disk publik langsung).
- Pegawai boleh mengubah sendiri tanpa approval: nomor HP, alamat domisili, foto profil, kontak darurat.
- Perubahan nomor rekening bank, nama, dan NIK oleh pegawai **wajib diverifikasi HRD** sebelum berlaku (bukan langsung aktif) — khusus rekening bank, ini kebijakan tetap yang tidak boleh dimatikan, karena mencegah pengalihan tujuan transfer gaji bila akun pegawai diretas.
- Field lain (NIP, unit kerja, jabatan, status kepegawaian, gaji) sama sekali tidak tersedia untuk diedit pegawai — murni kewenangan HRD.

## 2. Kebijakan Absensi

- Radius toleransi lokasi kerja: default **100 meter** dari titik `work_locations` terdaftar — dapat diubah per lokasi.
- Presensi di luar radius **tidak otomatis ditolak**, tetapi ditandai status "Di Luar Radius" untuk ditinjau HRD.
- Batas keterlambatan: default **15 menit** setelah jam masuk terjadwal sebelum status berubah menjadi "Telat".
- Durasi istirahat default **60 menit** (mis. jam makan siang) dikurangkan otomatis dari jam kerja efektif & perhitungan lembur — dapat diubah per jadwal/shift (POLICIES §10).
- Tidak ada catatan absensi pada hari tersebut (dan bukan hari libur/cuti/izin) → status otomatis "Alpha" pada akhir hari (via scheduled job).
- Foto selfie disimpan dengan retensi default **12 bulan**, kemudian diarsipkan/dihapus otomatis — dapat dikonfigurasi sesuai kebutuhan kepatuhan data klien.
- **Keterbatasan yang diakui secara sadar:** koordinat GPS dapat dimanipulasi dengan aplikasi fake-location. Sistem tidak mengklaim mendeteksi seluruh bentuk spoofing — validasi radius + selfie berfungsi sebagai lapisan pencegahan wajar, bukan jaminan mutlak. HRD tetap berperan meninjau pola kehadiran yang mencurigakan.
- **Izin browser ditolak/tidak didukung:** pegawai diarahkan untuk mengaktifkan izin lewat pengaturan browser (SRS FR-3.13). Untuk kasus perangkat benar-benar tidak mendukung, HRD dapat input absensi manual dengan keterangan alasan (FR-3.14) — ini jalur pengecualian yang harus dicatat, bukan default yang dibiarkan sering dipakai (kalau sering terjadi di satu pegawai/unit, itu sinyal untuk ditinjau, bukan dibiarkan).

## 3. Kebijakan Izin & Cuti

- Jenis cuti default: Cuti Tahunan, Sakit, Izin Pribadi, Cuti Melahirkan/Mendampingi — daftar ini dapat ditambah/diubah oleh tiap klien.
- Kuota cuti tahunan default: **12 hari/tahun** untuk Cuti Tahunan (dapat dikonfigurasi sesuai kebijakan/regulasi ketenagakerjaan lokal klien).
- Pengajuan cuti tahunan idealnya diajukan H-3 sebelum tanggal mulai (soft policy — sistem menampilkan peringatan, bukan blokir keras), kecuali jenis Sakit/Darurat.
- Approval berjenjang default: Atasan langsung → HRD (dua tingkat). Jumlah tingkatan dapat ditambah untuk struktur organisasi yang lebih besar.
- Cuti yang disetujui otomatis mengubah status Absensi pada rentang tanggal terkait (lihat DESIGN.md §4.1) — tidak memotong komponen kehadiran pada Tunjangan Kinerja.
- **Hangus vs carry-over**: default **hangus** (use-it-or-lose-it) untuk Cuti Tahunan di akhir tahun kalender — kebijakan yang lebih umum berlaku. Klien yang mengizinkan carry-over dapat mengaktifkannya per jenis cuti dengan batas maksimum default **6 hari**, berlaku sampai **31 Maret** tahun berikutnya (lewat tanggal itu, sisa carry-over ikut hangus). Semua angka ini dikonfigurasi bebas per instalasi.
- Alpha (tanpa keterangan) memberi potongan Tunjangan Kinerja lebih besar dibanding Izin/Sakit yang disertai lampiran sah.

## 4. Kebijakan Lembur

> **Keputusan final:** delegasi approval otomatis saat atasan cuti/dinas **sengaja tidak dibangun**. Approval izin/lembur/reimbursement tetap harus melalui `atasan_id` pengaju walau atasan sedang tidak di tempat — HRD dapat menjadi jalur eskalasi manual bila approval macet lebih dari beberapa hari, tapi ini tindakan manual, bukan mekanisme otomatis di sistem. Keputusan ini final, tidak perlu diangkat ulang di review berikutnya kecuali klien secara eksplisit memintanya.

- Lembur wajib disertai uraian tugas; lampiran SPL (Surat Perintah Lembur) wajib/opsional dapat dikonfigurasi per klien.
- Default pola pengajuan: **post-submission** (lembur dilakukan, lalu diajukan dengan bukti dalam 1x24 jam) — klien dapat beralih ke pre-approval bila kebijakan internal mengharuskan.
- Jam lembur yang tumpang tindih dengan jadwal kerja normal ditandai untuk ditinjau ulang, tidak otomatis ditolak.
- Kompensasi lembur berupa uang lembur atau libur pengganti — dipilih per pengajuan, kebijakan rasio/nominal ditentukan klien di Pengaturan.
- **Ambang minimum lembur**: default **60 menit** — lembur di bawah durasi ini tidak dapat diajukan sama sekali (SRS FR-5.9). Berlaku sama untuk metode penjadwalan Tetap maupun Shift; nilainya dapat diturunkan/dinaikkan per klien sesuai kebijakan internal masing-masing perusahaan.
- **Tarif lembur bertingkat** (default awal, mengikuti pola umum ketenagakerjaan Indonesia — dapat diubah bebas per klien di Pengaturan):

| Jenis Hari | Jam ke-1 | Jam ke-2 dst |
|---|---|---|
| Hari kerja biasa | ×1.5 | ×2.0 |
| Hari libur mingguan | ×2.0 | ×3.0 |
| Hari libur nasional | ×2.0 | ×3.0 (jam ke-8: ×4.0) |

- **Upah per jam** dihitung dari gaji pokok ÷ **173** jam (pembagi jam kerja standar bulanan default) — pembagi ini juga dapat dikonfigurasi per klien.
- Jenis hari (kerja/libur mingguan/libur nasional) ditentukan otomatis dari tanggal lembur & tabel `holidays` — HRD tidak perlu menandai manual tiap pengajuan.

## 5. Kebijakan Tunjangan

- Tunjangan Kinerja dihitung otomatis dari rekap kehadiran bulan berjalan; formula potongan default:
  - Alpha: potong 5% dari nominal dasar per hari
  - Terlambat lebih dari 3 kali dalam sebulan: potong tambahan 2%
  - (Nilai-nilai ini contoh awal, wajib disesuaikan per klien sebelum go-live)
- Hasil perhitungan berstatus **draft** dan wajib direview HRD sebelum difinalisasi — tidak ada finalisasi otomatis tanpa peninjauan manusia.
- Tunjangan Keluarga dihitung dari status kawin & jumlah tanggungan yang tercatat di Data Pegawai; HRD bertanggung jawab memastikan data ini akurat & termutakhir.

## 6. Kebijakan Payroll

- Siklus payroll: bulanan, diproses pada tanggal yang dikonfigurasi tiap klien (umumnya akhir bulan berjalan atau awal bulan berikutnya).
- Approval berlapis wajib: HRD siapkan (draft) → Bendahara/Keuangan verifikasi & finalisasi. Tidak ada payroll yang berstatus final tanpa dua pihak berbeda yang menyetujui.
- Sistem **tidak pernah** mengeksekusi transfer bank secara otomatis — hanya menghasilkan file transfer untuk diunggah manual oleh bendahara ke sistem perbankan resmi klien. Ini kebijakan tetap (bukan opsi yang dapat dimatikan), demi kontrol keuangan dan mengurangi permukaan risiko keamanan.
- Setiap perubahan pada `payroll_items` berstatus final wajib tercatat di audit log (siapa, kapan, apa yang diubah).
- Aturan pajak (`tax_rules`) harus ditinjau ulang setiap awal tahun pajak berjalan mengikuti regulasi yang berlaku saat itu.
- **Gaji prorata pegawai baru**: bulan pertama dihitung proporsional dari `tanggal_bergabung`, bukan gaji penuh sebulan — pembagi hari kerja default **21 hari kerja/bulan** (dapat dikonfigurasi).
- **Uang pengganti cuti saat resign**: sisa kuota Cuti Tahunan tahun berjalan (setelah dikurangi carry-over yang sudah hangus) dikonversi jadi uang, dihitung dari upah harian × sisa hari, masuk ke payroll periode terakhir pegawai tersebut. Ini kebijakan tetap (bukan opsional) karena umum diwajibkan regulasi ketenagakerjaan — klien tetap boleh menyesuaikan formula upah hariannya di Pengaturan.

## 7. Kebijakan Notifikasi

- Channel default: **email & in-app**, keduanya tanpa biaya integrasi tambahan (termasuk dalam produk).
- Channel **WhatsApp bersifat opsional** — hanya aktif bila klien menyediakan kredensial WhatsApp Business API sendiri dan bersedia menanggung biaya penggunaannya (bukan ditanggung/disediakan otomatis oleh produk). Ini perlu dikomunikasikan jelas ke klien saat penawaran, supaya tidak jadi ekspektasi keliru bahwa WhatsApp "gratis" seperti email.
- Notifikasi ulang tahun & hari jadi kerja default **aktif**, dapat dimatikan per klien atau per pegawai (opt-out individu).
- Notifikasi reminder dokumen kedaluwarsa dikirim default **30 hari** sebelum tanggal kedaluwarsa, diulang tiap 7 hari hingga dokumen diperbarui.
- Notifikasi reminder absen dikirim bila pegawai belum check-in **30 menit** setelah jam masuk terjadwal.

## 8. Kebijakan Offboarding (Resign/Pemutusan Hubungan Kerja)

- Data pegawai yang keluar/resign **tidak dihapus**, melainkan diberi status "Nonaktif" dengan tanggal efektif & alasan, lalu diarsipkan (mempertahankan riwayat untuk keperluan payroll/kepatuhan historis).
- Akun login otomatis dinonaktifkan tepat pada tanggal efektif resign — bukan langsung saat status diinput HRD, karena tanggal input dan tanggal efektif bisa berbeda (mis. resign diproses lebih awal untuk masa notice period).
- Data yang masih tercatat berjalan (izin pending, lembur pending) pada pegawai yang resign wajib diselesaikan (approve/reject) sebelum status nonaktif final — tidak dibiarkan menggantung.
- Payroll periode terakhir pegawai resign tetap diproses normal mengikuti alur approval berlapis (§6), termasuk komponen pro-rata bila resign di tengah periode.
- Notice period (masa pemberitahuan sebelum resign efektif) default **30 hari**, dapat dikonfigurasi per klien mengikuti kontrak kerja/regulasi ketenagakerjaan setempat.

## 9. Kebijakan Data & Privasi (Selaras UU PDP)

- Pegawai wajib menyetujui kebijakan privasi (persetujuan pemrosesan data pribadi, termasuk foto selfie & lokasi GPS) sebelum dapat menggunakan fitur Absensi (SRS FR-1.7, FR-14.x).
- Data pribadi hanya digunakan untuk tujuan yang dinyatakan dalam kebijakan privasi (administrasi kepegawaian, absensi, payroll) — tidak untuk tujuan lain tanpa persetujuan tambahan.
- Pegawai berhak mengajukan permintaan akses, koreksi, atau penghapusan data pribadinya, ditindaklanjuti HRD dalam batas waktu yang wajar — kecuali data yang wajib dipertahankan untuk kepatuhan payroll/pajak/ketenagakerjaan.
- Akses data pribadi (gaji, dokumen, penilaian kinerja) dibatasi ketat sesuai matriks role di SRS §5 — Atasan tidak dapat melihat gaji bawahannya kecuali diberi permission eksplisit.
- Setiap instalasi bertanggung jawab menetapkan kebijakan retensi & kepatuhan datanya sendiri sesuai regulasi perlindungan data/ketenagakerjaan yang berlaku di yurisdiksi klien tersebut — dokumen ini hanya menyediakan default awal, **bukan nasihat hukum**. Klien dengan kebutuhan kepatuhan ketat disarankan konsultasi dengan penasihat hukum sebelum go-live.

## 10. Kebijakan Masa Probation & Sistem Shift

**Masa Probation:**
- Durasi default masa percobaan pegawai baru: **3 bulan** sejak `tanggal_bergabung`, dapat diperpanjang atau dipersingkat per klien.
- Selama probation, kuota Cuti Tahunan default **tidak berlaku** (0 hari) — pegawai baru mulai mendapat kuota penuh sejak dikonfirmasi. Izin Sakit tetap dapat diajukan.
- Tunjangan Kinerja & Tunjangan Keluarga dapat diberlakukan penuh atau prorata selama probation — ditentukan HRD per klien, bukan default tunggal.
- HRD & atasan menerima reminder H-14 sebelum masa probation berakhir (SRS FR-2.13) — tidak ada otomatisasi keputusan konfirmasi, keputusan tetap manual oleh HRD.

**Sistem Shift:**
- Jenis shift default yang disarankan sebagai titik awal: **Pagi** (07:00–15:00), **Siang** (15:00–23:00), **Malam** (23:00–07:00, lintas hari) — nama, jam, dan jumlah jenis shift sepenuhnya dikonfigurasi per klien.
- Pengajuan tukar shift (shift swap) wajib disetujui atasan sebelum roster berubah; default **tidak dapat diajukan mendadak** (minimal H-1) kecuali kondisi darurat yang dicatat HRD secara manual.
- Roster shift idealnya dipublikasikan/terlihat oleh pegawai minimal **H-7** sebelum berlaku, agar ada waktu bagi pegawai mengajukan tukar shift bila diperlukan — ini rekomendasi praktik, bukan validasi keras di sistem.

## 11. Kebijakan Reimbursement & Perjalanan Dinas

- Jenis klaim reimbursement default (kesehatan, transport) wajib disertai bukti/struk; batas nominal maksimum per jenis klaim ditentukan tiap klien di Pengaturan (tidak ada batas baku dari produk).
- Perjalanan dinas yang disetujui membebaskan pegawai dari validasi radius lokasi kerja pada Absensi selama rentang tanggal dinas (status "Dinas Luar") — HRD tetap dapat meninjau kewajaran melalui laporan.
- Uang saku perjalanan dinas, bila ada, mengikuti alur approval & pencairan yang sama dengan Reimbursement — masuk payroll periode terkait.

## 12. Kebijakan Audit Log

- Cakupan wajib: Data Pegawai, Absensi, Payroll, Pengaturan, dan manajemen role/permission (SRS §3.18) — modul lain dapat ditambahkan kemudian tanpa mengubah mekanisme dasarnya (§ tabel `audit_logs` di DESIGN.md bersifat generik/polymorphic).
- Retensi default **2 tahun**, dapat diperpanjang per klien — beberapa yurisdiksi ketenagakerjaan mensyaratkan jejak data payroll disimpan lebih lama untuk keperluan sengketa/audit pajak; instalasi dengan kebutuhan itu sebaiknya menyesuaikan retensi ke atas, bukan ke bawah.
- Akses lihat audit log dibatasi Super Admin (semua modul) dan HRD (modul dalam kewenangannya) — Atasan dan Pegawai tidak memiliki akses sama sekali, termasuk untuk melihat riwayat perubahan datanya sendiri (berbeda dengan hak akses/koreksi data pribadi di §9, yang dilayani lewat permintaan ke HRD, bukan akses langsung ke audit log mentah).
- Audit log tidak pernah dihapus manual lewat aplikasi, termasuk oleh Super Admin — penghapusan (bila memang perlu, mis. sudah lewat masa retensi) hanya lewat proses database terjadwal di luar aplikasi, bukan tombol UI.

## 13. Kebijakan Keamanan Aplikasi

- **Batas ukuran & tipe file unggahan** (default, dapat disesuaikan per klien): foto selfie maks **5 MB** (jpg/png/webp), dokumen kepegawaian maks **10 MB** (pdf/jpg/png), bukti reimbursement & lampiran SPPD/SPL maks **5 MB** (pdf/jpg/png).
- **Sesi ganda**: diizinkan secara default (wajar pegawai pakai HP & kadang browser desktop bersamaan) — bukan dibatasi satu sesi aktif. Timeout tidak aktif tetap mengikuti §1 (default 2 jam). Pengguna dapat mencabut semua sesi aktif kapan saja lewat Akun Saya, terutama saat perangkat hilang/dicuri.
- **Audit dependency**: `composer audit`/`npm audit` wajib dijalankan di tiap rilis (DESIGN.md §7), dan minimal **sekali per triwulan** meski tidak ada rilis baru terjadwal — kerentanan baru pada dependency lama bisa muncul kapan saja tanpa perubahan kode di sisi kita.
- **Enkripsi backup**: wajib, bukan opsional — backup yang tidak terenkripsi tidak boleh dipindahkan ke penyimpanan offsite/cloud.
- **Header keamanan & mode debug**: `APP_DEBUG` wajib `false` di semua instalasi produksi (lihat SETUP.md checklist) — pesan error rinci/stack trace tidak boleh terlihat pengguna akhir maupun bisa diakses publik.
- Kebijakan ini berlaku sebagai baseline minimum tiap instalasi; klien dengan kebutuhan kepatuhan lebih ketat (mis. sektor keuangan/kesehatan) dapat memperketat nilai-nilai di atas, tidak dianjurkan melonggarkannya di bawah baseline ini.

---

## Catatan Implementasi

Nilai-nilai numerik (radius meter, persentase potongan, kuota cuti, batas hari reminder) sebaiknya disimpan di tabel `settings` (lihat DESIGN.md §3.6) sebagai key-value, bukan konstanta di kode, sehingga tim onboarding dapat menyesuaikannya per klien tanpa deploy ulang kode.
