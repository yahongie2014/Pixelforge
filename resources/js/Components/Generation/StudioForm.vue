<script setup>
/**
 * StudioForm — the prompt + options form panel for submitting a new generation.
 * Emits 'submit' when form is submitted.
 */
import { ref, watch } from 'vue';
import { useForm }   from '@inertiajs/vue3';
import {
    Sparkles, Image as ImageIcon, Info,
    Plus, Trash2, Loader2, AlertCircle, CheckCircle2,
} from 'lucide-vue-next';
import InputError       from '@/Components/InputError.vue';
import PrimaryButton    from '@/Components/PrimaryButton.vue';
import { useEstimate }  from '@/composables/useEstimate.js';
import EstimatePanel    from '@/Components/Generation/EstimatePanel.vue';
import { PixelforgeDirectives, GenerationSize, GenerationQuality } from '@/constants/enums.js';

const { isEstimating, estimateData, canAfford, fetchEstimate } = useEstimate();

const form = useForm({
    prompt:           '',
    size:             GenerationSize.SQUARE,
    quality:          GenerationQuality.MEDIUM,
    reference_images: [],
});

const newImageUrl  = ref('');
const showRefInput = ref(false);

// Testing directives
const directives = PixelforgeDirectives;

function toggleDirective(tag) {
    form.prompt = form.prompt.includes(tag)
        ? form.prompt.replace(tag, '').trim()
        : (form.prompt + ' ' + tag).trim();
}

function addReferenceImage() {
    const url = newImageUrl.value.trim();
    if (url && form.reference_images.length < 4) {
        form.reference_images.push(url);
        newImageUrl.value  = '';
        showRefInput.value = false;
    }
}

function removeReferenceImage(idx) {
    form.reference_images.splice(idx, 1);
}

function triggerEstimate() {
    fetchEstimate({
        prompt:           form.prompt,
        size:             form.size,
        quality:          form.quality,
        reference_images: form.reference_images,
    });
}

watch(
    () => [form.prompt, form.size, form.quality, form.reference_images.length],
    triggerEstimate,
    { immediate: true }
);

function submit() {
    form.post(route('generations.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('prompt', 'reference_images');
            triggerEstimate();
        },
    });
}

// Expose remixPrompt for parent use
function remix(gen) {
    form.prompt           = gen.prompt;
    form.size             = gen.size ?? '1024x1024';
    form.quality          = gen.quality ?? 'medium';
    form.reference_images = Array.isArray(gen.reference_images) ? [...gen.reference_images] : [];
    window.scrollTo({ top: 0, behavior: 'smooth' });
    triggerEstimate();
}

defineExpose({ remix });

// Size options
const sizeOptions = [
    { value: '1024x1024', ratio: '1:1', label: '1024×1024', w: 'w-5', h: 'h-5' },
    { value: '1536x1024', ratio: '3:2', label: '1536×1024', w: 'w-6', h: 'h-4' },
    { value: '1024x1536', ratio: '2:3', label: '1024×1536', w: 'w-4', h: 'h-6' },
];

const qualityOptions = [
    { value: 'low',    label: 'Low',    sub: 'Draft' },
    { value: 'medium', label: 'Medium', sub: 'Standard' },
    { value: 'high',   label: 'High',   sub: 'Detailed' },
];

