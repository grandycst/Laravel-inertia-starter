<?php

use Illuminate\Support\Facades\Route;

// Tidak ada landing page publik — SIMPEG langsung ke Login (SRS §3.1), tidak seperti
// halaman "Welcome" bawaan starter kit.
Route::redirect('/', '/login')->name('home');

// 'verified' sengaja tidak dipakai — fitur email verification dimatikan (lihat config/fortify.php),
// karena akun dibuat langsung oleh HRD dengan email undangan (SRS FR-1.4), bukan alur verify-email publik.
Route::middleware(['auth'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
