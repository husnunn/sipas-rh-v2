<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import AppHeader from '@/components/AppHeader.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import { Toaster } from '@/components/ui/sonner';
import { useAppSidebar } from '@/composables/useAppSidebar';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const { isCollapsed, isMobileOpen, closeMobile } = useAppSidebar();

const mainColumnClass = computed(() => [
    'flex min-h-screen flex-1 flex-col transition-[margin] duration-300 ease-out',
    'ml-0',
    isCollapsed.value ? 'lg:ml-[72px]' : 'lg:ml-[260px]',
]);

watch(
    () => page.url,
    () => {
        closeMobile();
    },
);
</script>

<template>
    <div class="min-h-screen bg-background font-body-md text-on-background">
        <div
            v-show="isMobileOpen"
            class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm lg:hidden"
            aria-hidden="true"
            @click="closeMobile"
        />

        <AppSidebar />

        <div :class="mainColumnClass">
            <AppHeader :breadcrumbs="breadcrumbs" />

            <main class="flex-1 overflow-x-hidden p-container-padding pb-24">
                <slot />
            </main>
        </div>
        <Toaster />
    </div>
</template>
