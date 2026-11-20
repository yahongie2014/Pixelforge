<script setup>
/**
 * CostSummary — compact cost row shown on each generation card.
 * Displays actual (post-completion) vs estimated costs.
 */
import { formatUSD } from '@/utils/format.js';

defineProps({
    status:            { type: String,  required: true },
    actualCostUser:    { type: [String, Number], default: '0' },
    estimatedCostUser: { type: [String, Number], default: '0' },
});
</script>

<template>
    <div class="p-2.5 rounded-xl bg-gray-50 dark:bg-gray-900/60 border border-gray-200/60 dark:border-gray-700/60 flex items-center justify-between text-xs">
        <div>
            <span class="text-[10px] text-gray-400 uppercase block font-semibold">User Charge</span>
            <span
                :class="[
                    'font-mono font-bold text-sm',
                    status === 'succeeded'
                        ? 'text-emerald-600 dark:text-emerald-400'
                        : 'text-gray-500',
                ]"
            >
                {{ status === 'succeeded' ? formatUSD(actualCostUser, 4) : '$0.0000' }}
            </span>
        </div>
        <div class="text-right">
            <span class="text-[10px] text-gray-400 uppercase block font-semibold">Estimated</span>
            <span class="font-mono text-xs text-gray-500">{{ formatUSD(estimatedCostUser, 4) }}</span>
        </div>
    </div>
</template>
