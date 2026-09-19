<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Copy, MailPlus, Users } from '@lucide/vue';
import EmptyState from '@/components/fluxa/EmptyState.vue';
import ErrorState from '@/components/fluxa/ErrorState.vue';
import PageHeader from '@/components/fluxa/PageHeader.vue';
import SampleNotice from '@/components/fluxa/SampleNotice.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { notYet } from '@/lib/notYet';
import { index as membersRoute } from '@/routes/members';

type Member = {
    id: number;
    name: string;
    email: string;
    role: string;
    joined_at: string;
    can_manage: boolean;
};

type Invitation = {
    id: number;
    email: string;
    role: string;
    status: string;
    expires_at: string;
};

defineProps<{
    state: string;
    members: Member[];
    invitations: Invitation[];
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Anggota', href: membersRoute() }] },
});

const roleLabel: Record<string, string> = {
    owner: 'Pemilik',
    admin: 'Admin',
    member: 'Anggota',
};

const dateLabel = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

function initials(name: string): string {
    return name
        .split(' ')
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}
</script>

<template>
    <Head title="Anggota" />

    <div class="space-y-4 p-4">
        <SampleNotice />

        <PageHeader
            title="Anggota"
            description="Orang yang bisa melihat dan mengelola uang di tenant ini."
        >
            <template #action>
                <Button class="min-h-11" @click="notYet('Undang anggota')">
                    <MailPlus class="size-4" aria-hidden="true" />
                    Undang
                </Button>
            </template>
        </PageHeader>

        <ErrorState
            v-if="state === 'failed'"
            title="Daftar anggota gagal dimuat"
            description="Data tidak bisa diambil saat ini. Coba muat ulang halaman."
        />

        <EmptyState
            v-else-if="!members.length"
            :icon="Users"
            title="Belum ada anggota lain"
            description="Undang lewat email supaya uang bisa dikelola bersama."
        >
            <Button class="min-h-11" @click="notYet('Undang anggota')">
                <MailPlus class="size-4" aria-hidden="true" />
                Undang anggota
            </Button>
        </EmptyState>

        <template v-else>
            <ul class="bg-card divide-y rounded-lg border">
                <li
                    v-for="member in members"
                    :key="member.id"
                    class="flex items-center justify-between gap-3 p-3"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <!--
                            Inisial, bukan foto: tidak ada foto asli yang dimiliki,
                            dan avatar karangan akan tampil seolah data sungguhan.
                        -->
                        <span
                            class="bg-muted text-muted-foreground flex size-10 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
                            aria-hidden="true"
                            >{{ initials(member.name) }}</span
                        >
                        <span class="min-w-0">
                            <span class="block truncate font-medium">{{
                                member.name
                            }}</span>
                            <span
                                class="text-muted-foreground block truncate text-xs"
                                >{{ member.email }}</span
                            >
                            <span class="text-muted-foreground block text-xs"
                                >Bergabung
                                {{
                                    dateLabel.format(new Date(member.joined_at))
                                }}</span
                            >
                        </span>
                    </div>
                    <div class="flex shrink-0 flex-col items-end gap-1">
                        <Badge
                            :variant="
                                member.role === 'owner'
                                    ? 'default'
                                    : 'secondary'
                            "
                            >{{ roleLabel[member.role] ?? member.role }}</Badge
                        >
                        <Button
                            v-if="member.can_manage"
                            variant="ghost"
                            size="sm"
                            class="min-h-11 px-2 text-xs md:min-h-9"
                            @click="notYet('Kelola anggota')"
                        >
                            Kelola
                        </Button>
                    </div>
                </li>
            </ul>

            <section v-if="invitations.length" class="space-y-2">
                <h2 class="text-muted-foreground px-1 text-xs font-medium">
                    Undangan menunggu
                </h2>
                <ul class="bg-card divide-y rounded-lg border">
                    <li
                        v-for="invitation in invitations"
                        :key="invitation.id"
                        class="flex items-center justify-between gap-3 p-3"
                    >
                        <span class="min-w-0">
                            <span class="block truncate text-sm">{{
                                invitation.email
                            }}</span>
                            <span class="text-muted-foreground block text-xs">
                                {{
                                    roleLabel[invitation.role] ??
                                    invitation.role
                                }}
                                · kedaluwarsa
                                {{
                                    dateLabel.format(
                                        new Date(invitation.expires_at),
                                    )
                                }}
                            </span>
                        </span>
                        <Button
                            variant="outline"
                            size="sm"
                            class="min-h-11 shrink-0 md:min-h-9"
                            @click="notYet('Salin tautan undangan')"
                        >
                            <Copy class="size-3.5" aria-hidden="true" />
                            Salin
                        </Button>
                    </li>
                </ul>
            </section>
        </template>
    </div>
</template>
