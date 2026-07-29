<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Landing page publik untuk starter project. Pengguna belum login akan melihat halaman ini.
Route::inertia('/', 'Welcome')->name('home');

// 'verified' sengaja tidak dipakai — fitur email verification dimatikan (lihat config/fortify.php),
// karena akun dibuat langsung oleh HRD dengan email undangan (SRS FR-1.4), bukan alur verify-email publik.
Route::middleware(['auth'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::controller(App\Http\Controllers\UserController::class)->group(function () {
        Route::get('users', 'index')->name('users.index');
        Route::get('users/create', 'create')->name('users.create');
        Route::post('users', 'store')->name('users.store');
        Route::get('users/{user}/edit', 'edit')->name('users.edit');
        Route::put('users/{user}', 'update')->name('users.update');
    });
});

require __DIR__.'/settings.php';
