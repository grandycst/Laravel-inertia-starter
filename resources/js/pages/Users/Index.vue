<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import type { PropType } from 'vue';
import userRoutes from '@/routes/users';

defineProps({
    users: {
        type: Array as PropType<Array<{ id: number; name: string; email: string; roles: string; created_at: string }>>,
        required: true,
    },
});
</script>

<template>
    <Head title="User Management" />

    <div class="space-y-6 p-6">
        <div class="flex flex-col gap-4 rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-950/5 dark:bg-slate-950 dark:shadow-none">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm uppercase tracking-[0.32em] text-slate-500 dark:text-slate-400">User Management</p>
                    <h1 class="text-2xl font-semibold text-slate-950 dark:text-white">Kelola Pengguna</h1>
                </div>
                <Link :href="userRoutes.create()" class="inline-flex items-center justify-center rounded-full bg-emerald-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-600">
                    Tambah User
                </Link>
            </div>
            <p class="text-sm leading-6 text-slate-600 dark:text-slate-400">Lihat daftar semua pengguna yang terdaftar, email, dan peran mereka. Gunakan halaman ini untuk memantau akun internal HRD dan admin.</p>
        </div>

        <div class="overflow-hidden rounded-[2rem] border border-slate-200/80 bg-white shadow-sm dark:border-slate-700/70 dark:bg-slate-950">
            <table class="min-w-full divide-y divide-slate-200 text-left dark:divide-slate-700">
                <thead class="bg-slate-50 text-slate-600 dark:bg-slate-900 dark:text-slate-300">
                    <tr>
                        <th class="px-6 py-4 text-xs font-semibold uppercase tracking-[0.24em]">Name</th>
                        <th class="px-6 py-4 text-xs font-semibold uppercase tracking-[0.24em]">Email</th>
                        <th class="px-6 py-4 text-xs font-semibold uppercase tracking-[0.24em]">Role</th>
                        <th class="px-6 py-4 text-xs font-semibold uppercase tracking-[0.24em]">Created</th>
                        <th class="px-6 py-4 text-xs font-semibold uppercase tracking-[0.24em]">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-950">
                    <tr v-for="user in users" :key="user.id">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-slate-100">{{ user.name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">{{ user.email }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">{{ user.roles || 'User' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">{{ user.created_at }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm dark:text-slate-300">
                            <Link :href="userRoutes.edit(user.id)" class="rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-white transition hover:bg-slate-800">Edit</Link>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="border-t border-slate-200/80 bg-slate-50 px-6 py-4 text-sm text-slate-500 dark:border-slate-700/70 dark:bg-slate-900 dark:text-slate-400">
                Total pengguna: {{ users.length }}
            </div>
        </div>
    </div>
</template>
