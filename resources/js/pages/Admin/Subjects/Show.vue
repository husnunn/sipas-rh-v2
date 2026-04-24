<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { edit, index } from '@/actions/App/Http/Controllers/Admin/SubjectController';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { Subject } from '@/types/models';

defineOptions({ layout: AppSidebarLayout });
defineProps<{ subject: Subject }>();
</script>

<template>
    <Head title="Detail Mata Pelajaran" />
    <div class="max-w-3xl mx-auto bg-white dark:bg-slate-900 rounded-xl shadow p-6 space-y-4">
        <h1 class="text-xl font-semibold">Detail Mata Pelajaran</h1>
        <p><strong>Kode:</strong> {{ subject.code }}</p>
        <p><strong>Nama:</strong> {{ subject.name }}</p>
        <p><strong>Deskripsi:</strong> {{ subject.description || '-' }}</p>
        <p><strong>Status:</strong> {{ subject.is_active ? 'Aktif' : 'Nonaktif' }}</p>
        <div class="flex gap-2 justify-end">
            <Link :href="index().url" class="px-4 py-2 rounded-lg border">Kembali</Link>
            <Link :href="edit(subject).url" class="px-4 py-2 rounded-lg bg-emerald-700 text-white">Edit</Link>
        </div>
    </div>
</template>
