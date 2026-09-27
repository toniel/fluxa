<script setup lang="ts">
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import ColorSwatches from '@/components/fluxa/ColorSwatches.vue';
import IconSwatches from '@/components/fluxa/IconSwatches.vue';
import ImageUpload from '@/components/fluxa/ImageUpload.vue';
import { Input } from '@/components/ui/input';

/**
 * Penampilan kategori: satu ikon dari tiga sumber — ikon lucide, emoji, atau
 * gambar unggahan — plus warna dari palet kategorikal. Ketiganya saling
 * meniadakan: nilai yang terakhir dipilih yang dipakai.
 */
const props = withDefaults(
    defineProps<{
        icons: string[];
        iconLabels?: Record<string, string>;
        colors: string[];
        icon?: string;
        emoji?: string;
        color?: string;
        iconUrl?: string;
        errors?: Record<string, string | undefined>;
    }>(),
    {
        iconLabels: () => ({}),
        icon: '',
        emoji: '',
        color: '',
        iconUrl: '',
        errors: () => ({}),
    },
);

const emit = defineEmits<{
    (e: 'update:icon', value: string): void;
    (e: 'update:emoji', value: string): void;
    (e: 'update:color', value: string): void;
    (e: 'update:removeIcon', value: boolean): void;
}>();

const mode = ref<'icon' | 'emoji' | 'image'>(
    props.iconUrl ? 'image' : props.emoji ? 'emoji' : 'icon',
);

const imageRemoved = ref(false);
const draftEmoji = ref('');

const shownPreview = computed(() => (imageRemoved.value ? '' : props.iconUrl));

function switchMode(next: 'icon' | 'emoji' | 'image'): void {
    if (props.iconUrl && !imageRemoved.value) {
        imageRemoved.value = true;
        emit('update:removeIcon', true);
    }

    mode.value = next;
}

function pickIcon(name: string): void {
    emit('update:icon', name);
    emit('update:emoji', '');
}

function pickEmoji(emoji: string): void {
    if (emoji === '') {
        return;
    }

    emit('update:emoji', emoji);
    emit('update:icon', '');
}

function commitDraftEmoji(): void {
    pickEmoji(draftEmoji.value.trim());
}

function onSelect(): void {
    imageRemoved.value = false;
    emit('update:removeIcon', false);
    emit('update:icon', '');
    emit('update:emoji', '');
}

function onClear(): void {
    imageRemoved.value = true;
    emit('update:removeIcon', true);
}

const presets: string[] = [
    '☕',
    '🥤',
    '🍜',
    '🍚',
    '🍗',
    '🍞',
    '🍎',
    '⚡',
    '💧',
    '📶',
    '📱',
    '🔋',
    '🚗',
    '🛵',
    '🚌',
    '⛽',
    '🛒',
    '🧾',
    '🏠',
    '🔑',
    '🧼',
    '💊',
    '🐱',
    '🎁',
    '📚',
    '🎮',
    '✈️',
    '👕',
    '💪',
    '💼',
    '🏦',
    '💰',
];

const modeOptions: { value: 'icon' | 'emoji' | 'image'; label: string }[] = [
    { value: 'icon', label: 'Ikon' },
    { value: 'emoji', label: 'Emoji' },
    { value: 'image', label: 'Gambar' },
];
</script>

<template>
    <fieldset class="grid gap-3">
        <legend class="text-sm leading-none font-medium">Penampilan</legend>

        <div class="bg-muted grid grid-cols-3 gap-1 rounded-xl p-1">
            <button
                v-for="option in modeOptions"
                :key="option.value"
                type="button"
                class="focus-visible:ring-ring min-h-10 rounded-lg text-sm font-semibold focus-visible:ring-2 focus-visible:outline-none"
                :class="
                    mode === option.value
                        ? 'bg-card text-foreground shadow-sm'
                        : 'text-muted-foreground'
                "
                :aria-pressed="mode === option.value"
                @click="switchMode(option.value)"
            >
                {{ option.label }}
            </button>
        </div>

        <IconSwatches
            v-if="mode === 'icon'"
            :icons="icons"
            :labels="iconLabels"
            :model-value="icon"
            @update:model-value="pickIcon"
        />

        <template v-if="mode === 'emoji'">
            <div class="grid grid-cols-4 gap-1.5 sm:grid-cols-8">
                <button
                    v-for="preset in presets"
                    :key="preset"
                    type="button"
                    class="focus-visible:ring-ring flex size-11 items-center justify-center rounded-lg border text-lg focus-visible:ring-2 focus-visible:outline-none"
                    :class="
                        emoji === preset
                            ? 'border-primary bg-primary/10'
                            : 'border-input hover:bg-accent'
                    "
                    :aria-pressed="emoji === preset"
                    @click="pickEmoji(preset)"
                >
                    <span aria-hidden="true">{{ preset }}</span>
                </button>
            </div>
            <div class="grid gap-1.5">
                <span class="text-sm leading-none font-medium">Emoji lain</span>
                <Input
                    id="category-emoji"
                    v-model="draftEmoji"
                    type="text"
                    maxlength="8"
                    class="min-h-11"
                    placeholder="Ketik emoji lain..."
                    autocomplete="off"
                    @change="commitDraftEmoji"
                />
            </div>
        </template>

        <ImageUpload
            v-if="mode === 'image'"
            input-id="category-icon"
            input-name="icon_file"
            label="Gambar ikon"
            preview-alt="Ikon kategori"
            :initial-preview="shownPreview"
            :cover="true"
            @select="onSelect"
            @clear="onClear"
        />

        <div class="grid gap-2">
            <span class="text-sm leading-none font-medium">Warna</span>
            <ColorSwatches
                :colors="colors"
                :model-value="color"
                @update:model-value="emit('update:color', $event)"
            />
        </div>

        <InputError
            :message="
                errors.icon_file ?? errors.icon ?? errors.emoji ?? errors.color
            "
        />
    </fieldset>
</template>
