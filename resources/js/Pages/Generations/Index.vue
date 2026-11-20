<script setup>
/**
 * Generations/Index.vue — Studio & History page.
 *
 * All business logic lives in composables and components:
 *   - StudioForm       — prompt form + options + estimation
 *   - GenerationCard   — individual history card
 *   - ImageViewModal   — lightbox
 *   - useGenerationPoller — live status polling
 */
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StudioForm          from '@/Components/Generation/StudioForm.vue';
import GenerationCard      from '@/Components/Generation/GenerationCard.vue';
import ImageViewModal      from '@/Components/Generation/ImageViewModal.vue';
import { Image as ImageIcon, Loader2 } from 'lucide-vue-next';
import { useGenerationPoller } from '@/composables/useGenerationPoller.js';
import { GenerationStatus } from '@/constants/enums.js';

const props = defineProps({
    generations:   Object,
    pricing:       Object,
    stats:         Object,
    currentFilter: String,
});

// Keep the paginator reactive for the poller
const generationsRef = computed(() => props.generations);
const { activeGenerations } = useGenerationPoller(generationsRef);

// Lightbox
const activeImage = ref(null);

// Studio form ref — to call remix() externally
const studioFormRef = ref(null);

function handleRemix(gen) {
    studioFormRef.value?.remix(gen);
}

// History filter tabs
const FILTERS = ['all', ...Object.values(GenerationStatus)];

function setFilter(status) {
    router.get(
        route('generations.index'),
        { status: status === 'all' ? undefined : status },
        { preserveState: true, preserveScroll: true }
    );
}
</script>

<template>
    <Head title="Studio & Generations" />

    <AuthenticatedLayout>
        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">

                <!-- Studio Form + Estimate Panel -->
                <StudioForm ref="studioFormRef" />

                <!-- History section -->
                <div class="space-y-6">
                    <!-- Section header + filter tabs -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-gray-200 dark:border-gray-700 pb-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <span>Generation History</span>
                                <span
                                    v-if="activeGenerations.length > 0"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300 animate-pulse"
                                >
                                    <Loader2 class="w-3 h-3 animate-spin" />
                                    {{ activeGenerations.length }} In Progress
                                </span>
                            </h2>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Live updates via webhooks &amp; polling. Token expenditure recorded per generation.
                            </p>
                        </div>

                        <!-- Filter tabs -->
                        <div class="inline-flex p-1 rounded-xl bg-gray-200/80 dark:bg-gray-800 text-xs font-medium">
                            <button
                                v-for="f in FILTERS"
                                :key="f"
                                @click="setFilter(f)"
                                :class="[
                                    'px-3 py-1.5 rounded-lg capitalize transition-all cursor-pointer',
                                    (currentFilter === f || (f === 'all' && !currentFilter))
                                        ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-xs font-semibold'
                                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white',
                                ]"
                            >
                                {{ f === 'processing' ? 'In Progress' : f }}
                            </button>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div
                        v-if="generations.data.length === 0"
                        class="text-center py-16 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700"
                    >
                        <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-400 flex items-center justify-center mx-auto mb-3">
                            <ImageIcon class="w-6 h-6" />
                        </div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">No generations found</h3>
                        <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">
                            Submit your first prompt above to generate AI artwork!
                        </p>
                    </div>

                    <!-- Generations grid -->
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <GenerationCard
                            v-for="gen in generations.data"
                            :key="gen.id"
                            :gen="gen"
                            @view="activeImage = $event"
                            @remix="handleRemix"
                        />
                    </div>

                    <!-- Pagination -->
                    <div v-if="generations.links && generations.links.length > 3" class="flex justify-center mt-8">
                        <div class="flex items-center gap-1">
                            <Link
                                v-for="(link, i) in generations.links"
                                :key="i"
                                :href="link.url || '#'"
                                :class="[
                                    'px-3 py-1.5 text-xs rounded-lg font-medium transition',
                                    link.active
                                        ? 'bg-indigo-600 text-white font-semibold'
                                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800',
                                    !link.url && 'opacity-40 pointer-events-none',
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Full-resolution lightbox -->
        <ImageViewModal :generation="activeImage" @close="activeImage = null" />
    </AuthenticatedLayout>
</template>
