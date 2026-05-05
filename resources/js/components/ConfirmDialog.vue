<script setup lang="ts">
import { ref } from 'vue';

const props = withDefaults(
    defineProps<{
        title?: string;
        message?: string;
        confirmText?: string;
        cancelText?: string;
        variant?: 'danger' | 'warning' | 'info';
    }>(),
    {
        title: 'Konfirmasi',
        message: 'Apakah Anda yakin ingin melanjutkan?',
        confirmText: 'Ya, Lanjutkan',
        cancelText: 'Batal',
        variant: 'danger',
    },
);

const isOpen = ref(false);
let resolvePromise: ((value: boolean) => void) | null = null;

const open = (): Promise<boolean> => {
    isOpen.value = true;

    return new Promise((resolve) => {
        resolvePromise = resolve;
    });
};

const confirm = () => {
    isOpen.value = false;
    resolvePromise?.(true);
    resolvePromise = null;
};

const cancel = () => {
    isOpen.value = false;
    resolvePromise?.(false);
    resolvePromise = null;
};

defineExpose({ open });
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="isOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <!-- Overlay -->
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="cancel" />

                <!-- Dialog -->
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-2"
                >
                    <div
                        v-if="isOpen"
                        class="relative bg-surface-container-lowest rounded-2xl shadow-2xl border border-surface-container-highest w-full max-w-md overflow-hidden"
                    >
                        <!-- Header -->
                        <div class="p-6 pb-2">
                            <div class="flex items-start gap-4">
                                <div
                                    class="w-10 h-10 rounded-full flex items-center justify-center shrink-0"
                                    :class="{
                                        'bg-error-container text-on-error-container': variant === 'danger',
                                        'bg-tertiary-container text-on-tertiary-container': variant === 'warning',
                                        'bg-primary-container text-on-primary-container': variant === 'info',
                                    }"
                                >
                                    <span class="material-symbols-outlined text-[22px]">
                                        {{ variant === 'danger' ? 'warning' : variant === 'warning' ? 'help' : 'info' }}
                                    </span>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-on-surface text-lg leading-tight">{{ title }}</h3>
                                    <p class="text-on-surface-variant text-sm mt-1 leading-relaxed">{{ message }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3 px-6 py-4">
                            <button
                                type="button"
                                class="px-4 py-2 rounded-lg text-sm font-medium text-on-surface-variant bg-surface-container hover:bg-surface-container-high transition-colors"
                                @click="cancel"
                            >
                                {{ cancelText }}
                            </button>
                            <button
                                type="button"
                                class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                                :class="{
                                    'bg-error text-on-error hover:bg-error/90': variant === 'danger',
                                    'bg-tertiary text-on-tertiary hover:bg-tertiary/90': variant === 'warning',
                                    'bg-primary text-on-primary hover:bg-primary/90': variant === 'info',
                                }"
                                @click="confirm"
                            >
                                {{ confirmText }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
