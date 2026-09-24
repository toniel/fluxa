<script setup lang="ts">
import { Camera, Lock, Smartphone, UserPlus } from '@lucide/vue';

const items = [
    { icon: Lock, text: 'Tiap tenant punya ruang data sendiri' },
    { icon: UserPlus, text: 'Login Google sekali klik' },
    { icon: Smartphone, text: 'Enak dibuka dari HP' },
    { icon: Camera, text: 'Struk difoto sebagai bukti' },
];
</script>

<template>
    <section class="border-y py-12" aria-label="Kenapa Fluxa">
        <p
            class="text-muted-foreground text-center text-xs font-extrabold tracking-widest uppercase"
        >
            Dibuat untuk kas yang dilihat banyak orang
        </p>
        <ul
            class="mt-6 grid grid-cols-2 gap-6 text-sm font-semibold md:grid-cols-4"
        >
            <li
                v-for="item in items"
                :key="item.text"
                class="flex items-center gap-2.5"
            >
                <component
                    :is="item.icon"
                    class="size-5 shrink-0"
                    aria-hidden="true"
                />
                {{ item.text }}
            </li>
        </ul>
    </section>
</template>
