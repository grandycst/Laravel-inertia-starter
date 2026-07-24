<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Role & permission dasar sesuai matriks role SRS §5. Permission di sini menentukan modul apa
 * yang terlihat di navigasi (level fitur) — batasan "hanya data unitnya" / "hanya milik sendiri"
 * ditegakkan lewat Policy per model (docs/DESIGN.md §11.1), bukan lewat permission yang berbeda.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Data Pegawai
            'pegawai.view-own', 'pegawai.view-unit', 'pegawai.manage',
            // Absensi
            'absensi.input-own', 'absensi.view-unit', 'absensi.manage',
            // Izin & Cuti
            'izin.request', 'izin.approve',
            // Lembur
            'lembur.request', 'lembur.approve',
            // Reimbursement
            'reimbursement.request', 'reimbursement.approve',
            // Perjalanan Dinas
            'dinas.request', 'dinas.approve',
            // Kepangkatan
            'kepangkatan.view-own', 'kepangkatan.view-unit', 'kepangkatan.manage',
            // Kinerja
            'kinerja.self-assess', 'kinerja.review-unit', 'kinerja.manage',
            // Dokumen
            'dokumen.manage-own', 'dokumen.manage-all',
            // Tunjangan
            'tunjangan.view-own', 'tunjangan.manage',
            // Payroll
            'payroll.view-own-slip', 'payroll.prepare', 'payroll.approve-final',
            // Struktur Organisasi & Direktori
            'struktur-organisasi.view', 'struktur-organisasi.manage',
            // Pengaturan
            'pengaturan.manage-partial', 'pengaturan.manage-full',
            // Audit Log
            'audit-log.view-limited', 'audit-log.view-full',
            // Laporan
            'laporan.view-unit', 'laporan.view-all',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        // Spatie meng-cache daftar permission; tanpa ini, syncPermissions() di bawah bisa gagal
        // "not found" untuk permission yang baru saja dibuat pada request/proses yang sama.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $pegawai = Role::findOrCreate('Pegawai');
        $pegawai->syncPermissions([
            'pegawai.view-own',
            'absensi.input-own',
            'izin.request',
            'lembur.request',
            'reimbursement.request',
            'dinas.request',
            'kepangkatan.view-own',
            'kinerja.self-assess',
            'dokumen.manage-own',
            'tunjangan.view-own',
            'payroll.view-own-slip',
            'struktur-organisasi.view',
        ]);

        $atasan = Role::findOrCreate('Atasan');
        $atasan->syncPermissions([
            ...$pegawai->permissions->pluck('name')->all(),
            'pegawai.view-unit',
            'absensi.view-unit',
            'izin.approve',
            'lembur.approve',
            'reimbursement.approve',
            'dinas.approve',
            'kepangkatan.view-unit',
            'kinerja.review-unit',
            'laporan.view-unit',
        ]);

        $hrd = Role::findOrCreate('HRD');
        $hrd->syncPermissions([
            'pegawai.view-unit', 'pegawai.manage',
            'absensi.view-unit', 'absensi.manage',
            'izin.approve',
            'lembur.approve',
            'reimbursement.approve',
            'dinas.approve',
            'kepangkatan.view-unit', 'kepangkatan.manage',
            'kinerja.review-unit', 'kinerja.manage',
            'dokumen.manage-all',
            'tunjangan.manage',
            'payroll.view-own-slip', 'payroll.prepare',
            'struktur-organisasi.view', 'struktur-organisasi.manage',
            'pengaturan.manage-partial',
            'audit-log.view-limited',
            'laporan.view-unit', 'laporan.view-all',
        ]);

        // Super Admin: seluruh permission, termasuk Pengaturan penuh, approve final payroll,
        // dan audit log penuh — satu-satunya role dengan akses tak terbatas (SRS §2.1).
        $superAdmin = Role::findOrCreate('Super Admin');
        $superAdmin->syncPermissions(Permission::all());
    }
}
