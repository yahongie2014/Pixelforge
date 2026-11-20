<script setup>
/**
 * StatusBadge — renders a coloured pill for a generation status.
 * Props:
 *   status: 'queued' | 'processing' | 'succeeded' | 'failed'
 */
defineProps({
    status: { type: String, required: true },
});

const colorMap = {
    succeeded:  'bg-emerald-500 text-white',
    processing: 'bg-blue-600 text-white animate-pulse',
    queued:     'bg-amber-500 text-white animate-pulse',
    failed:     'bg-rose-500 text-white',
};
</script>

<template>
    <span
        :class="[
            'inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider shadow-xs',
            colorMap[status] ?? 'bg-gray-400 text-white',
        ]"
    >
        {{ status }}
    </span>
</template>
