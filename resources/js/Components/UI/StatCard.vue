<script setup>
/**
 * StatCard — reusable KPI metric card for dashboards.
 */
defineProps({
    label:    { type: String, required: true },
    value:    { type: [String, Number], required: true },
    sub:      { type: String, default: '' },
    /** Tailwind color prefix for icon bg/text, e.g. 'indigo', 'emerald', 'amber', 'rose' */
    color:    { type: String, default: 'indigo' },
});
</script>

<template>
    <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-200/80 dark:border-gray-700/80 shadow-xs">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ label }}</span>
            <div :class="`p-2 rounded-lg bg-${color}-50 dark:bg-${color}-950/60 text-${color}-600 dark:text-${color}-400`">
                <slot name="icon" />
            </div>
        </div>
        <div class="text-2xl font-bold font-mono text-gray-900 dark:text-white">{{ value }}</div>
        <span v-if="sub" class="text-[11px] text-gray-500 mt-1 block">{{ sub }}</span>
    </div>
</template>
