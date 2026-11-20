<script setup>
/**
 * ImageViewModal — full-resolution lightbox for a completed generation.
 */
import Modal from '@/Components/Modal.vue';
import { ExternalLink } from 'lucide-vue-next';

defineProps({
    generation: { type: Object, default: null },
});

const emit = defineEmits(['close']);
</script>

<template>
    <Modal :show="!!generation" @close="emit('close')" max-width="4xl">
        <div v-if="generation" class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Generated Image</h3>
                    <p class="text-xs text-gray-500 font-mono">{{ generation.size }} · {{ generation.quality }} quality</p>
                </div>
                <a
                    :href="generation.image_url"
                    target="_blank"
                    download
                    class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition"
                >
                    <ExternalLink class="w-3.5 h-3.5" />
                    <span>Download Original</span>
                </a>
            </div>

            <div class="rounded-xl overflow-hidden bg-black/90 flex items-center justify-center max-h-[70vh]">
                <img
                    :src="generation.image_url"
                    :alt="generation.prompt"
                    class="max-h-[70vh] w-auto object-contain"
                />
            </div>

            <div class="mt-4 p-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 text-xs text-gray-700 dark:text-gray-300">
                <span class="font-bold block mb-1">Prompt:</span>
                <p class="font-mono text-[11px] leading-relaxed">{{ generation.prompt }}</p>
            </div>
        </div>
    </Modal>
</template>
