<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import AppHeader from '@/components/AppHeader.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <div class="bg-background text-on-background font-body-md min-h-screen flex">
        <AppSidebar />
        
        <!-- Main Content Canvas -->
        <div class="flex-1 ml-0 lg:ml-[260px] flex flex-col min-h-screen transition-all duration-300">
            <AppHeader :breadcrumbs="breadcrumbs" />
            
            <!-- Main Workspace -->
            <main class="flex-1 p-container-padding pb-24 overflow-x-hidden">
                <slot />
            </main>
        </div>
        <Toaster />
    </div>
</template>
