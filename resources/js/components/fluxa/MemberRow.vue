<script setup lang="ts">
import { UserX } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

defineProps<{
    member: App.Data.TenantMemberData;
    roles: string[];
}>();

const emit = defineEmits<{
    (e: 'change-role', role: string): void;
    (e: 'remove'): void;
}>();

const roleLabel: Record<string, string> = {
    owner: 'Pemilik',
    admin: 'Admin',
    member: 'Anggota',
};

const roleBadgeVariant: Record<string, 'default' | 'secondary' | 'outline'> = {
    owner: 'default',
    admin: 'secondary',
    member: 'outline',
};

function initials(name: string): string {
    return name
        .split(' ')
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}

const dateLabel = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

// Kolom bertipe date dibaca sebagai Y-m-d tanpa offset, jadi pin jam tengah
// malam lokal supaya format tidak mundur sehari di zona WIB.
const asLocalDate = (ymd: string): Date => new Date(`${ymd}T00:00:00`);
</script>

<template>
    <li class="flex items-center gap-3 py-3">
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
                {{ member.email }}
                <template v-if="member.joined_at">
                    · bergabung
                    {{ dateLabel.format(asLocalDate(member.joined_at)) }}
                </template>
            </p>
        </div>

        <select
            v-if="member.can_change_role"
            :value="member.role"
            class="border-input bg-card focus-visible:ring-ring min-h-11 shrink-0 rounded-lg border px-2 text-xs font-medium focus-visible:ring-2 focus-visible:outline-none"
            :aria-label="`Ubah role ${member.name}`"
            @change="
                emit('change-role', ($event.target as HTMLSelectElement).value)
            "
        >
            <option v-for="role in roles" :key="role" :value="role">
                {{ roleLabel[role] ?? role }}
            </option>
        </select>
        <Badge
            v-else
            :variant="roleBadgeVariant[member.role] ?? 'outline'"
            class="shrink-0"
            >{{ roleLabel[member.role] ?? member.role }}</Badge
        >

        <Button
            v-if="member.can_remove"
            variant="ghost"
            size="icon"
            class="text-money-out hover:text-money-out size-11 shrink-0"
            :aria-label="`Keluarkan ${member.name}`"
            @click="emit('remove')"
        >
            <UserX class="size-4" aria-hidden="true" />
        </Button>
    </li>
</template>
