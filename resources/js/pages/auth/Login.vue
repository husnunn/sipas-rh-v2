<script setup lang="ts">
import { Form, Head, useForm, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Spinner } from '@/components/ui/spinner';
import { register, login } from '@/routes';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Admin Portal',
        description: 'Sign in to manage academic operations.',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();

const form = useForm({
    username: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(login().url, {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <form @submit.prevent="submit" class="flex flex-col gap-stack-md mt-2">
        <!-- Username/Email Input Group -->
        <div class="flex flex-col gap-unit">
            <label class="text-label-sm font-label-sm text-on-surface" for="identifier">Username or Email</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="material-symbols-outlined text-[18px] text-outline">mail</span>
                </div>
                <input
                    v-model="form.username"
                    class="w-full h-[44px] pl-10 pr-4 rounded-xl border border-outline-variant bg-surface-container-lowest text-body-md font-body-md text-on-surface placeholder:text-outline/70 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200"
                    id="identifier" name="identifier" placeholder="admin Atau admin@rhschool.edu" required autofocus
                    type="text" />
            </div>
            <InputError :message="form.errors.username" />
        </div>

        <!-- Password Input Group -->
        <div class="flex flex-col gap-unit">
            <div class="flex justify-between items-center">
                <label class="text-label-sm font-label-sm text-on-surface" for="password">Password</label>
                <Link
                    v-if="canResetPassword"
                    :href="request()"
                    class="text-label-sm font-label-sm text-primary hover:text-primary-container transition-colors"
                >
                    Forgot?
                </Link>
            </div>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="material-symbols-outlined text-[18px] text-outline">lock</span>
                </div>
                <input
                    v-model="form.password"
                    class="w-full h-[44px] pl-10 pr-10 rounded-xl border border-outline-variant bg-surface-container-lowest text-body-md font-body-md text-on-surface placeholder:text-outline/70 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200"
                    id="password" name="password" placeholder="••••••••" required type="password" />
            </div>
            <InputError :message="form.errors.password" />
        </div>

        <!-- Remember me -->
        <div class="flex items-center justify-between">
            <label for="remember" class="flex items-center space-x-2 text-sm text-on-surface cursor-pointer">
                <input id="remember" name="remember" v-model="form.remember" type="checkbox" class="rounded border-outline-variant text-primary focus:ring-primary">
                <span>Remember me</span>
            </label>
        </div>

        <!-- Action Button -->
        <button
            :disabled="form.processing"
            class="mt-stack-sm w-full h-[44px] bg-primary text-on-primary rounded-xl text-body-md font-body-md font-semibold hover:bg-primary-container focus:ring-2 focus:ring-offset-2 focus:ring-offset-surface-container-lowest focus:ring-primary transition-all duration-200 flex items-center justify-center gap-2 shadow-[0_2px_4px_rgba(0,53,39,0.15)] hover:shadow-[0_4px_8px_rgba(0,53,39,0.2)] disabled:opacity-50"
            type="submit">
            <Spinner v-if="form.processing" class="h-4 w-4 mr-2 border-on-primary" />
            <span>Login to Dashboard</span>
            <span class="material-symbols-outlined text-[18px]"
                style="font-variation-settings: 'wght' 600;">arrow_forward</span>
        </button>
        
        <div class="text-center text-sm text-on-surface-variant mt-2" v-if="canRegister">
            Don't have an account?
            <Link :href="register()" class="text-primary hover:underline font-medium">Sign up</Link>
        </div>
    </form>
</template>
