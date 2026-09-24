<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import MoneyText from '@/components/fluxa/MoneyText.vue';
import { register } from '@/routes';
</script>

<template>
    <section class="grid items-center gap-10 md:grid-cols-2 md:gap-12">
        <div class="space-y-5">
            <h1
                class="text-4xl font-extrabold tracking-tight md:text-6xl md:leading-[1.05]"
            >
                Uang patungan, nol tebak-tebakan.
            </h1>
            <p class="text-muted-foreground max-w-md text-lg">
                Fluxa adalah buku kas bersama untuk keluarga, RT, dan komunitas.
                Buat kantong, catat keluar masuk, undang anggota, semua
                transparan dari HP.
            </p>
            <div class="flex flex-wrap items-center gap-3">
                <Button as-child class="min-h-11 px-6 py-3.5 text-base">
                    <Link :href="register()">Mulai Gratis</Link>
                </Button>
                <Button
                    as-child
                    variant="outline"
                    class="min-h-11 px-6 py-3.5 text-base"
                >
                    <Link href="#cara-kerja">Lihat cara kerja</Link>
                </Button>
            </div>
        </div>

        <div class="relative">
            <div
                class="bg-card rounded-3xl border p-4 shadow-[0_1px_2px_rgba(23,23,18,.05),0_12px_32px_-16px_rgba(23,23,18,.18)]"
                role="img"
                aria-label="Ilustrasi tampilan aplikasi Fluxa"
            >
                <div class="bg-brand text-brand-foreground rounded-2xl p-5">
                    <p class="text-xs opacity-80">Total saldo semua kantong</p>
                    <p
                        class="font-numeric mt-1 text-3xl font-extrabold tabular-nums"
                    >
                        <MoneyText :value="1250000" />
                    </p>
                    <div class="mt-4 flex flex-wrap gap-2" aria-hidden="true">
                        <span
                            class="rounded-full bg-white/15 px-3 py-1 text-[11px] font-bold"
                            >Kas Harian</span
                        >
                        <span
                            class="rounded-full bg-white/10 px-3 py-1 text-[11px] font-bold"
                            >BCA Bersama</span
                        >
                        <span
                            class="rounded-full bg-white/10 px-3 py-1 text-[11px] font-bold"
                            >Tabungan Kurban</span
                        >
                    </div>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-3">
                    <div class="bg-card rounded-2xl border p-4">
                        <p
                            class="text-muted-foreground text-[11px] font-semibold"
                        >
                            Masuk bulan ini
                        </p>
                        <p
                            class="font-numeric text-lg font-extrabold tabular-nums"
                        >
                            <MoneyText :value="8000000" direction="in" />
                        </p>
                    </div>
                    <div class="bg-card rounded-2xl border p-4">
                        <p
                            class="text-muted-foreground text-[11px] font-semibold"
                        >
                            Keluar bulan ini
                        </p>
                        <p class="font-numeric text-lg font-bold tabular-nums">
                            <MoneyText :value="650000" direction="out" />
                        </p>
                    </div>
                </div>
                <div class="bg-card mt-3 space-y-3 rounded-2xl border p-4">
                    <div
                        class="flex items-center justify-between gap-2 text-sm"
                    >
                        <div class="min-w-0">
                            <p class="truncate font-bold">Iuran warga blok C</p>
                            <p
                                class="text-muted-foreground truncate text-[11px]"
                            >
                                BCA Bersama · 15 Sep
                            </p>
                        </div>
                        <MoneyText
                            :value="450000"
                            direction="in"
                            class="shrink-0 text-sm font-bold"
                        />
                    </div>
                    <div
                        class="flex items-center justify-between gap-2 text-sm"
                    >
                        <div class="min-w-0">
                            <p class="truncate font-bold">Belanja mingguan</p>
                            <p
                                class="text-muted-foreground truncate text-[11px]"
                            >
                                Kas Harian · 19 Sep
                            </p>
                        </div>
                        <MoneyText
                            :value="85000"
                            direction="out"
                            class="shrink-0 text-sm font-bold"
                        />
                    </div>
                </div>
            </div>
            <p class="text-muted-foreground mt-3 text-center text-xs">
                Ilustrasi tampilan aplikasi, bukan data sungguhan.
            </p>
            <Button
                as-child
                class="absolute -bottom-5 left-1/2 min-h-11 -translate-x-1/2 rounded-full px-6 whitespace-nowrap"
            >
                <Link :href="register()">Buat akun gratis</Link>
            </Button>
        </div>
    </section>
</template>
