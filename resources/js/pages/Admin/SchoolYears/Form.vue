<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { index, store, update } from '@/actions/App/Http/Controllers/Admin/SchoolYearController';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { SchoolYear } from '@/types/models';

defineOptions({ layout: AppSidebarLayout });

const props = defineProps<{
    mode: 'create' | 'edit';
    schoolYear: SchoolYear | null;
}>();

const form = useForm({
    name: props.schoolYear?.name ?? '',
    start_date: props.schoolYear?.start_date ?? '',
    end_date: props.schoolYear?.end_date ?? '',
});

const pageTitle = computed(() => (props.mode === 'create' ? 'Tambah Tahun Ajaran' : 'Edit Tahun Ajaran'));

const submit = () => {
    if (props.mode === 'create') {
        form.post(store().url);

        return;
    }

    if (props.schoolYear) {
        form.put(update(props.schoolYear).url);
    }
};
</script>

<template>
    <Head :title="pageTitle" />

    <div class="flex flex-col gap-stack-lg max-w-3xl mx-auto w-full">
        <Link
            :href="index().url"
            class="flex w-fit items-center gap-1 text-sm font-medium text-on-surface-variant transition-colors hover:text-primary"
        >
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            Kembali
        </Link>

        <div>
            <h2 class="font-h2 text-h2 text-on-surface">{{ pageTitle }}</h2>
            <p class="font-body-md text-body-md text-on-surface-variant mt-1">Isi periode tahun ajaran.</p>
        </div>

        <div class="bg-surface-container-lowest rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest overflow-hidden">
            <form class="p-6 md:p-8 flex flex-col gap-5" @submit.prevent="submit">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-3">
                        <label class="text-label-sm font-label-sm text-on-surface">Nama <span class="text-error">*</span></label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="Contoh: 2025/2026"
                            class="mt-1 h-[44px] w-full rounded-lg border border-outline-variant bg-transparent px-4 text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none"
                            :class="{ 'border-red-500': !!form.errors.name }"
                        />
                        <span v-if="form.errors.name" class="text-red-500 text-xs mt-1 block">{{ form.errors.name }}</span>
                    </div>

                    <div>
                        <label class="text-label-sm font-label-sm text-on-surface">Tanggal mulai <span class="text-error">*</span></label>
                        <input
                            v-model="form.start_date"
                            type="date"
                            class="mt-1 h-[44px] w-full rounded-lg border border-outline-variant bg-transparent px-4 text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none"
                            :class="{ 'border-red-500': !!form.errors.start_date }"
                        />
                        <span v-if="form.errors.start_date" class="text-red-500 text-xs mt-1 block">{{ form.errors.start_date }}</span>
                    </div>

                    <div>
                        <label class="text-label-sm font-label-sm text-on-surface">Tanggal selesai <span class="text-error">*</span></label>
                        <input
                            v-model="form.end_date"
                            type="date"
                            class="mt-1 h-[44px] w-full rounded-lg border border-outline-variant bg-transparent px-4 text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none"
                            :class="{ 'border-red-500': !!form.errors.end_date }"
                        />
                        <span v-if="form.errors.end_date" class="text-red-500 text-xs mt-1 block">{{ form.errors.end_date }}</span>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <Link
                        :href="index().url"
                        class="px-5 py-2.5 rounded-lg border border-outline-variant text-on-surface font-label-sm text-label-sm hover:bg-surface-container-low transition-colors"
                    >
                        Batal
                    </Link>
                    <button
                        :disabled="form.processing"
                        type="submit"
                        class="bg-primary hover:bg-primary-container text-on-primary font-label-sm text-label-sm px-6 py-2.5 rounded-lg flex items-center gap-2 shadow-[0_4px_6px_rgba(0,0,0,0.05)] transition-all disabled:opacity-70"
                    >
                        <span v-if="form.processing" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                        <span v-else class="material-symbols-outlined text-[18px]">save</span>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

