/**
 * useGenerationPoller — composable that polls non-terminal generations
 * every 3 seconds and patches the reactive list in place.
 */
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

/**
 * @param {import('vue').Ref<Object>} generationsPaginator  — the Inertia paginator prop (reactive)
 */
export function useGenerationPoller(generationsPaginator) {
    const page = usePage();

    const activeGenerations = computed(() =>
        (generationsPaginator.value?.data || []).filter(
            g => g.status === 'queued' || g.status === 'processing'
        )
    );

    let interval = null;

    async function poll() {
        if (activeGenerations.value.length === 0) return;

        for (const gen of activeGenerations.value) {
            try {
                const { data } = await axios.get(route('generations.status', gen.id));
                if (data?.generation) {
                    const idx = generationsPaginator.value.data.findIndex(g => g.id === gen.id);
                    if (idx !== -1) {
                        generationsPaginator.value.data[idx] = {
                            ...generationsPaginator.value.data[idx],
                            ...data.generation,
                        };
                    }
                    if (data.user_balance && page.props.auth?.user) {
                        page.props.auth.user.balance = data.user_balance;
                    }
                }
            } catch {
                // silent — network hiccup during polling is non-fatal
            }
        }
    }

    onMounted(() => { interval = setInterval(poll, 3000); });
    onUnmounted(() => { clearInterval(interval); });

    return { activeGenerations };
}
