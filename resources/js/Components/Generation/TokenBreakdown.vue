<script setup>
/**
 * TokenBreakdown — collapsible cost + token detail panel per generation card.
 */
import { ref } from 'vue';
import { ChevronDown, ChevronUp } from 'lucide-vue-next';
import { formatUSD } from '@/utils/format.js';

defineProps({
    gen: { type: Object, required: true },
});
const open = ref(false);
</script>

<template>
    <div>
        <button
            type="button"
            @click="open = !open"
            class="w-full flex items-center justify-between text-[11px] font-semibold text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 py-1"
        >
            <span>Cost &amp; Token Breakdown</span>
            <component :is="open ? ChevronUp : ChevronDown" class="w-3.5 h-3.5" />
        </button>

        <div
            v-if="open"
            class="mt-2 space-y-1.5 p-2.5 rounded-lg bg-gray-100/60 dark:bg-gray-900 text-[11px] font-mono border border-gray-200/40 dark:border-gray-800"
        >
            <div class="flex justify-between">
                <span class="text-gray-500">Provider Cost:</span>
                <span class="text-gray-800 dark:text-gray-200 font-semibold">{{ formatUSD(gen.actual_cost_provider, 9) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">PromptEdit 25%:</span>
                <span class="text-indigo-600 dark:text-indigo-400 font-semibold">
                    +{{ formatUSD(Number(gen.actual_cost_user || 0) - Number(gen.actual_cost_provider || 0), 9) }}
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Total Charged:</span>
                <span class="text-emerald-600 dark:text-emerald-400 font-bold">{{ formatUSD(gen.actual_cost_user, 9) }}</span>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700 pt-1.5 text-[10px] space-y-1">
                <div class="flex justify-between text-gray-500">
                    <span>Input Tokens (text / img):</span>
                    <span>{{ gen.input_text_tokens }} / {{ gen.input_image_tokens }}</span>
                </div>
                <div class="flex justify-between text-gray-500">
                    <span>Output Tokens:</span>
                    <span>{{ gen.output_tokens }}</span>
                </div>
                <div class="flex justify-between text-gray-500">
                    <span>Reasoning Tokens:</span>
                    <span>{{ gen.reasoning_tokens }}</span>
                </div>
                <div class="flex justify-between font-bold text-gray-700 dark:text-gray-300">
                    <span>Total Tokens:</span>
                    <span>{{ gen.total_tokens }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
