<script setup>
/**
 * Admin/Report.vue — Operator financial & reconciliation report.
 *
 * Uses shared UI components (SectionCard, DataTable, StatCard) and
 * the central format utilities — no inline formatter duplication.
 */
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SectionCard  from '@/Components/UI/SectionCard.vue';
import DataTable    from '@/Components/UI/DataTable.vue';
import StatCard     from '@/Components/UI/StatCard.vue';
import {
    DollarSign, Coins, TrendingUp, CheckCircle2, AlertTriangle,
    Users, Filter,
} from 'lucide-vue-next';
import { formatUSD, formatUSDExact } from '@/utils/format.js';

const props = defineProps({
    dailyReport: Array,
    userReport:  Array,
    summary:     Object,
    filters:     Object,
});

const startDate = ref(props.filters.start_date);
const endDate   = ref(props.filters.end_date);

function applyFilter() {
    router.get(route('admin.report'), { start_date: startDate.value, end_date: endDate.value }, { preserveState: true, preserveScroll: true });
}

function setPreset(days) {
    const end   = new Date();
    const start = new Date();
    start.setDate(end.getDate() - (days - 1));
    startDate.value = start.toISOString().split('T')[0];
    endDate.value   = end.toISOString().split('T')[0];
    applyFilter();
}
</script>

