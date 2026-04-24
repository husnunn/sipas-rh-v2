<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { edit, index } from '@/actions/App/Http/Controllers/Admin/ScheduleController';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { Schedule } from '@/types/models';

defineOptions({ layout: AppSidebarLayout });
defineProps<{ schedule: Schedule; days: Record<string, string> }>();
</script>

<template>
    <Head title="Detail Jadwal" />
    <div class="max-w-3xl mx-auto bg-white dark:bg-slate-900 rounded-xl shadow p-6 space-y-4">
        <h1 class="text-xl font-semibold">Detail Jadwal</h1>
        <p><strong>Hari:</strong> {{ days[schedule.day_of_week] ?? schedule.day_of_week }}</p>
        <p><strong>Waktu:</strong> {{ schedule.start_time }} - {{ schedule.end_time }}</p>
        <p><strong>Kelas:</strong> {{ schedule.class_room?.name || '-' }}</p>
        <p><strong>Mata Pelajaran:</strong> {{ schedule.subject?.name || '-' }}</p>
        <p><strong>Guru:</strong> {{ schedule.teacher_profile?.full_name || '-' }}</p>
        <div class="flex gap-2 justify-end">
            <Link :href="index().url" class="px-4 py-2 rounded-lg border">Kembali</Link>
            <Link :href="edit(schedule).url" class="px-4 py-2 rounded-lg bg-emerald-700 text-white">Edit</Link>
        </div>
    </div>
</template>
