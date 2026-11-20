<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const fillCredentials = (role) => {
    if (role === 'admin') {
        form.email = 'admin@promptedit.com';
        form.password = 'password';
    } else {
        form.email = 'user@promptedit.com';
        form.password = 'password';
    }
    form.clearErrors();
};

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <!-- Quick Auto-Fill Demo Credentials -->
        <div class="mb-5 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-800/60 shadow-sm">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2.5 flex items-center justify-between">
                <span class="flex items-center gap-1.5 text-gray-700 dark:text-gray-300 font-bold">
                    <svg class="w-3.5 h-3.5 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                    </svg>
                    Quick Autofill
                </span>
                <span class="text-[10px] lowercase text-gray-400">click to load account</span>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <!-- Admin Card -->
                <button
                    type="button"
                    @click="fillCredentials('admin')"
                    class="group relative flex flex-col items-start p-2.5 rounded-lg border text-left transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-offset-1"
                    :class="form.email === 'admin@promptedit.com'
                        ? 'border-indigo-500 bg-indigo-50/80 dark:bg-indigo-950/50 shadow-sm ring-1 ring-indigo-500/50'
                        : 'border-indigo-200/70 hover:border-indigo-400 dark:border-indigo-900/60 bg-white dark:bg-gray-900/50 hover:bg-indigo-50/40 dark:hover:bg-indigo-950/30'"
                >
                    <div class="flex items-center justify-between w-full">
                        <span class="text-xs font-bold text-indigo-700 dark:text-indigo-300 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                            Operator Admin
                        </span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded font-mono font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/60 dark:text-indigo-200">
                            admin
                        </span>
                    </div>
                    <span class="text-[11px] text-gray-600 dark:text-gray-400 font-mono mt-1 truncate w-full">admin@promptedit.com</span>
                    <span class="text-[10px] text-indigo-600/90 dark:text-indigo-400 mt-0.5">Admin report & full metrics</span>
                </button>

                <!-- User Card -->
                <button
                    type="button"
                    @click="fillCredentials('user')"
                    class="group relative flex flex-col items-start p-2.5 rounded-lg border text-left transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-offset-1"
                    :class="form.email === 'user@promptedit.com'
                        ? 'border-emerald-500 bg-emerald-50/80 dark:bg-emerald-950/50 shadow-sm ring-1 ring-emerald-500/50'
                        : 'border-emerald-200/70 hover:border-emerald-400 dark:border-emerald-900/60 bg-white dark:bg-gray-900/50 hover:bg-emerald-50/40 dark:hover:bg-emerald-950/30'"
                >
                    <div class="flex items-center justify-between w-full">
                        <span class="text-xs font-bold text-emerald-700 dark:text-emerald-300 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Standard User
                        </span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded font-mono font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-200">
                            user
                        </span>
                    </div>
                    <span class="text-[11px] text-gray-600 dark:text-gray-400 font-mono mt-1 truncate w-full">user@promptedit.com</span>
                    <span class="text-[10px] text-emerald-600/90 dark:text-emerald-400 mt-0.5">$15 balance · Studio</span>
                </button>
            </div>
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600 dark:text-gray-400"
                        >Remember me</span
                    >
                </label>
            </div>

            <div class="mt-4 flex items-center justify-end">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
                >
                    Forgot your password?
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Log in
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
