<script setup>
/**
 * EstimatePanel — the right-hand live cost estimate sidebar.
 */
import { computed } from 'vue';
import { Coins } from 'lucide-vue-next';
import { formatUSD, formatTokens } from '@/utils/format.js';

const props = defineProps({
    estimateData: { type: Object, default: null },
    quality:      { type: String, default: 'medium' },
    size:         { type: String, default: '1024x1024' },
    isLoading:    { type: Boolean, default: false },
});

const margin = computed(() => {
    const user = Number(props.estimateData?.user?.expected ?? 0);
    const prov = Number(props.estimateData?.provider?.expected ?? 0);
    return user - prov;
});
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-xs border border-gray-200/80 dark:border-gray-700/80 space-y-5">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700/60 pb-4">
            <div class="flex items-center gap-2.5">
                <Coins class="w-5 h-5 text-amber-500" />
                <h2 class="text-sm font-bold uppercase tracking-wider text-gray-900 dark:text-gray-100">
                    Upfront Price Estimate
                </h2>
            </div>
            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                +25% Markup
            </span>
        </div>

        <!-- Expected / Max price cards -->
        <div class="grid grid-cols-2 gap-3">
            <div class="p-3.5 rounded-xl bg-gray-50 dark:bg-gray-900/60 border border-gray-200/70 dark:border-gray-700/70">
                <span class="text-[11px] text-gray-500 uppercase font-semibold">Expected Price</span>
                <div class="text-xl font-bold font-mono text-gray-900 dark:text-white mt-1">
                    {{ formatUSD(estimateData?.user?.expected, 4) }}
                </div>
                <span class="text-[10px] text-gray-400">Typical reasoning tokens</span>
            </div>
            <div class="p-3.5 rounded-xl bg-gray-50 dark:bg-gray-900/60 border border-gray-200/70 dark:border-gray-700/70">
                <span class="text-[11px] text-gray-500 uppercase font-semibold">Maximum Price</span>
                <div class="text-xl font-bold font-mono text-gray-900 dark:text-white mt-1">
                    {{ formatUSD(estimateData?.user?.max, 4) }}
                </div>
                <span class="text-[10px] text-gray-400">Worst-case token usage</span>
            </div>
        </div>

        <!-- Provider vs retail -->
        <div class="text-xs space-y-2 p-3 rounded-xl bg-indigo-50/40 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/40">
            <div class="flex justify-between items-center">
                <span class="text-gray-600 dark:text-gray-400">Pixelforge Base Cost:</span>
                <span class="font-mono font-medium text-gray-900 dark:text-gray-200">
                    {{ formatUSD(estimateData?.provider?.expected, 5) }} (exp) / {{ formatUSD(estimateData?.provider?.max, 5) }} (max)
                </span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600 dark:text-gray-400">PromptEdit Margin (25%):</span>
                <span class="font-mono font-medium text-indigo-600 dark:text-indigo-400">
                    +{{ formatUSD(margin, 5) }}
                </span>
            </div>
            <div class="border-t border-indigo-200/60 dark:border-indigo-900/60 pt-2 flex justify-between items-center font-bold">
                <span>You Pay (upon completion):</span>
                <span class="font-mono text-emerald-600 dark:text-emerald-400">
                    {{ formatUSD(estimateData?.user?.expected, 4) }}
                </span>
            </div>
        </div>

        <!-- Token breakdown table -->
        <div class="space-y-2">
            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider block">
                Estimated Token Counts
            </span>
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden text-xs">
                <div class="flex justify-between p-2.5 bg-gray-50 dark:bg-gray-900/80 border-b border-gray-200 dark:border-gray-700">
                    <span class="text-gray-600 dark:text-gray-400">Input Text (Prompt)</span>
                    <span class="font-mono font-semibold">{{ formatTokens(estimateData?.tokens?.text?.baseline) }} tokens (±15%)</span>
                </div>
                <div class="flex justify-between p-2.5 border-b border-gray-200 dark:border-gray-700">
                    <span class="text-gray-600 dark:text-gray-400">Input Images ({{ estimateData?.tokens?.image?.count || 0 }} ref)</span>
                    <span class="font-mono font-semibold">{{ formatTokens(estimateData?.tokens?.image?.total) }} tokens</span>
                </div>
                <div class="flex justify-between p-2.5 bg-gray-50 dark:bg-gray-900/80 border-b border-gray-200 dark:border-gray-700">
                    <span class="text-gray-600 dark:text-gray-400">Output Image ({{ quality }}, {{ size }})</span>
                    <span class="font-mono font-semibold">{{ formatTokens(estimateData?.tokens?.output) }} tokens</span>
                </div>
                <div class="flex justify-between p-2.5">
                    <span class="text-gray-600 dark:text-gray-400">Reasoning ({{ quality }})</span>
                    <span class="font-mono font-semibold">
                        {{ formatTokens(estimateData?.tokens?.reasoning?.min) }} – {{ formatTokens(estimateData?.tokens?.reasoning?.max) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Pricing note -->
        <p class="text-[11px] text-gray-500 dark:text-gray-400 leading-relaxed bg-gray-50 dark:bg-gray-900 p-3 rounded-lg border border-gray-200/60 dark:border-gray-800">
            <strong>How Pricing Works:</strong> Only completed, succeeded generations are billed.
            Failed generations cost <strong>$0.00</strong>. Exact token counts are determined upon
            completion and the actual cost is deducted from your credit balance.
        </p>
    </div>
</template>
