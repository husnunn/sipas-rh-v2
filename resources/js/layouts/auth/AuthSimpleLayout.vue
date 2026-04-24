<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="bg-background min-h-screen flex flex-col justify-center items-center p-stack-lg relative overflow-hidden">
        <!-- Decorative Ambient Background Elements -->
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-primary-container opacity-[0.03] rounded-full blur-[100px] pointer-events-none"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] bg-secondary-container opacity-[0.04] rounded-full blur-[120px] pointer-events-none"></div>
        
        <!-- Main Content Container -->
        <main class="w-full max-w-[420px] z-10">
            <!-- Login Card -->
            <div class="bg-surface-container-lowest rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.04)] border border-surface-variant/50 p-container-padding flex flex-col gap-stack-lg relative overflow-hidden">
                <!-- Subtle top accent line -->
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-primary to-secondary"></div>
                
                <!-- Header Section -->
                <div class="text-center flex flex-col items-center gap-stack-sm mt-stack-sm">
                    <!-- Logo -->
                    <div class="w-14 h-14 bg-surface-container-low border border-surface-variant rounded-xl flex items-center justify-center text-primary mb-2 shadow-sm">
                        <img alt="Robithotul Hikmah Logo" class="w-8 h-8 object-contain mix-blend-multiply opacity-90"
                            src="https://lh3.googleusercontent.com/aida-public/AB6AXuCp7aCgiI7Z9gHiATLXRUUdAQiYRyL4bnJtAfy6ET-Sny1Wo-a_OftMqxTMt9sC6tXSRHIMQzuAAXc0zl2OUqhkm_Rheut23c24SZSuQGWMdKdEtd0kyKC3aTy1IxY9Zq98NELpiqIylxZVk1Z86cIWYr-lXpBfVbmoXDoSPaMzBwWzal8AI2ZXetxIFNz25P_FUGqPFsJOCYTJgdBb_ET-FWLsYYATYeMGGeO6Dvz9duh8z4O1JjxHYyCzRNIu4lOjSRWn0uPgaVg" />
                    </div>
                    <h1 class="text-h2 font-h2 text-on-surface">{{ title || 'Admin Portal' }}</h1>
                    <p class="text-body-md font-body-md text-on-surface-variant">{{ description || 'Sign in to manage academic operations.' }}</p>
                </div>
                
                <slot />
                
                <!-- Optional Security Indicator -->
                <div class="flex items-center justify-center gap-2 mt-2 text-outline text-[11px] font-body-md">
                    <span class="material-symbols-outlined text-[14px]">lock_person</span>
                    <span>Secure SSL Encrypted Connection</span>
                </div>
            </div>
        </main>
        
        <!-- Subtle Footer -->
        <footer class="mt-stack-lg text-center z-10 flex flex-col gap-1 text-outline">
            <p class="text-body-md font-body-md">© 2024 Robithotul Hikmah Jombang.</p>
            <p class="text-[12px] font-body-md opacity-80">Academic Administration System v2.1</p>
        </footer>
    </div>
</template>
