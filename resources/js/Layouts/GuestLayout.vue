<script setup>
import { Link } from '@inertiajs/vue3';
import { Sparkles } from 'lucide-vue-next';
import AppFooter from '@/Components/AppFooter.vue';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center justify-between bg-gray-50 pt-8 dark:bg-gray-950 font-sans text-gray-900 dark:text-gray-100"
    >
        <div class="w-full flex flex-col items-center my-auto py-6">
            <div class="mb-2">
                <Link :href="route('generations.index')" class="flex flex-col items-center gap-3 group">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-violet-500 flex items-center justify-center text-white shadow-xl shadow-indigo-500/25 group-hover:scale-105 transition-transform duration-200">
                        <Sparkles class="w-8 h-8" />
                    </div>
                    <div class="flex flex-col items-center text-center">
                        <span class="font-extrabold tracking-tight text-gray-900 dark:text-white text-2xl leading-tight">
                            PromptEdit
                        </span>
                        <span class="text-xs uppercase tracking-widest text-indigo-600 dark:text-indigo-400 font-bold mt-0.5">
                            PixelForge Studio
                        </span>
                    </div>
                </Link>
            </div>

            <div
                class="mt-4 w-full overflow-hidden bg-white px-8 py-6 shadow-xl ring-1 ring-gray-900/5 sm:max-w-md sm:rounded-2xl dark:bg-gray-900 dark:ring-white/10"
            >
                <slot />
            </div>
        </div>

        <AppFooter :compact="true" class="w-full" />
    </div>
</template>
