<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { edit, index } from '@/actions/App/Http/Controllers/Admin/SchoolYearController';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { SchoolYear } from '@/types/models';

defineOptions({ layout: AppSidebarLayout });

const props = defineProps<{
    schoolYear: SchoolYear;
}>();

const dateFormatter = new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
});

function formatDate(value?: string | null): string {
    if (!value) {
        return '—';
    }

    const date = new Date(`${value}T00:00:00`);
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return dateFormatter.format(date);
}
</script>

<template>
    <Head title="Detail Tahun Ajaran" />

    <div class="flex flex-col gap-stack-lg max-w-3xl mx-auto w-full">
        <div class="flex items-center justify-between">
            <Link
                :href="index().url"
                class="flex w-fit items-center gap-1 text-sm font-medium text-on-surface-variant transition-colors hover:text-primary"
            >
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                Kembali
            </Link>
            <Link
                :href="edit(schoolYear).url"
                class="rounded-lg border border-outline px-4 py-2 font-label-sm text-label-sm text-on-surface transition-colors hover:bg-surface-container"
            >
                Edit
            </Link>
        </div>

        <div class="bg-surface-container-lowest rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest overflow-hidden p-6 md:p-8">
            <h2 class="font-h2 text-h2 text-on-surface">{{ schoolYear.name }}</h2>
            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-lg border border-outline-variant bg-surface px-4 py-3">
                    <div class="text-xs text-on-surface-variant">Mulai</div>
                    <div class="mt-1 font-medium text-on-surface">{{ formatDate(schoolYear.start_date) }}</div>
                </div>
                <div class="rounded-lg border border-outline-variant bg-surface px-4 py-3">
                    <div class="text-xs text-on-surface-variant">Selesai</div>
                    <div class="mt-1 font-medium text-on-surface">{{ formatDate(schoolYear.end_date) }}</div>
                </div>
                <div class="rounded-lg border border-outline-variant bg-surface px-4 py-3">
                    <div class="text-xs text-on-surface-variant">Status</div>
                    <div class="mt-1 font-medium" :class="schoolYear.is_active ? 'text-primary' : 'text-on-surface'">
                        {{ schoolYear.is_active ? 'Aktif' : 'Nonaktif' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

