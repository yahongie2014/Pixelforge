<script setup>
import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Coins, Sparkles, CheckCircle2, ShieldCheck } from 'lucide-vue-next';
import { CreditPurchaseTier } from '@/constants/enums.js';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['close']);

const page = usePage();
const selectedAmount = ref(10);
const options = CreditPurchaseTier;

const form = useForm({
    amount: 10,
});

const submit = () => {
    form.amount = selectedAmount.value;
    form.post(route('credits.buy'), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
        },
    });
};
</script>

<template>
    <Modal :show="show" @close="emit('close')" max-width="md">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                    <Coins class="w-6 h-6" />
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                        Buy Image Credits
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Top up your PromptEdit balance to generate high-resolution AI images.
                    </p>
                </div>
            </div>

            <!-- Current balance info -->
            <div class="bg-gray-50 dark:bg-gray-800/60 rounded-xl p-3 mb-5 border border-gray-200 dark:border-gray-700/60 flex items-center justify-between">
                <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Current Balance:</span>
                <span class="text-sm font-semibold font-mono text-emerald-600 dark:text-emerald-400">
                    ${{ Number(page.props.auth.user.balance || 0).toFixed(4) }} USD
                </span>
            </div>

            <!-- Preset options -->
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                Select Credit Amount
            </label>
            <div class="grid grid-cols-3 gap-2.5 mb-5">
                <button
                    v-for="amt in options"
                    :key="amt"
                    type="button"
                    @click="selectedAmount = amt"
                    :class="[
                        'relative flex flex-col items-center justify-center p-3 rounded-xl border text-center transition-all',
                        selectedAmount === amt
                            ? 'border-indigo-600 dark:border-indigo-500 bg-indigo-50/60 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 ring-2 ring-indigo-500/30 font-semibold'
                            : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 text-gray-700 dark:text-gray-300'
                    ]"
                >
                    <span class="text-lg font-bold font-mono">${{ amt }}</span>
                    <span class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">credits</span>
                    <span v-if="amt === 25" class="absolute -top-2 -right-1 bg-amber-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full uppercase tracking-wider shadow-sm">
                        Best
                    </span>
                </button>
            </div>

            <!-- Notice box -->
            <div class="flex items-start gap-2.5 p-3 rounded-lg bg-blue-50/70 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/40 text-blue-800 dark:text-blue-300 text-xs mb-6">
                <ShieldCheck class="w-4 h-4 shrink-0 mt-0.5 text-blue-600 dark:text-blue-400" />
                <span>
                    <strong>Simulated Purchase:</strong> Assessment testing environment. Credits are instantly added to your account without requiring actual payment details.
                </span>
            </div>

            <div class="flex justify-end gap-3">
                <SecondaryButton @click="emit('close')" :disabled="form.processing">
                    Cancel
                </SecondaryButton>

                <PrimaryButton
                    @click="submit"
                    :disabled="form.processing"
                    class="bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800"
                >
                    <Coins class="w-4 h-4 mr-1.5" />
                    <span>Add ${{ selectedAmount }} Credits</span>
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>
