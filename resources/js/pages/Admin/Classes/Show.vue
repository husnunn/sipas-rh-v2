<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { edit, index } from '@/actions/App/Http/Controllers/Admin/ClassRoomController';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { ClassRoom } from '@/types/models';

defineOptions({ layout: AppSidebarLayout });
defineProps<{ classRoom: ClassRoom }>();
</script>

<template>
    <Head title="Detail Kelas" />
    <div class="max-w-3xl mx-auto bg-white dark:bg-slate-900 rounded-xl shadow p-6 space-y-4">
        <h1 class="text-xl font-semibold">Detail Kelas</h1>
        <p><strong>Nama:</strong> {{ classRoom.name }}</p>
        <p><strong>Level:</strong> {{ classRoom.level }}</p>
        <p><strong>Tahun Ajaran:</strong> {{ classRoom.school_year?.name || '-' }}</p>
        <p><strong>Wali Kelas:</strong> {{ classRoom.homeroom_teacher?.user?.name || '-' }}</p>
        <div class="flex gap-2 justify-end">
            <Link :href="index().url" class="px-4 py-2 rounded-lg border">Kembali</Link>
            <Link :href="edit(classRoom).url" class="px-4 py-2 rounded-lg bg-emerald-700 text-white">Edit</Link>
        </div>
    </div>
</template>
