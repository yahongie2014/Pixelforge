/**
 * useEstimate — composable for real-time Pixelforge cost estimation.
 * Debounces calls to GET /generations/estimate and exposes reactive state.
 */
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

export function useEstimate() {
    const page = usePage();

    const isEstimating = ref(false);
    const estimateData = ref(null);
    const canAfford    = ref(true);

    let debounceTimer = null;

    /**
     * Trigger a debounced estimate fetch.
     * @param {{ prompt: string, size: string, quality: string, reference_images: string[] }} params
     */
    function fetchEstimate(params) {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(async () => {
            isEstimating.value = true;
            try {
                const res = await axios.get(route('generations.estimate'), { params });
                estimateData.value = res.data.estimate;
                canAfford.value    = res.data.can_afford;
                // Sync balance shown in nav
                if (res.data.user_balance && page.props.auth?.user) {
                    page.props.auth.user.balance = res.data.user_balance;
                }
            } catch (e) {
                console.error('[useEstimate] fetch failed', e);
            } finally {
                isEstimating.value = false;
            }
        }, 250);
    }

    return { isEstimating, estimateData, canAfford, fetchEstimate };
}
