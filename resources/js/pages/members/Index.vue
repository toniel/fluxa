<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { UserPlus, Users, UserX } from '@lucide/vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/fluxa/EmptyState.vue';
import ConfirmDeleteDialog from '@/components/fluxa/ConfirmDeleteDialog.vue';
import InvitationList from '@/components/fluxa/InvitationList.vue';
import InviteMemberDialog from '@/components/fluxa/InviteMemberDialog.vue';
import MemberRow from '@/components/fluxa/MemberRow.vue';
import { Button } from '@/components/ui/button';
import { index as membersRoute } from '@/routes/members';
import { destroy as destroyMemberRoute } from '@/routes/members';
import { store as storeInvitationRoute } from '@/routes/invitations';
import { update as updateMemberRoute } from '@/routes/members';

const props = defineProps<{
    members: App.Data.TenantMemberData[];
    invitations: App.Data.TenantInvitationData[];
    roles: string[];
    can: { invite: boolean };
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Anggota', href: membersRoute.url() }] },
});

const inviting = ref(false);

const roleLabel: Record<string, string> = {
    owner: 'Pemilik',
    admin: 'Admin',
    member: 'Anggota',
};

const currentRole = computed(
    () => props.members.find((m) => m.is_current_user)?.role,
);

const roleForm = useForm({ role: 'member' });

function changeRole(member: App.Data.TenantMemberData, role: string): void {
    roleForm
        .transform(() => ({ role }))
        .patch(updateMemberRoute.url(member.id), { preserveScroll: true });
}

const removeForm = useForm({});
const removeTarget = ref<App.Data.TenantMemberData | null>(null);

const removeOpen = computed({
    get: () => removeTarget.value !== null,
    set: (open: boolean) => {
        if (!open) {
            removeTarget.value = null;
        }
    },
});

function confirmRemove(): void {
    const member = removeTarget.value;

    if (member === null) {
        return;
    }

    removeForm.delete(destroyMemberRoute.url(member.id), {
        preserveScroll: true,
    });
}

const accessNotes = [
    {
        role: 'Pemilik',
        description:
            'Semua akses termasuk billing, pengaturan tenant, ubah role',
    },
    {
        role: 'Admin',
        description: 'Kelola kantong, kategori, undang dan keluarkan anggota',
    },
    {
        role: 'Anggota',
        description: 'Catat transaksi dan transfer, lihat laporan',
    },
];
</script>

<template>
    <Head title="Anggota" />

    <div class="space-y-4 p-4">
        <ConfirmDeleteDialog
            v-model:open="removeOpen"
            title="Keluarkan anggota"
            :description="`Anggota \u201C${removeTarget?.name ?? ''}\u201D akan dikeluarkan dari tenant.`"
            confirm-label="Keluarkan anggota"
            @confirm="confirmRemove"
        />

        <InviteMemberDialog
            v-model:open="inviting"
            :action="storeInvitationRoute.url()"
            :roles="roles"
        />

        <header class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-xl font-bold tracking-tight">Anggota</h1>
                <p class="text-muted-foreground text-sm">
                    {{ members.length }} anggota · kamu masuk sebagai
                    {{ roleLabel[currentRole ?? 'member'] }}
                </p>
            </div>
            <Button
                v-if="can.invite"
                class="min-h-11 shrink-0"
                @click="inviting = true"
            >
                <UserPlus class="size-4" aria-hidden="true" />
                Undang
            </Button>
        </header>

        <EmptyState
            v-if="!members.length"
            :icon="Users"
            title="Belum ada anggota lain"
            description="Undang lewat email supaya uang bisa dikelola bersama."
        >
            <Button v-if="can.invite" class="min-h-11" @click="inviting = true">
                <UserPlus class="size-4" aria-hidden="true" />
                Undang anggota
            </Button>
        </EmptyState>

        <template v-else>
            <ul class="bg-card divide-y rounded-2xl border px-3">
                <MemberRow
                    v-for="member in members"
                    :key="member.id"
                    :member="member"
                    :roles="roles"
                    @change-role="changeRole(member, $event)"
                    @remove="removeTarget = member"
                />
            </ul>

            <InvitationList :invitations="invitations" />

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
