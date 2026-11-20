<script setup>
/**
 * SectionCard — a white/dark card with a header slot and content slot.
 * Used by Admin Report and Studio history sections.
 */
defineProps({
    title: { type: String, default: '' },
    sub:   { type: String, default: '' },
});
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/80 dark:border-gray-700/80 shadow-xs overflow-hidden">
        <!-- Header -->
        <div class="p-5 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <slot name="title">{{ title }}</slot>
                </h2>
                <p v-if="sub || $slots.sub" class="text-xs text-gray-500 mt-0.5">
                    <slot name="sub">{{ sub }}</slot>
                </p>
            </div>
            <div v-if="$slots.action">
                <slot name="action" />
            </div>
        </div>
        <!-- Content -->
        <slot />
    </div>
</template>
