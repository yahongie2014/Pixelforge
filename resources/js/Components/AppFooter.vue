<script setup>
import { Globe } from 'lucide-vue-next';

defineProps({
    compact: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <footer
        class="border-t border-gray-200/80 bg-white/70 backdrop-blur-md dark:border-gray-800 dark:bg-gray-900/70 py-6 mt-auto transition-colors"
        :class="compact ? 'py-4' : 'py-6'"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-gray-500 dark:text-gray-400">
                <div class="flex items-center gap-2 text-center sm:text-left">
                    <span class="font-bold text-gray-800 dark:text-gray-200">Ahmed Saeed</span>
                    <span class="text-gray-300 dark:text-gray-600">&bull;</span>
                    <span>Senior Product Engineer Assessment</span>
                </div>

                <div class="flex items-center gap-5">
                    <!-- Personal Website -->
                    <a
                        href="https://coder79.me"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 font-medium hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors group"
                        title="Personal Website"
                    >
                        <Globe class="w-4 h-4 text-gray-400 group-hover:text-indigo-500 dark:group-hover:text-indigo-400 transition-colors" />
                        <span>coder79.me</span>
                    </a>

                    <!-- LinkedIn -->
                    <a
                        href="https://linkedin.com/in/devahmedsaeed"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 font-medium hover:text-[#0A66C2] transition-colors group"
                        title="LinkedIn Profile"
                    >
                        <svg class="w-4 h-4 fill-current text-gray-400 group-hover:text-[#0A66C2] transition-colors" viewBox="0 0 24 24">
                            <path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.28 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.75M6.46 8.76a1.64 1.64 0 1 0 0-3.28 1.64 1.64 0 0 0 0 3.28m1.39 9.74V9.93H5.07v8.57h2.78z"/>
                        </svg>
                        <span>LinkedIn</span>
                    </a>

                    <!-- GitHub Repository -->
                    <a
                        href="https://github.com/yahongie2014/Pixelforge"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 font-medium hover:text-gray-900 dark:hover:text-white transition-colors group"
                        title="GitHub Repository"
                    >
                        <svg class="w-4 h-4 fill-current text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"/>
                        </svg>
                        <span>GitHub</span>
                    </a>
                </div>
            </div>
        </div>
    </footer>
</template>
