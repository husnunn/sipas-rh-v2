<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { store, update } from '@/actions/App/Http/Controllers/Admin/ClassRoomController';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { ClassRoom, SchoolYear, TeacherProfile } from '@/types/models';

defineOptions({ layout: AppSidebarLayout });

const props = defineProps<{
    mode: 'create' | 'edit';
    classRoom: ClassRoom | null;
    schoolYears: SchoolYear[];
    teachers: TeacherProfile[];
}>();

const form = useForm({
    school_year_id: props.classRoom?.school_year_id ?? '',
    name: props.classRoom?.name ?? '',
    level: props.classRoom?.level ?? 1,
    homeroom_teacher_id: props.classRoom?.homeroom_teacher_id ?? '',
    is_active: props.classRoom?.is_active ?? true,
});

const submit = () => {
    if (props.mode === 'create') {
        form.post(store().url);

        return;
    }

    if (props.classRoom) {
        form.put(update(props.classRoom).url);
    }
};
</script>

<template>
    <Head :title="mode === 'create' ? 'Tambah Kelas' : 'Edit Kelas'" />
    <div class="max-w-3xl mx-auto bg-white dark:bg-slate-900 rounded-xl shadow p-6">
        <h1 class="text-xl font-semibold mb-6">{{ mode === 'create' ? 'Tambah Kelas' : 'Edit Kelas' }}</h1>
        <form class="grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="submit">
            <select v-model="form.school_year_id" class="border rounded-lg px-3 py-2">
                <option value="">Tahun Ajaran</option>
                <option v-for="item in schoolYears" :key="item.id" :value="item.id">{{ item.name }}</option>
            </select>
            <input v-model="form.name" class="border rounded-lg px-3 py-2" placeholder="Nama Kelas" />
            <input v-model.number="form.level" class="border rounded-lg px-3 py-2" min="1" max="12" placeholder="Level" type="number" />
            <select v-model="form.homeroom_teacher_id" class="border rounded-lg px-3 py-2">
                <option value="">Wali Kelas</option>
                <option v-for="item in teachers" :key="item.id" :value="item.id">{{ item.user?.name }}</option>
            </select>
            <label class="md:col-span-2 flex items-center gap-2">
                <input v-model="form.is_active" type="checkbox" />
                <span>Aktif</span>
            </label>
            <div class="md:col-span-2 flex justify-end">
                <button :disabled="form.processing" class="bg-emerald-700 text-white rounded-lg px-4 py-2">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                </button>
            </div>
        </form>
    </div>
</template>
