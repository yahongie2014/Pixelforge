<script setup>
import { Sparkles } from 'lucide-vue-next';

defineProps({
    compact: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="flex items-center gap-2.5">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center text-white shadow-md shadow-indigo-500/20">
            <Sparkles class="w-5 h-5" />
        </div>
        <div v-if="!compact" class="flex flex-col text-left">
            <span class="font-extrabold tracking-tight text-gray-900 dark:text-white text-base leading-tight">
                PromptEdit
            </span>
            <span class="text-[10px] uppercase tracking-wider text-indigo-600 dark:text-indigo-400 font-bold">
                PixelForge Studio
            </span>
        </div>
    </div>
</template>
