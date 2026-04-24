<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { edit, index } from '@/actions/App/Http/Controllers/Admin/TeacherController';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { TeacherProfile } from '@/types/models';

defineOptions({ layout: AppSidebarLayout });
defineProps<{ teacher: TeacherProfile }>();
</script>

<template>
    <Head title="Detail Guru" />
    <div class="max-w-3xl mx-auto bg-white dark:bg-slate-900 rounded-xl shadow p-6 space-y-4">
        <h1 class="text-xl font-semibold">Detail Guru</h1>
        <p><strong>Nama:</strong> {{ teacher.full_name }}</p>
        <p><strong>NIP:</strong> {{ teacher.nip || '-' }}</p>
        <p><strong>Gender:</strong> {{ teacher.gender || '-' }}</p>
        <p><strong>Mata Pelajaran:</strong> {{ teacher.subjects?.map((item) => item.name).join(', ') || '-' }}</p>
        <div class="flex gap-2 justify-end">
            <Link :href="index().url" class="px-4 py-2 rounded-lg border">Kembali</Link>
            <Link :href="edit(teacher).url" class="px-4 py-2 rounded-lg bg-emerald-700 text-white">Edit</Link>
        </div>
    </div>
</template>
