<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { edit, index } from '@/actions/App/Http/Controllers/Admin/StudentController';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { StudentProfile } from '@/types/models';

defineOptions({ layout: AppSidebarLayout });

defineProps<{ student: StudentProfile }>();
</script>

<template>
    <Head title="Detail Siswa" />
    <div class="max-w-3xl mx-auto bg-white dark:bg-slate-900 rounded-xl shadow p-6 space-y-4">
        <h1 class="text-xl font-semibold">Detail Siswa</h1>
        <p><strong>Nama:</strong> {{ student.user?.name }}</p>
        <p><strong>Username:</strong> {{ student.user?.username }}</p>
        <p><strong>NIS:</strong> {{ student.nis }}</p>
        <p><strong>NISN:</strong> {{ student.nisn || '-' }}</p>
        <p><strong>Kelas:</strong> {{ student.classes?.[0]?.name || '-' }}</p>
        <div class="flex gap-2 justify-end">
            <Link :href="index().url" class="px-4 py-2 rounded-lg border">Kembali</Link>
            <Link :href="edit(student).url" class="px-4 py-2 rounded-lg bg-emerald-700 text-white">Edit</Link>
        </div>
    </div>
</template>
