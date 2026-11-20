<script setup>
/**
 * GenerationCard — full card for a single generation in the history grid.
 */
import { ref } from 'vue';
import {
    Sparkles, XCircle, Maximize2, ExternalLink,
    RefreshCw, Copy, Check,
} from 'lucide-vue-next';
import StatusBadge     from '@/Components/Generation/StatusBadge.vue';
import CostSummary     from '@/Components/Generation/CostSummary.vue';
import TokenBreakdown  from '@/Components/Generation/TokenBreakdown.vue';
import { formatDate }  from '@/utils/format.js';

const props = defineProps({
    gen: { type: Object, required: true },
});

const emit = defineEmits(['view', 'remix', 'copy']);

const copied = ref(false);

function handleCopy() {
    navigator.clipboard.writeText(props.gen.prompt);
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2000);
    emit('copy', props.gen);
}
</script>

<template>
    <article class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/80 dark:border-gray-700/80 shadow-xs flex flex-col overflow-hidden transition hover:shadow-md">

        <!-- Image / Status Canvas -->
        <div class="relative aspect-square bg-gray-100 dark:bg-gray-900 overflow-hidden flex items-center justify-center group">

            <!-- Succeeded: show image -->
            <template v-if="gen.status === 'succeeded' && gen.image_url">
                <img
                    :src="gen.image_url"
                    :alt="gen.prompt"
                    class="w-full h-full object-cover transition duration-300 group-hover:scale-105"
                    loading="lazy"
                />
                <!-- Hover overlay -->
                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3">
                    <button
                        type="button"
                        @click="emit('view', gen)"
                        class="p-2.5 rounded-full bg-white/90 text-gray-900 hover:bg-white shadow-lg transition"
                        title="View full resolution"
                    >
                        <Maximize2 class="w-4 h-4" />
                    </button>
                    <a
                        :href="gen.image_url"
                        target="_blank"
                        download
                        class="p-2.5 rounded-full bg-white/90 text-gray-900 hover:bg-white shadow-lg transition"
                        title="Open original image"
                    >
                        <ExternalLink class="w-4 h-4" />
                    </a>
                </div>
                <div class="absolute bottom-2 left-2 px-2 py-0.5 rounded-md bg-black/60 backdrop-blur-xs text-[10px] text-white/90 font-mono">
                    Signed 24h
                </div>
            </template>

            <!-- Active: spinner -->
            <template v-else-if="gen.status === 'queued' || gen.status === 'processing'">
                <div class="flex flex-col items-center justify-center p-6 text-center space-y-3">
                    <div class="relative">
                        <div class="w-12 h-12 rounded-full border-4 border-indigo-200 dark:border-indigo-900 border-t-indigo-600 animate-spin" />
                        <Sparkles class="w-5 h-5 text-indigo-600 absolute inset-0 m-auto" />
                    </div>
                    <div>
                        <span class="text-sm font-semibold capitalize text-gray-900 dark:text-gray-100 block">{{ gen.status }}…</span>
                        <span class="text-[11px] text-gray-500">Pixelforge is painting your canvas</span>
                    </div>
                </div>
            </template>

            <!-- Failed -->
            <template v-else>
                <div class="flex flex-col items-center justify-center p-6 text-center space-y-2 text-rose-500">
                    <XCircle class="w-10 h-10" />
                    <span class="text-sm font-semibold">Generation Failed</span>
                    <span class="text-[11px] text-gray-500 max-w-[200px] line-clamp-2">
                        {{ gen.error_message || gen.error_code || 'Unknown error' }}
                    </span>
                </div>
            </template>

            <!-- Top badges row -->
            <div class="absolute top-2.5 left-2.5 right-2.5 flex items-center justify-between pointer-events-none">
                <StatusBadge :status="gen.status" class="pointer-events-auto" />
                <span class="px-2 py-0.5 rounded-md bg-black/60 backdrop-blur-xs text-[10px] text-white font-mono font-medium">
                    {{ gen.quality }} · {{ gen.size }}
                </span>
            </div>
        </div>

        <!-- Card body -->
        <div class="p-4 flex-1 flex flex-col justify-between space-y-3">
            <div>
                <p class="text-xs font-medium text-gray-900 dark:text-gray-200 line-clamp-2 hover:line-clamp-none transition-all">
                    {{ gen.prompt }}
                </p>
                <span class="text-[10px] text-gray-400 mt-1 block">{{ formatDate(gen.created_at) }}</span>
            </div>

            <CostSummary
                :status="gen.status"
                :actual-cost-user="gen.actual_cost_user"
                :estimated-cost-user="gen.estimated_cost_user"
            />

            <TokenBreakdown :gen="gen" />

            <!-- Action bar -->
            <div class="border-t border-gray-100 dark:border-gray-700/60 pt-2.5 flex items-center justify-between">
                <button
                    type="button"
                    @click="emit('remix', gen)"
                    class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline"
                >
                    <RefreshCw class="w-3.5 h-3.5" />
                    <span>Remix</span>
                </button>

                <button
                    type="button"
                    @click="handleCopy"
                    class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                >
                    <component :is="copied ? Check : Copy" class="w-3.5 h-3.5" />
                    <span>{{ copied ? 'Copied' : 'Copy' }}</span>
                </button>
            </div>
        </div>
    </article>
</template>
