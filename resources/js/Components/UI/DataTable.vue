<script setup>
/**
 * DataTable — slim wrapper for consistent table styles across the app.
 * Slot "head" = <tr> with <th> cells; default slot = <tr> rows.
 */
</script>

<template>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
            <thead>
                <tr class="bg-gray-50/80 dark:bg-gray-900/60 border-b border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 uppercase font-semibold text-[11px]">
                    <slot name="head" />
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200/60 dark:divide-gray-700/60">
                <slot />
            </tbody>
        </table>
    </div>
</template>
