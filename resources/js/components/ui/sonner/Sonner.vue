<script lang="ts" setup>
import type { ToasterProps } from "vue-sonner"
import { CircleCheckIcon, InfoIcon, Loader2Icon, OctagonXIcon, TriangleAlertIcon, XIcon } from "@lucide/vue"
import { Toaster as Sonner } from "vue-sonner"
import { cn } from "@/lib/utils"

import 'vue-sonner/style.css';

const props = withDefaults(defineProps<ToasterProps>(), {
  richColors: true,
})
</script>

<template>
  <Sonner
    :class="cn('toaster group', props.class)"
    :style="{
      '--normal-bg': 'var(--popover)',
      '--normal-text': 'var(--popover-foreground)',
      '--normal-border': 'var(--border)',
      '--border-radius': 'var(--radius)',
      '--success-bg': 'color-mix(in srgb, var(--money-in) 12%, var(--popover))',
      '--success-border': 'color-mix(in srgb, var(--money-in) 45%, var(--border))',
      '--success-text': 'var(--money-in)',
      '--info-bg': 'var(--popover)',
      '--info-border': 'var(--border)',
      '--info-text': 'var(--popover-foreground)',
      '--warning-bg': 'var(--popover)',
      '--warning-border': 'var(--border)',
      '--warning-text': 'var(--popover-foreground)',
      '--error-bg': 'var(--popover)',
      '--error-border': 'var(--destructive)',
      '--error-text': 'var(--destructive)',
    }"
    v-bind="props"
  >
    <template #success-icon>
      <CircleCheckIcon class="size-4" />
    </template>
    <template #info-icon>
      <InfoIcon class="size-4" />
    </template>
    <template #warning-icon>
      <TriangleAlertIcon class="size-4" />
    </template>
    <template #error-icon>
      <OctagonXIcon class="size-4" />
    </template>
    <template #loading-icon>
      <div>
        <Loader2Icon class="size-4 animate-spin" />
      </div>
    </template>
    <template #close-icon>
      <XIcon class="size-4" />
    </template>
  </Sonner>
</template>