<template>
    <Head title="Admin Financial Report" />

    <AuthenticatedLayout>
        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

                <!-- Page header -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-gray-200 dark:border-gray-700 pb-5">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                Operator Portal
                            </span>
                            <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white">Financial &amp; Provider Reconciliation</h1>
                        </div>
                        <p class="text-xs text-gray-500">
                            Revenue, Pixelforge cost reconciliation against
                            <code class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 font-mono">GET /v1/usage</code>,
                            and profit margins.
                        </p>
                    </div>

                    <!-- Date filter -->
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="flex items-center gap-1.5 bg-white dark:bg-gray-800 p-1 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs">
                            <input v-model="startDate" type="date" class="border-0 bg-transparent text-xs p-1.5 focus:ring-0 text-gray-700 dark:text-gray-300 font-mono" />
                            <span class="text-gray-400 text-xs">to</span>
                            <input v-model="endDate"   type="date" class="border-0 bg-transparent text-xs p-1.5 focus:ring-0 text-gray-700 dark:text-gray-300 font-mono" />
                            <button @click="applyFilter" class="p-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition" title="Apply">
                                <Filter class="w-3.5 h-3.5" />
                            </button>
                        </div>
                        <div class="flex items-center gap-1">
                            <button v-for="d in [7, 30, 90]" :key="d" @click="setPreset(d)"
                                class="px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                {{ d }}d
                            </button>
                        </div>
                    </div>
                </div>

                <!-- KPI cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <StatCard label="Credit Spend" :value="formatUSD(summary.total_spend, 4)" sub="Total charged to users (cost + 25%)" color="indigo">
                        <template #icon><DollarSign class="w-4 h-4" /></template>
                    </StatCard>

                    <StatCard label="Provider Cost" :value="formatUSD(summary.total_provider_cost, 4)" sub="Pixelforge unrounded base charges" color="amber">
                        <template #icon><Coins class="w-4 h-4" /></template>
                    </StatCard>

                    <StatCard label="Gross Profit" :value="formatUSD(summary.total_profit, 4)" :sub="`${summary.global_margin}% retail margin`" color="emerald">
                        <template #icon><TrendingUp class="w-4 h-4" /></template>
                    </StatCard>

                    <StatCard
                        label="Reconciliation"
                        :value="summary.is_reconciled ? '100% Reconciled' : 'Discrepancy'"
                        sub="Matches Pixelforge GET /v1/usage"
                        :color="summary.is_reconciled ? 'emerald' : 'rose'"
                    >
                        <template #icon>
                            <component :is="summary.is_reconciled ? CheckCircle2 : AlertTriangle" class="w-4 h-4" />
                        </template>
                    </StatCard>
                </div>

                <!-- Daily breakdown table -->
                <SectionCard
                    title="Daily Breakdown & API Reconciliation"
                    sub="Compared day-by-day in UTC (Pixelforge bills by completion date)."
                >
                    <template #action>
                        <span class="text-[11px] text-gray-500 font-mono">{{ dailyReport.length }} Days</span>
                    </template>

                    <DataTable>
                        <template #head>
                            <th class="py-3 px-4">Date (UTC)</th>
                            <th class="py-3 px-4 text-center">Generations</th>
                            <th class="py-3 px-4">Credit Spend</th>
                            <th class="py-3 px-4">Provider Cost</th>
                            <th class="py-3 px-4">Pixelforge API</th>
                            <th class="py-3 px-4 text-center">Audit</th>
                            <th class="py-3 px-4 text-right">Profit</th>
                            <th class="py-3 px-4 text-right">Margin</th>
                        </template>

                        <tr
                            v-for="row in dailyReport"
                            :key="row.date"
                            class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors"
                        >
                            <td class="py-3 px-4 font-mono font-medium text-gray-900 dark:text-gray-200">{{ row.date }}</td>
                            <td class="py-3 px-4 text-center font-mono">{{ row.generations }}</td>
                            <td class="py-3 px-4 font-mono font-semibold text-gray-900 dark:text-gray-100">{{ formatUSD(row.credit_spend, 4) }}</td>
                            <td class="py-3 px-4 font-mono text-gray-700 dark:text-gray-300" :title="formatUSDExact(row.provider_cost)">{{ formatUSD(row.provider_cost, 4) }}</td>
                            <td class="py-3 px-4 font-mono text-gray-600 dark:text-gray-400" :title="formatUSDExact(row.remote_provider_cost)">{{ formatUSD(row.remote_provider_cost, 4) }}</td>
                            <td class="py-3 px-4 text-center">
                                <span v-if="row.is_match" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                    <CheckCircle2 class="w-3 h-3" /> Exact Match
                                </span>
                                <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300" :title="`Diff: ${row.discrepancy}`">
                                    <AlertTriangle class="w-3 h-3" /> Diff
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-semibold text-emerald-600 dark:text-emerald-400">{{ formatUSD(row.profit, 4) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-gray-600 dark:text-gray-400">{{ row.margin_percent }}%</td>
                        </tr>
                    </DataTable>
                </SectionCard>

                <!-- Per-user table -->
                <SectionCard>
                    <template #title>
                        <Users class="w-4 h-4 text-indigo-500" />
                        Per-User Activity &amp; Spend
                    </template>
                    <template #sub>Granular user-level consumption, revenue contribution, and credit balances.</template>

                    <DataTable>
                        <template #head>
                            <th class="py-3 px-4">User</th>
                            <th class="py-3 px-4">Role</th>
                            <th class="py-3 px-4 text-center">Generations (OK / Fail)</th>
                            <th class="py-3 px-4">Credit Spend</th>
                            <th class="py-3 px-4">Provider Cost</th>
                            <th class="py-3 px-4">Profit</th>
                            <th class="py-3 px-4 text-right">Balance</th>
                        </template>

                        <tr
                            v-for="u in userReport"
                            :key="u.id"
                            class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors"
                        >
                            <td class="py-3 px-4">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ u.name }}</div>
                                <div class="text-[11px] text-gray-400 font-mono">{{ u.email }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <span v-if="u.is_admin" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300">Admin</span>
                                <span v-else class="text-gray-500 text-xs">User</span>
                            </td>
                            <td class="py-3 px-4 text-center font-mono">
                                <span class="text-emerald-600 dark:text-emerald-400 font-semibold">{{ u.succeeded_generations }}</span>
                                <span class="text-gray-400"> / </span>
                                <span class="text-rose-500">{{ u.failed_generations }}</span>
                                <span class="text-gray-400 text-[10px] ml-1">({{ u.total_generations }})</span>
                            </td>
                            <td class="py-3 px-4 font-mono font-semibold text-gray-900 dark:text-gray-100">{{ formatUSD(u.credit_spend, 4) }}</td>
                            <td class="py-3 px-4 font-mono text-gray-600 dark:text-gray-400">{{ formatUSD(u.provider_cost, 4) }}</td>
                            <td class="py-3 px-4 font-mono font-semibold text-emerald-600 dark:text-emerald-400">{{ formatUSD(u.profit, 4) }}</td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-gray-900 dark:text-gray-100">{{ formatUSD(u.balance, 4) }}</td>
                        </tr>
                    </DataTable>
                </SectionCard>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
