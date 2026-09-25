<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Layers, ScrollText, UsersRound } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import GoogleIcon from '@/components/GoogleIcon.vue';
import InputError from '@/components/InputError.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { redirect as googleRedirect } from '@/routes/auth/google';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const highlights = [
    { icon: Layers, label: 'Kantong terpisah' },
    { icon: UsersRound, label: 'Peran per anggota' },
    { icon: ScrollText, label: 'Riwayat lengkap' },
];
</script>

<template>
    <Head title="Masuk" />

    <!--
        Di HP panel pengantar duduk di atas form; dari lg ke atas keduanya jadi
        dua kolom sehingga form tidak melebar berlebihan di layar besar.
    -->
    <div class="bg-background min-h-svh lg:grid lg:min-h-svh lg:grid-cols-2">
        <section
            class="bg-brand text-brand-foreground relative overflow-hidden px-6 py-10 lg:flex lg:flex-col lg:justify-between lg:py-12"
        >
            <div
                class="pointer-events-none absolute -top-24 -right-20 size-72 rounded-full bg-white/5"
            />

            <div class="relative flex items-center gap-2">
                <AppLogoIcon class="size-7" />
                <span class="text-lg font-bold tracking-tight">Fluxa</span>
            </div>

            <div class="relative mt-8 lg:mt-0">
                <h1 class="text-3xl leading-tight font-bold tracking-tight">
                    Satu kas,<br />banyak tangan.
                </h1>
                <p class="mt-3 max-w-sm text-sm opacity-85">
                    Catat pemasukan dan pengeluaran bersama anggota keluarga,
                    RT, atau komunitas. Semua orang melihat angka yang sama.
                </p>

                <ul class="mt-5 flex flex-wrap gap-2">
                    <li
                        v-for="item in highlights"
                        :key="item.label"
                        class="flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium"
                    >
                        <component
                            :is="item.icon"
                            class="size-3.5"
                            aria-hidden="true"
                        />
                        {{ item.label }}
                    </li>
                </ul>
            </div>

            <p class="relative mt-8 hidden text-xs opacity-70 lg:block">
                Data keuangan tenant kamu terpisah penuh dari tenant lain.
            </p>
        </section>

        <section class="px-6 py-8 lg:flex lg:items-center lg:justify-center">
            <div class="mx-auto w-full max-w-sm">
                <header class="space-y-1">
                    <h2 class="text-xl font-semibold tracking-tight">Masuk</h2>
                    <p class="text-muted-foreground text-sm">
                        Gunakan email dan kata sandi akun kamu.
                    </p>
                </header>

                <div
                    v-if="status"
                    class="text-money-in mt-4 text-sm font-medium"
                >
                    {{ status }}
                </div>

                <div class="mt-6 space-y-4">
                    <PasskeyVerify />

                    <!-- type eksplisit: <button> tanpa type default-nya submit.
                        <a> penuh (bukan Link Inertia): OAuth mengembalikan 302
                        ke Google, bukan respons Inertia, jadi tidak boleh
                        dibawa lewat XHR. -->
                    <Button as-child variant="outline" class="min-h-11 w-full">
                        <a :href="googleRedirect.url()">
                            <GoogleIcon class="size-4" />
                            Lanjutkan dengan Google
                        </a>
                    </Button>

                    <div class="flex items-center gap-3">
                        <span class="bg-border h-px flex-1" />
                        <span class="text-muted-foreground text-xs">atau</span>
                        <span class="bg-border h-px flex-1" />
                    </div>
                </div>

                <Form
                    v-bind="store.form()"
                    :reset-on-success="['password']"
                    v-slot="{ errors, processing }"
                    class="mt-4 flex flex-col gap-4"
                >
                    <div class="grid gap-2">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="email"
                            class="min-h-11"
                            placeholder="nama@contoh.test"
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <div class="flex items-center justify-between gap-3">
                            <Label for="password">Kata sandi</Label>
                            <TextLink
                                v-if="canResetPassword"
                                :href="request()"
                                class="inline-flex min-h-11 items-center text-sm"
                                :tabindex="5"
                            >
                                Lupa kata sandi?
                            </TextLink>
                        </div>
                        <PasswordInput
                            id="password"
                            name="password"
                            required
                            :tabindex="2"
                            autocomplete="current-password"
                            class="min-h-11"
                            placeholder="Kata sandi"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <Label
                        for="remember"
                        class="flex min-h-11 items-center gap-3"
                    >
                        <Checkbox id="remember" name="remember" :tabindex="3" />
                        <span class="text-sm">Ingat saya</span>
                    </Label>

                    <Button
                        type="submit"
                        class="min-h-11 w-full"
                        :tabindex="4"
                        :disabled="processing"
                        data-test="login-button"
                    >
                        <Spinner v-if="processing" />
                        Masuk
                    </Button>
                </Form>

                <p class="text-muted-foreground mt-6 text-center text-sm">
                    Belum punya akun?
                    <TextLink :href="register()" :tabindex="5">Daftar</TextLink>
                </p>
            </div>
        </section>
    </div>
</template>
