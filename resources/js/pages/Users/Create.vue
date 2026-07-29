<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import users from '@/routes/users';
</script>

<template>
    <Head title="Tambah User" />

    <div class="space-y-6 p-6">
        <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-950/5 dark:bg-slate-950">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm uppercase tracking-[0.32em] text-slate-500 dark:text-slate-400">Tambah User</p>
                    <h1 class="text-2xl font-semibold text-slate-950 dark:text-white">Tambah pengguna baru</h1>
                </div>
                <Link :href="users.index()" class="rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Kembali</Link>
            </div>

            <Form v-bind="users.store.form()" v-slot="{ errors, processing }" class="mt-8 space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Nama</label>
                    <input type="text" name="name" class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-white" required />
                    <InputError :message="errors.name" />
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Email</label>
                    <input type="email" name="email" class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-white" required />
                    <InputError :message="errors.email" />
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Password</label>
                    <input type="password" name="password" class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-white" required />
                    <InputError :message="errors.password" />
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-white" required />
                    <InputError :message="errors.password_confirmation" />
                </div>
                <button type="submit" :disabled="processing" class="inline-flex items-center justify-center rounded-full bg-emerald-500 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-500/20 transition hover:bg-emerald-600 disabled:cursor-not-allowed disabled:opacity-50">
                    <span v-if="processing" class="mr-2">Loading...</span>
                    Simpan
                </button>
            </Form>
        </div>
    </div>
</template>
