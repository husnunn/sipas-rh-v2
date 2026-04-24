<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { index as adminDashboard } from '@/actions/App/Http/Controllers/Admin/DashboardController';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: adminDashboard().url,
            },
        ],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 items-center justify-center p-4">
        <p class="text-muted-foreground">Dashboard telah dipindah ke <a :href="adminDashboard().url" class="underline">Admin Dashboard</a>.</p>
    </div>
</template>
