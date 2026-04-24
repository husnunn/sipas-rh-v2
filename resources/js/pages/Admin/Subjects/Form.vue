<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { store, update } from '@/actions/App/Http/Controllers/Admin/SubjectController';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { Subject } from '@/types/models';

defineOptions({ layout: AppSidebarLayout });

const props = defineProps<{
    mode: 'create' | 'edit';
    subject: Subject | null;
}>();

const form = useForm({
    code: props.subject?.code ?? '',
    name: props.subject?.name ?? '',
    description: props.subject?.description ?? '',
    is_active: props.subject?.is_active ?? true,
});

const submit = () => {
    if (props.mode === 'create') {
        form.post(store().url);

        return;
    }

    if (props.subject) {
        form.put(update(props.subject).url);
    }
};
</script>

<template>
    <Head :title="mode === 'create' ? 'Tambah Mata Pelajaran' : 'Edit Mata Pelajaran'" />
    <div class="max-w-3xl mx-auto bg-white dark:bg-slate-900 rounded-xl shadow p-6">
        <h1 class="text-xl font-semibold mb-6">{{ mode === 'create' ? 'Tambah Mata Pelajaran' : 'Edit Mata Pelajaran' }}</h1>
        <form class="grid grid-cols-1 gap-4" @submit.prevent="submit">
            <input v-model="form.code" class="border rounded-lg px-3 py-2" placeholder="Kode" />
            <input v-model="form.name" class="border rounded-lg px-3 py-2" placeholder="Nama" />
            <textarea v-model="form.description" class="border rounded-lg px-3 py-2" placeholder="Deskripsi" />
            <label class="flex items-center gap-2">
                <input v-model="form.is_active" type="checkbox" />
                <span>Aktif</span>
            </label>
            <div class="flex justify-end">
                <button :disabled="form.processing" class="bg-emerald-700 text-white rounded-lg px-4 py-2">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                </button>
            </div>
        </form>
    </div>
</template>
