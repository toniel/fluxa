<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Layers, ScrollText, UsersRound } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import GoogleIcon from '@/components/GoogleIcon.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { redirect as googleRedirect } from '@/routes/auth/google';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
}>();

const highlights = [
    { icon: Layers, label: 'Kantong terpisah' },
    { icon: UsersRound, label: 'Peran per anggota' },
    { icon: ScrollText, label: 'Riwayat lengkap' },
];
</script>

<template>
    <Head title="Daftar" />

    <!--
        Struktur sama dengan halaman Masuk: panel pengantar di atas pada HP,
        dua kolom dari lg ke atas.
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
                    Daftar sekali, langsung dapat tenant sendiri lengkap dengan
                    kantong contoh dan langganan Free.
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
                Gratis, tanpa kartu kredit.
            </p>
        </section>

        <section class="px-6 py-8 lg:flex lg:items-center lg:justify-center">
            <div class="mx-auto w-full max-w-sm">
                <header class="space-y-1">
                    <h2 class="text-xl font-semibold tracking-tight">Daftar</h2>
                    <p class="text-muted-foreground text-sm">
                        Isi data di bawah untuk membuat akun kamu.
                    </p>
                </header>

                <div class="mt-6 space-y-4">
                    <Button as-child variant="outline" class="min-h-11 w-full">
                        <a :href="googleRedirect.url()">
                            <GoogleIcon class="size-4" />
                            Daftar dengan Google
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
                    :reset-on-success="['password', 'password_confirmation']"
                    v-slot="{ errors, processing }"
                    class="mt-4 flex flex-col gap-4"
                >
                    <div class="grid gap-2">
                        <Label for="name">Nama</Label>
                        <Input
                            id="name"
                            type="text"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="name"
                            name="name"
                            placeholder="Nama lengkap"
                            class="min-h-11"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            required
                            :tabindex="2"
                            autocomplete="email"
                            name="email"
                            placeholder="nama@contoh.test"
                            class="min-h-11"
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">Kata sandi</Label>
                        <PasswordInput
                            id="password"
                            required
                            :tabindex="3"
                            autocomplete="new-password"
                            name="password"
                            placeholder="Kata sandi"
                            class="min-h-11"
                            :passwordrules="passwordRules"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation"
                            >Konfirmasi kata sandi</Label
                        >
                        <PasswordInput
                            id="password_confirmation"
                            required
                            :tabindex="4"
                            autocomplete="new-password"
                            name="password_confirmation"
                            placeholder="Ulangi kata sandi"
                            class="min-h-11"
                            :passwordrules="passwordRules"
                        />
                        <InputError :message="errors.password_confirmation" />
                    </div>

                    <Button
                        type="submit"
                        class="min-h-11 w-full"
                        tabindex="5"
                        :disabled="processing"
                        data-test="register-user-button"
                    >
                        <Spinner v-if="processing" />
                        Buat akun
                    </Button>
                </Form>

                <p class="text-muted-foreground mt-6 text-center text-sm">
                    Sudah punya akun?
                    <TextLink :href="login()" :tabindex="6">Masuk</TextLink>
                </p>
            </div>
        </section>
    </div>
</template>