const optionBase   = 'p-2.5 rounded-xl border text-center flex flex-col items-center justify-center gap-1 transition-all';
const optionActive = 'border-indigo-600 dark:border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 ring-2 ring-indigo-500/20 font-semibold';
const optionIdle   = 'border-gray-200 dark:border-gray-700 hover:border-gray-300 text-gray-700 dark:text-gray-300';
</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <!-- Form panel -->
        <div class="lg:col-span-7 bg-white dark:bg-gray-800 rounded-2xl p-6 sm:p-7 shadow-xs border border-gray-200/80 dark:border-gray-700/80">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                        <Sparkles class="w-4 h-4" />
                    </div>
                    <h1 class="text-lg font-bold text-gray-900 dark:text-white">Create New Image</h1>
                </div>
                <span class="text-xs text-gray-500 font-mono">{{ form.prompt.length }} / 4,000 chars</span>
            </div>

            <form @submit.prevent="submit" class="space-y-6">

                <!-- Prompt -->
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">Prompt</label>
                    <textarea
                        v-model="form.prompt"
                        rows="4"
                        placeholder="Describe the image you want to create…"
                        class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900/60 text-sm focus:border-indigo-500 focus:ring-indigo-500 placeholder:text-gray-400 dark:placeholder:text-gray-500 transition-colors"
                        required
                    />
                    <InputError :message="form.errors.prompt" class="mt-2" />

                    <!-- Testing directives -->
                    <div class="mt-2.5">
                        <div class="flex items-center gap-1.5 text-[11px] text-gray-500 dark:text-gray-400 mb-1.5 font-medium">
                            <Info class="w-3.5 h-3.5" />
                            <span>Testing keywords (click to toggle):</span>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                v-for="d in directives"
                                :key="d.tag"
                                type="button"
                                @click="toggleDirective(d.tag)"
                                :class="[
                                    'text-[11px] font-mono px-2 py-0.5 rounded-md border transition-all cursor-pointer select-none',
                                    form.prompt.includes(d.tag)
                                        ? 'bg-indigo-600 border-indigo-600 text-white font-semibold shadow-xs'
                                        : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:border-gray-300',
                                ]"
                                :title="d.desc"
                            >
                                {{ d.tag }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Size & Quality -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <!-- Size -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">Image Dimensions</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                v-for="opt in sizeOptions"
                                :key="opt.value"
                                type="button"
                                @click="form.size = opt.value"
                                :class="[optionBase, form.size === opt.value ? optionActive : optionIdle]"
                            >
                                <div :class="[opt.w, opt.h, 'border-2 rounded-xs border-current opacity-80']" />
                                <span class="text-xs font-mono font-medium">{{ opt.ratio }}</span>
                                <span class="text-[10px] text-gray-500">{{ opt.label }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Quality -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">Generation Quality</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                v-for="opt in qualityOptions"
                                :key="opt.value"
                                type="button"
                                @click="form.quality = opt.value"
                                :class="[optionBase, form.quality === opt.value ? optionActive : optionIdle]"
                            >
                                <span class="text-xs font-semibold">{{ opt.label }}</span>
                                <span class="text-[10px] text-gray-500 mt-0.5">{{ opt.sub }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Reference images -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                            Reference Images <span class="font-normal text-gray-400">(optional, max 4)</span>
                        </label>
                        <span class="text-[11px] text-gray-500">765 tokens per image</span>
                    </div>

                    <div v-if="form.reference_images.length > 0" class="space-y-2 mb-3">
                        <div
                            v-for="(url, idx) in form.reference_images"
                            :key="idx"
                            class="flex items-center justify-between p-2.5 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-xs"
                        >
                            <div class="flex items-center gap-2 truncate">
                                <ImageIcon class="w-4 h-4 text-indigo-500 shrink-0" />
                                <span class="font-mono truncate text-gray-700 dark:text-gray-300">{{ url }}</span>
                            </div>
                            <button type="button" @click="removeReferenceImage(idx)" class="text-gray-400 hover:text-rose-500 p-1" title="Remove">
                                <Trash2 class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>

                    <div v-if="form.reference_images.length < 4">
                        <div v-if="showRefInput" class="flex gap-2">
                            <input
                                v-model="newImageUrl"
                                type="url"
                                placeholder="https://example.com/style-reference.png"
                                class="flex-1 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-xs focus:ring-indigo-500 focus:border-indigo-500"
                                @keydown.enter.prevent="addReferenceImage"
                            />
                            <button type="button" @click="addReferenceImage" class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700">Add</button>
                            <button type="button" @click="showRefInput = false" class="px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 text-xs font-semibold">Cancel</button>
                        </div>
                        <button
                            v-else
                            type="button"
                            @click="showRefInput = true"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700"
                        >
                            <Plus class="w-3.5 h-3.5" />
                            <span>Add Reference Image URL</span>
                        </button>
                    </div>
                </div>

                <!-- Submit row -->
                <div class="pt-2 flex items-center justify-between">
                    <span
                        v-if="!canAfford"
                        class="text-rose-600 dark:text-rose-400 font-semibold flex items-center gap-1 text-xs"
                    >
                        <AlertCircle class="w-4 h-4 shrink-0" />
                        Insufficient credits to submit
                    </span>
                    <span v-else class="text-emerald-600 dark:text-emerald-400 flex items-center gap-1 text-xs">
                        <CheckCircle2 class="w-4 h-4 shrink-0" />
                        Balance is sufficient
                    </span>

                    <PrimaryButton
                        type="submit"
                        :disabled="form.processing || !canAfford || !form.prompt.trim()"
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-sm font-semibold rounded-xl shadow-sm"
                    >
                        <Loader2 v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                        <Sparkles v-else class="w-4 h-4 mr-2" />
                        <span>Generate Image</span>
                    </PrimaryButton>
                </div>
            </form>
        </div>

        <!-- Estimate sidebar -->
        <div class="lg:col-span-5">
            <EstimatePanel
                :estimate-data="estimateData"
                :quality="form.quality"
                :size="form.size"
                :is-loading="isEstimating"
            />
        </div>
    </div>
</template>
