<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import BuyCreditsModal from '@/Components/BuyCreditsModal.vue';
import AppFooter from '@/Components/AppFooter.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Sparkles, Coins, Plus, BarChart3, CheckCircle, AlertTriangle, ShieldCheck } from 'lucide-vue-next';

const showingNavigationDropdown = ref(false);
const showBuyCreditsModal = ref(false);
const page = usePage();

const formatBalance = (val) => {
    const num = Number(val || 0);
    return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 4 });
};
</script>

<template>
    <div class="min-h-screen flex flex-col bg-gray-50 dark:bg-gray-900 font-sans antialiased text-gray-900 dark:text-gray-100">
        <!-- Navigation -->
            <nav class="sticky top-0 z-30 border-b border-gray-200/80 bg-white/90 backdrop-blur-md dark:border-gray-800 dark:bg-gray-800/90 shadow-xs">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between items-center">
                        <div class="flex items-center gap-8">
                            <!-- Logo & Brand -->
                            <Link :href="route('generations.index')" class="flex items-center gap-2.5 group">
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center text-white shadow-md shadow-indigo-500/20 group-hover:scale-105 transition-transform">
                                    <Sparkles class="w-5 h-5" />
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-extrabold tracking-tight text-gray-900 dark:text-white text-base leading-tight">
                                        PromptEdit
                                    </span>
                                    <span class="text-[10px] uppercase tracking-wider text-indigo-600 dark:text-indigo-400 font-bold">
                                        PixelForge Studio
                                    </span>
                                </div>
                            </Link>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-2 sm:flex">
                                <NavLink
                                    :href="route('generations.index')"
                                    :active="route().current('generations.*')"
                                    class="text-sm font-medium"
                                >
                                    Studio & History
                                </NavLink>

                                <NavLink
                                    v-if="$page.props.auth.user.is_admin"
                                    :href="route('admin.report')"
                                    :active="route().current('admin.report')"
                                    class="text-sm font-medium flex items-center gap-1.5"
                                >
                                    <BarChart3 class="w-4 h-4 text-emerald-500" />
                                    <span>Admin Report</span>
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                        Operator
                                    </span>
                                </NavLink>

                                <a
                                    v-if="$page.props.auth.user.is_admin"
                                    href="/telescope"
                                    target="_blank"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg text-indigo-600 dark:text-indigo-400 bg-indigo-50/80 dark:bg-indigo-950/40 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 border border-indigo-200 dark:border-indigo-800 transition"
                                    title="Open Laravel Telescope in new tab"
                                >
                                    <span>Telescope</span>
                                    <span class="text-[11px]">🔭</span>
                                </a>
                            </div>
                        </div>

                        <!-- Right Section: Balance & User Menu -->
                        <div class="hidden sm:flex sm:items-center sm:gap-4">
                            <!-- Credit Balance Pill -->
                            <div class="flex items-center gap-2 bg-gray-100/80 dark:bg-gray-700/60 pl-3 pr-1.5 py-1 rounded-full border border-gray-200 dark:border-gray-700 shadow-2xs">
                                <div class="flex items-center gap-1.5">
                                    <Coins class="w-4 h-4 text-amber-500 shrink-0" />
                                    <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Credits:</span>
                                    <span class="text-sm font-bold font-mono text-gray-900 dark:text-gray-100">
                                        ${{ formatBalance($page.props.auth.user.balance) }}
                                    </span>
                                </div>

                                <button
                                    type="button"
                                    @click="showBuyCreditsModal = true"
                                    class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full bg-indigo-600 hover:bg-indigo-700 text-white shadow-2xs transition-all hover:scale-105 active:scale-95"
                                    title="Add credits to balance"
                                >
                                    <Plus class="w-3.5 h-3.5" />
                                    <span>Add</span>
                                </button>
                            </div>

                            <!-- Settings Dropdown -->
                            <div class="relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition"
                                        >
                                            <div class="w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 flex items-center justify-center text-xs font-bold uppercase">
                                                {{ $page.props.auth.user.name.charAt(0) }}
                                            </div>
                                            <span class="max-w-[120px] truncate">{{ $page.props.auth.user.name }}</span>
                                            <svg class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </template>

                                    <template #content>
                                        <div class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                            Signed in as <br>
                                            <span class="font-medium text-gray-900 dark:text-gray-200 truncate block">{{ $page.props.auth.user.email }}</span>
                                        </div>

                                        <DropdownLink :href="route('generations.index')">
                                            Studio & Generations
                                        </DropdownLink>

                                        <DropdownLink v-if="$page.props.auth.user.is_admin" :href="route('admin.report')">
                                            Admin Report
                                        </DropdownLink>

                                        <a
                                            v-if="$page.props.auth.user.is_admin"
                                            href="/telescope"
                                            target="_blank"
                                            class="block w-full px-4 py-2 text-start text-sm leading-5 text-indigo-600 dark:text-indigo-400 hover:bg-gray-100 dark:hover:bg-gray-850 font-medium transition duration-150 ease-in-out"
                                        >
                                            Laravel Telescope 🔭
                                        </a>

                                        <DropdownLink :href="route('profile.edit')">
                                            Profile Settings
                                        </DropdownLink>

                                        <div class="border-t border-gray-100 dark:border-gray-700"></div>

                                        <DropdownLink :href="route('logout')" method="post" as="button">
                                            Log Out
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Mobile Hamburger Button -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path
                                        :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink :href="route('generations.index')" :active="route().current('generations.*')">
                            Studio & History
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="$page.props.auth.user.is_admin" :href="route('admin.report')" :active="route().current('admin.report')">
                            Admin Report
                        </ResponsiveNavLink>
                    </div>

                    <div class="border-t border-gray-200 pb-3 pt-4 dark:border-gray-600 px-4">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <div class="text-base font-semibold text-gray-800 dark:text-gray-200">{{ $page.props.auth.user.name }}</div>
                                <div class="text-sm text-gray-500">{{ $page.props.auth.user.email }}</div>
                            </div>
                            <button
                                @click="showBuyCreditsModal = true; showingNavigationDropdown = false"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-semibold"
                            >
                                <Plus class="w-3.5 h-3.5" />
                                <span>Buy Credits</span>
                            </button>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')"> Profile </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button"> Log Out </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Flash Notifications -->
            <div v-if="$page.props.flash?.success || $page.props.flash?.error" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                <div
                    v-if="$page.props.flash?.success"
                    class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 flex items-center justify-between shadow-xs"
                >
                    <div class="flex items-center gap-2.5">
                        <CheckCircle class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                        <span class="text-sm font-medium">{{ $page.props.flash.success }}</span>
                    </div>
                </div>

                <div
                    v-if="$page.props.flash?.error"
                    class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 flex items-center justify-between shadow-xs"
                >
                    <div class="flex items-center gap-2.5">
                        <AlertTriangle class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0" />
                        <span class="text-sm font-medium">{{ $page.props.flash.error }}</span>
                    </div>
                </div>
            </div>

            <!-- Page Heading (optional) -->
            <header v-if="$slots.header" class="bg-white dark:bg-gray-800 shadow-2xs border-b border-gray-200/60 dark:border-gray-700/60">
                <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1">
                <slot />
            </main>

            <!-- Application Footer -->
            <AppFooter />

            <!-- Buy Credits Modal -->
            <BuyCreditsModal
                :show="showBuyCreditsModal"
                @close="showBuyCreditsModal = false"
            />
        </div>
</template>
