<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Copy, ShieldCheck, UserPlus, UserX, Users } from '@lucide/vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/fluxa/EmptyState.vue';
import ErrorState from '@/components/fluxa/ErrorState.vue';
import InviteMemberDialog from '@/components/fluxa/InviteMemberDialog.vue';
import SampleNotice from '@/components/fluxa/SampleNotice.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { notYet } from '@/lib/notYet';
import { index as membersRoute } from '@/routes/members';

type Member = {
    id: number;
    name: string;
    email: string;
    role: 'owner' | 'admin' | 'member';
    joined_at: string;
    tx_count: number;
    is_current_user: boolean;
    can_change_role: boolean;
    can_remove: boolean;
};

type Invitation = {
    id: number;
    email: string;
    role: string;
    status: string;
    expires_at: string;
};

const props = defineProps<{
    state: string;
    members: Member[];
    invitations: Invitation[];
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Anggota', href: membersRoute() }] },
});

const inviting = ref(false);

const roleLabel: Record<string, string> = {
    owner: 'Owner',
    admin: 'Admin',
    member: 'Member',
};

const roleBadgeVariant: Record<string, 'default' | 'secondary' | 'outline'> = {
    owner: 'default',
    admin: 'secondary',
    member: 'outline',
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

const currentRole = computed(
    () => props.members.find((m) => m.is_current_user)?.role,
);

const accessNotes = [
    {
        role: 'Owner',
        description:
            'Semua akses termasuk billing, pengaturan tenant, hapus anggota',
    },
    {
        role: 'Admin',
        description: 'Kelola kantong, kategori, undang anggota & ubah role',
    },
    {
        role: 'Member',
        description: 'Catat transaksi & transfer, lihat laporan',
    },
];
</script>

<template>
    <Head title="Anggota" />

    <div class="space-y-4 p-4">
        <SampleNotice />

        <InviteMemberDialog v-model:open="inviting" />

        <header class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-xl font-bold tracking-tight">Anggota</h1>
                <p class="text-muted-foreground text-sm">
                    {{ members.length }} anggota · kamu masuk sebagai
                    {{ roleLabel[currentRole ?? 'member'] }}
                </p>
            </div>
            <Button class="min-h-11 shrink-0" @click="inviting = true">
                <UserPlus class="size-4" aria-hidden="true" />
                Undang
            </Button>
        </header>

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
            <Button class="min-h-11" @click="inviting = true">
                <UserPlus class="size-4" aria-hidden="true" />
                Undang anggota
            </Button>
        </EmptyState>

        <template v-else>
            <ul class="bg-card divide-y rounded-2xl border px-3">
                <li
                    v-for="member in members"
                    :key="member.id"
                    class="flex items-center gap-3 py-3"
                >
                    <span
                        class="bg-accent text-money-in flex size-10 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
                        aria-hidden="true"
                        >{{ initials(member.name) }}</span
                    >

                    <div class="min-w-0 flex-1">
                        <p class="flex flex-wrap items-baseline gap-x-1.5">
                            <span class="truncate text-sm font-medium">{{
                                member.name
                            }}</span>
                            <span
                                v-if="member.is_current_user"
                                class="text-muted-foreground text-xs"
                                >(kamu)</span
                            >
                        </p>
                        <p class="text-muted-foreground truncate text-xs">
                            {{ member.email }} · {{ member.tx_count }} transaksi
                        </p>
                    </div>

                    <Badge
                        :variant="roleBadgeVariant[member.role]"
                        class="shrink-0"
                        >{{ roleLabel[member.role] }}</Badge
                    >

                    <!--
                        Baris "kamu" dan role owner tidak pernah mendapat tombol
                        aksi, mengikuti PermissionEnum::MembersManageRole dan
                        guard "owner tidak bisa dikeluarkan dari tenantnya
                        sendiri" - ini menampilkan izin sungguhan, bukan
                        menyembunyikan tombol yang seharusnya tetap ada.
                    -->
                    <div
                        v-if="member.can_change_role || member.can_remove"
                        class="flex shrink-0 items-center gap-1"
                    >
                        <Button
                            v-if="member.can_change_role"
                            variant="ghost"
                            size="icon"
                            class="size-11"
                            :aria-label="`Ubah role ${member.name}`"
                            @click="notYet(`Ubah role ${member.name}`)"
                        >
                            <ShieldCheck class="size-4" aria-hidden="true" />
                        </Button>
                        <Button
                            v-if="member.can_remove"
                            variant="ghost"
                            size="icon"
                            class="text-money-out size-11"
                            :aria-label="`Keluarkan ${member.name}`"
                            @click="notYet(`Keluarkan ${member.name}`)"
                        >
                            <UserX class="size-4" aria-hidden="true" />
                        </Button>
                    </div>
                </li>
            </ul>

            <section v-if="invitations.length" class="space-y-2">
                <h2 class="text-muted-foreground px-1 text-xs font-semibold">
                    Undangan menunggu
                </h2>
                <ul class="bg-card divide-y rounded-2xl border px-3">
                    <li
                        v-for="invitation in invitations"
                        :key="invitation.id"
                        class="flex items-center gap-3 py-3"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">
                                {{ invitation.email }}
                            </p>
                            <p class="text-muted-foreground truncate text-xs">
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
                            </p>
                        </div>
                        <Button
                            variant="outline"
                            size="sm"
                            class="min-h-11 shrink-0"
                            @click="notYet('Salin tautan undangan')"
                        >
                            <Copy class="size-3.5" aria-hidden="true" />
                            Salin
                        </Button>
                    </li>
                </ul>
            </section>

            <section class="bg-card rounded-2xl border p-4">
                <h2 class="font-semibold">Hak akses per role</h2>
                <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <div
                        v-for="note in accessNotes"
                        :key="note.role"
                        class="bg-muted rounded-xl p-3"
                    >
                        <p class="text-sm font-semibold">{{ note.role }}</p>
                        <p class="text-muted-foreground mt-1 text-xs">
                            {{ note.description }}
                        </p>
                    </div>
                </div>
            </section>
        </template>
    </div>
</template>
