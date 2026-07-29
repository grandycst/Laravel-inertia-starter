<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Home, LayoutDashboard, Settings, LogIn, MessageSquare } from '@lucide/vue';

const menuItems = [
    { title: 'Login', href: '/login' },
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Settings', href: '/settings/profile' },
];
</script>

<template>
    <Head title="Welcome" />

    <div class="min-h-svh bg-background px-4 py-8 pb-28 text-foreground sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl overflow-hidden rounded-[2.5rem] border border-border bg-card shadow-[0_40px_120px_-40px_rgba(15,23,42,0.15)] backdrop-blur-xl">
            <div class="grid gap-10 lg:grid-cols-[1.2fr_0.9fr] p-8 lg:p-10">
                <section class="space-y-8">
                    <div class="max-w-xl space-y-6">
                        <div class="inline-flex items-center gap-3 rounded-full bg-primary/15 px-4 py-2 text-sm font-semibold text-primary">
                            <span>Starter</span>
                            <span class="rounded-full bg-primary/25 px-2 py-0.5 text-[11px] font-semibold text-primary">Full-stack</span>
                        </div>
                        <div>
                            <h1 class="text-4xl font-semibold tracking-tight text-foreground sm:text-5xl">Laravel + Inertia Starter</h1>
                            <p class="mt-4 text-base leading-8 text-muted-foreground">
                                Aplikasi starter lengkap dengan otentikasi, dashboard, serta halaman pengaturan yang siap digunakan untuk HRIS, admin panel, atau aplikasi internal.
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <Link
                            v-for="item in menuItems"
                            :key="item.title"
                            :href="item.href"
                            class="inline-flex items-center justify-center rounded-3xl bg-secondary px-5 py-4 text-sm font-semibold text-secondary-foreground shadow-lg shadow-black/5 transition hover:-translate-y-0.5 hover:bg-accent hover:text-accent-foreground"
                        >
                            {{ item.title }}
                        </Link>
                    </div>

                    <div class="rounded-[2rem] border border-border bg-muted/40 p-6 shadow-lg shadow-black/5">
                        <div class="space-y-4">
                            <div class="space-y-3">
                                <p class="text-sm uppercase tracking-[0.35em] text-muted-foreground">Starter Features</p>
                                <h2 class="text-2xl font-semibold text-foreground">Dua mode tampilan</h2>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="rounded-3xl bg-background p-5 shadow-inner shadow-black/5 ring-1 ring-border">
                                    <p class="text-sm font-semibold text-foreground">Desktop</p>
                                    <p class="mt-2 text-sm leading-6 text-muted-foreground">Layout sidebar dan kartu dashboard untuk pengguna admin atau manajer.</p>
                                </div>
                                <div class="rounded-3xl bg-background p-5 shadow-inner shadow-black/5 ring-1 ring-border">
                                    <p class="text-sm font-semibold text-foreground">Mobile</p>
                                    <p class="mt-2 text-sm leading-6 text-muted-foreground">Navigasi bawah yang mudah diakses untuk tampilan mobile starter.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-[2rem] bg-sidebar p-6 text-sidebar-foreground shadow-2xl shadow-black/10">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm uppercase tracking-[0.35em] text-muted-foreground">Preview</p>
                            <h2 class="mt-2 text-2xl font-semibold">Mode Mobile</h2>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-3xl bg-primary/10 text-primary">
                            M
                        </div>
                    </div>

                    <div class="mt-8 space-y-5">
                        <div class="rounded-[2rem] border border-border bg-card p-4 shadow-xl shadow-black/10">
                            <div class="rounded-[1.75rem] bg-background p-5 shadow-inner shadow-black/10">
                                <div class="grid gap-3 rounded-[1.5rem] bg-card p-4">
                                    <div class="flex items-center justify-between text-sm text-muted-foreground">
                                        <span class="font-medium text-foreground">SIMPEG</span>
                                        <span>10:21</span>
                                    </div>
                                    <div class="space-y-3 rounded-[1.5rem] bg-muted p-4">
                                        <div class="h-3 w-28 rounded-full bg-border"></div>
                                        <div class="h-3 w-32 rounded-full bg-border"></div>
                                        <div class="h-3 w-20 rounded-full bg-border"></div>
                                    </div>
                                </div>

                                <div class="mt-5 rounded-[1.75rem] bg-card/90 p-4 text-muted-foreground">
                                    <div class="flex items-center justify-between gap-2 text-[11px] uppercase tracking-[0.15em] text-muted-foreground">
                                        <span>Menu bawah</span>
                                        <span class="shrink-0 rounded-full bg-muted px-2 py-1 text-[9px] uppercase tracking-[0.15em] text-muted-foreground">Active</span>
                                    </div>
                                    <div class="mt-4 grid grid-cols-4 gap-2">
                                        <div class="flex items-center justify-center rounded-[1.5rem] bg-muted py-3 shadow-inner shadow-black/10">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-secondary text-secondary-foreground">
                                                <Home class="h-5 w-5" />
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-center rounded-[1.5rem] bg-muted py-3 shadow-inner shadow-black/10">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-secondary text-secondary-foreground">
                                                <LayoutDashboard class="h-5 w-5" />
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-center rounded-[1.5rem] bg-muted py-3 shadow-inner shadow-black/10">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-secondary text-secondary-foreground">
                                                <Settings class="h-5 w-5" />
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-center rounded-[1.5rem] bg-muted py-3 shadow-inner shadow-black/10">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-primary text-primary-foreground shadow-md shadow-primary/20">
                                                <LogIn class="h-5 w-5" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div class="mx-auto mt-8 max-w-lg rounded-[1.5rem] bg-card px-5 py-4 text-center shadow-lg shadow-black/5 lg:mt-10">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-semibold text-foreground">GR Tech</p>
                    <p class="mt-1 text-xs text-muted-foreground">Modern starter for Laravel + Inertia.</p>
                </div>
                <a href="https://wa.me/6283801032021" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-full bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-lg shadow-primary/20 transition hover:bg-primary/90">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white/10 text-primary-foreground">
                        <MessageSquare class="h-4 w-4" />
                    </span>
                    <span>Chat WA</span>
                </a>
            </div>
            <p class="mt-4 text-xs text-muted-foreground">by G. Randy</p>
        </div>

        <div class="fixed inset-x-0 bottom-0 z-50 lg:hidden">
            <div class="mx-auto max-w-md rounded-[2rem] bg-card/95 px-3 py-3 shadow-[0_20px_60px_-24px_rgba(15,23,42,0.25)] backdrop-blur-xl">
                <div class="grid grid-cols-4 gap-2 rounded-[1.75rem] bg-muted/60 p-1 shadow-inner shadow-black/5">
                    <Link href="/" class="group flex flex-col items-center justify-center rounded-[1.5rem] px-2 py-1.5 text-[10px] font-semibold text-primary transition">
                        <span class="mb-1.5 flex h-9 w-9 items-center justify-center rounded-2xl bg-primary text-primary-foreground shadow-md shadow-primary/20 transition">
                            <Home class="h-4.5 w-4.5" />
                        </span>
                        <span>Home</span>
                    </Link>
                    <Link href="/dashboard" class="group flex flex-col items-center justify-center rounded-[1.5rem] px-2 py-1.5 text-[10px] font-semibold text-muted-foreground transition hover:bg-accent hover:text-accent-foreground">
                        <span class="mb-1.5 flex h-9 w-9 items-center justify-center rounded-2xl bg-secondary text-secondary-foreground transition group-hover:bg-primary group-hover:text-primary-foreground">
                            <LayoutDashboard class="h-4.5 w-4.5" />
                        </span>
                        <span>Dashboard</span>
                    </Link>
                    <Link href="/settings/profile" class="group flex flex-col items-center justify-center rounded-[1.5rem] px-2 py-1.5 text-[10px] font-semibold text-muted-foreground transition hover:bg-accent hover:text-accent-foreground">
                        <span class="mb-1.5 flex h-9 w-9 items-center justify-center rounded-2xl bg-secondary text-secondary-foreground transition group-hover:bg-primary group-hover:text-primary-foreground">
                            <Settings class="h-4.5 w-4.5" />
                        </span>
                        <span>Settings</span>
                    </Link>
                    <Link href="/login" class="group flex flex-col items-center justify-center rounded-[1.5rem] px-2 py-1.5 text-[10px] font-semibold text-muted-foreground transition hover:bg-accent hover:text-accent-foreground">
                        <span class="mb-1.5 flex h-9 w-9 items-center justify-center rounded-2xl bg-secondary text-secondary-foreground transition group-hover:bg-primary group-hover:text-primary-foreground">
                            <LogIn class="h-4.5 w-4.5" />
                        </span>
                        <span>Login</span>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
