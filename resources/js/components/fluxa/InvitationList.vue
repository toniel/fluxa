<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Copy, UserX } from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import ConfirmDeleteDialog from '@/components/fluxa/ConfirmDeleteDialog.vue';
import { Button } from '@/components/ui/button';
import {
    destroy as destroyInvitationRoute,
    store as storeInvitationRoute,
} from '@/routes/invitations';

defineProps<{
    invitations: App.Data.TenantInvitationData[];
}>();

const roleLabel: Record<string, string> = {
    admin: 'Admin',
    member: 'Anggota',
};

const dateLabel = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

// Kolom bertipe date dibaca sebagai Y-m-d tanpa offset, jadi pin jam tengah
// malam lokal supaya format tidak mundur sehari di zona WIB.
const asLocalDate = (ymd: string): Date => new Date(`${ymd}T00:00:00`);

const resendForm = useForm({ email: '', role: 'member' });

function resend(invitation: App.Data.TenantInvitationData): void {
    resendForm
        .transform(() => ({
            email: invitation.email,
            role: invitation.role,
        }))
        .post(storeInvitationRoute.url(), { preserveScroll: true });
}

async function copyLink(
    invitation: App.Data.TenantInvitationData,
): Promise<void> {
    try {
        await navigator.clipboard.writeText(invitation.accept_url);
        toast.success('Tautan undangan disalin.');
    } catch {
        toast.error('Gagal menyalin tautan.');
    }
}

const removeForm = useForm({});
const revokeTarget = ref<App.Data.TenantInvitationData | null>(null);

const revokeOpen = computed({
    get: () => revokeTarget.value !== null,
    set: (open: boolean) => {
        if (!open) {
            revokeTarget.value = null;
        }
    },
});

function confirmRevoke(): void {
    const invitation = revokeTarget.value;

    if (invitation === null) {
        return;
    }

    removeForm.delete(destroyInvitationRoute.url(invitation.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <section v-if="invitations.length" class="space-y-2">
        <ConfirmDeleteDialog
            v-model:open="revokeOpen"
            title="Batalkan undangan"
            :description="`Undangan ke \u201C${revokeTarget?.email ?? ''}\u201D akan dibatalkan dan tautannya mati.`"
            confirm-label="Batalkan undangan"
            @confirm="confirmRevoke"
        />

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
                        {{ roleLabel[invitation.role] ?? invitation.role }}
                        · kedaluwarsa
                        {{
                            dateLabel.format(asLocalDate(invitation.expires_at))
                        }}
                    </p>
                </div>
                <Button
                    variant="outline"
                    size="sm"
                    class="min-h-11 shrink-0"
                    @click="copyLink(invitation)"
                >
                    <Copy class="size-3.5" aria-hidden="true" />
                    Salin
                </Button>
                <Button
                    v-if="invitation.can_resend"
                    variant="ghost"
                    size="sm"
                    class="min-h-11 shrink-0"
                    @click="resend(invitation)"
                >
                    Kirim ulang
                </Button>
                <Button
                    v-if="invitation.can_revoke"
                    variant="ghost"
                    size="icon"
                    class="text-money-out hover:text-money-out size-11 shrink-0"
                    :aria-label="`Batalkan undangan ${invitation.email}`"
                    @click="revokeTarget = invitation"
                >
                    <UserX class="size-4" aria-hidden="true" />
                </Button>
            </li>
        </ul>
    </section>
</template>
