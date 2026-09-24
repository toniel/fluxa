<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { CircleAlert, CircleCheck, MailOpen, UserPlus } from '@lucide/vue';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import GoogleIcon from '@/components/GoogleIcon.vue';
import { Button } from '@/components/ui/button';
import { accept as acceptRoute } from '@/routes/invitations';
import { login } from '@/routes';
import { redirect as googleRedirect } from '@/routes/auth/google';

const props = defineProps<{
    invitation: {
        tenant_name: string;
        email: string;
        role: string;
        status: string;
        inviter_name: string;
    };
    token: string;
    state: string;
}>();

const roleLabel: Record<string, string> = {
    admin: 'Admin',
    member: 'Anggota',
};

const form = useForm({});

function accept(): void {
    form.post(acceptRoute.url(props.token));
}

const statusMeta = computed(() => {
    switch (props.state) {
        case 'expired':
            return {
                icon: CircleAlert,
                title: 'Undangan kedaluwarsa',
                description: `Undangan ke ${props.invitation.email} sudah lewat 7 hari. Minta pemilik tenant mengirim ulang.`,
            };
        case 'revoked':
            return {
                icon: CircleAlert,
                title: 'Undangan dibatalkan',
                description:
                    'Undangan ini dibatalkan pemilik tenant. Hubungi mereka bila ini keliru.',
            };
        case 'accepted':
            return {
                icon: CircleCheck,
                title: 'Undangan sudah diterima',
                description:
                    'Undangan ini sudah dipakai. Buka tenant untuk mulai mencatat.',
            };
        default:
            return null;
    }
});
</script>

<template>
    <Head :title="`Undangan ${invitation.tenant_name}`" />

    <div
        class="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6"
    >
        <div class="w-full max-w-sm">
            <div class="flex flex-col items-center gap-2 text-center">
                <AppLogoIcon class="size-9" />
                <h1 class="text-xl font-bold tracking-tight">
                    {{ invitation.tenant_name }}
                </h1>
                <p class="text-muted-foreground text-sm">
                    Undangan sebagai
                    {{ roleLabel[invitation.role] ?? invitation.role }} untuk
                    {{ invitation.email }}, dari {{ invitation.inviter_name }}
                </p>
            </div>

            <div class="bg-card mt-6 rounded-2xl border p-5 text-center">
                <template v-if="state === 'guest'">
                    <MailOpen
                        class="text-muted-foreground mx-auto size-8"
                        aria-hidden="true"
                    />
                    <p class="mt-3 text-sm font-semibold">
                        Masuk dulu untuk menerima
                    </p>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Undangan ini untuk {{ invitation.email }}. Masuk dengan
                        akun yang sesuai, lalu buka tautan ini lagi.
                    </p>
                    <Button as-child class="mt-4 min-h-11 w-full">
                        <Link
                            :href="
                                googleRedirect.url({
                                    query: { invitation: token },
                                })
                            "
                            ><GoogleIcon class="size-4" />Lanjutkan dengan
                            Google</Link
                        >
                    </Button>
                    <Button
                        as-child
                        variant="outline"
                        class="mt-2 min-h-11 w-full"
                    >
                        <Link :href="login.url()">Masuk dengan email</Link>
                    </Button>
                </template>

                <template v-else-if="state === 'ready'">
                    <UserPlus
                        class="text-money-in mx-auto size-8"
                        aria-hidden="true"
                    />
                    <p class="mt-3 text-sm font-semibold">
                        Terima dan gabung tenant?
                    </p>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Kamu akan bisa melihat dan mencatat keuangan
                        {{ invitation.tenant_name }}.
                    </p>
                    <Button
                        class="mt-4 min-h-11 w-full"
                        :disabled="form.processing"
                        @click="accept"
                    >
                        Terima Undangan
                    </Button>
                </template>

                <template v-else-if="state === 'email_mismatch'">
                    <CircleAlert
                        class="text-money-out mx-auto size-8"
                        aria-hidden="true"
                    />
                    <p class="mt-3 text-sm font-semibold">
                        Undangan ini bukan untuk akunmu
                    </p>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Undangan ini untuk {{ invitation.email }}. Keluar lalu
                        masuk dengan akun yang benar — jangan terima dengan akun
                        lain supaya undangan tidak dicuri.
                    </p>
                </template>

                <template v-else-if="state === 'already_member'">
                    <CircleCheck
                        class="text-money-in mx-auto size-8"
                        aria-hidden="true"
                    />
                    <p class="mt-3 text-sm font-semibold">
                        Kamu sudah anggota tenant ini
                    </p>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Tidak perlu menerima lagi.
                    </p>
                </template>

                <template v-else-if="statusMeta">
                    <component
                        :is="statusMeta.icon"
                        class="text-muted-foreground mx-auto size-8"
                        aria-hidden="true"
                    />
                    <p class="mt-3 text-sm font-semibold">
                        {{ statusMeta.title }}
                    </p>
                    <p class="text-muted-foreground mt-1 text-sm">
                        {{ statusMeta.description }}
                    </p>
                </template>
            </div>
        </div>
    </div>
</template>
