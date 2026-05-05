<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { importForm, importMethod, index } from '@/actions/App/Http/Controllers/Admin/StudentController';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { SchoolYear } from '@/types/models';

defineOptions({
    layout: AppSidebarLayout,
});

defineProps<{
    schoolYears: SchoolYear[];
}>();

const form = useForm<{
    file: File | null;
    school_year_id: number | '';
}>({
    file: null,
    school_year_id: '',
});

const onFile = (event: Event) => {
    const input = event.target as HTMLInputElement;
    form.file = input.files?.[0] ?? null;
};

const submit = () => {
    form.post(importMethod().url, {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Import Data Siswa" />
    <div class="flex flex-col gap-stack-lg max-w-4xl mx-auto w-full">
        <div class="flex flex-col gap-2">
            <Link
                :href="index().url"
                class="text-on-surface-variant hover:text-primary transition-colors flex items-center gap-1 text-sm font-medium w-fit">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                Kembali ke Daftar Siswa
            </Link>
            <h2 class="font-h2 text-h2 text-on-surface">Import Data Siswa (Excel)</h2>
            <p class="font-body-md text-body-md text-on-surface-variant">
                Unggah berkas .xlsx yang memiliki baris judul kolom berisi <strong>Nama</strong> dan
                <strong>NIS</strong> atau <strong>NISN</strong>. Setiap lembar kerja yang memenuhi pola itu akan
                diproses (misalnya beberapa lembar per kelas).
            </p>
        </div>

        <div class="bg-surface-container-lowest rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest overflow-hidden">
            <div class="p-6 md:p-8 flex flex-col gap-6">
                <div>
                    <h3 class="text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider mb-2">
                        Pemetaan kolom ke form siswa
                    </h3>
                    <p class="text-body-md text-on-surface-variant mb-4">
                        Header kolom dibaca tanpa membedakan huruf besar/kecil. Contoh lembar seperti
                        <code class="text-sm bg-surface-container px-1 py-0.5 rounded">DATASISWA.xlsx</code>:
                    </p>
                    <div class="overflow-x-auto rounded-lg border border-surface-container-highest">
                        <table class="w-full text-left text-body-md min-w-[560px]">
                            <thead>
                                <tr class="bg-surface-container-low border-b border-surface-container-highest">
                                    <th class="py-2 px-4 font-table-header text-table-header text-on-surface-variant">
                                        Kolom Excel
                                    </th>
                                    <th class="py-2 px-4 font-table-header text-table-header text-on-surface-variant">
                                        Field di sistem
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-surface-container-highest">
                                <tr>
                                    <td class="py-2 px-4 text-on-surface">Nama</td>
                                    <td class="py-2 px-4 text-on-surface">Nama lengkap &amp; nama akun</td>
                                </tr>
                                <tr class="bg-surface-container-low/40">
                                    <td class="py-2 px-4 text-on-surface">NIS / NISN</td>
                                    <td class="py-2 px-4 text-on-surface">
                                        NIS &amp; NISN (jika hanya NISN, dipakai juga sebagai NIS). Password login
                                        default = NIS.
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-4 text-on-surface">JK / Jenis kelamin</td>
                                    <td class="py-2 px-4 text-on-surface">L / P → laki-laki / perempuan</td>
                                </tr>
                                <tr class="bg-surface-container-low/40">
                                    <td class="py-2 px-4 text-on-surface">Tempat lahir, Tanggal lahir</td>
                                    <td class="py-2 px-4 text-on-surface">Disimpan di profil siswa</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-4 text-on-surface">kelas / rombel</td>
                                    <td class="py-2 px-4 text-on-surface">
                                        Penempatan kelas — nama harus sama persis dengan nama kelas di master data
                                        untuk tahun ajaran yang Anda pilih.
                                    </td>
                                </tr>
                                <tr class="bg-surface-container-low/40">
                                    <td class="py-2 px-4 text-on-surface">NIK</td>
                                    <td class="py-2 px-4 text-on-surface">
                                        Diabaikan (belum ada field di profil). Kolom No / nomor urut juga diabaikan.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <hr class="border-surface-container-highest" />

                <form class="flex flex-col gap-4" @submit.prevent="submit">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-label-sm font-label-sm text-on-surface">
                            Tahun ajaran (penempatan kelas) <span class="text-error">*</span>
                        </label>
                        <div class="relative max-w-md">
                            <select
                                v-model="form.school_year_id"
                                required
                                class="w-full h-[44px] appearance-none rounded-lg border border-outline-variant bg-transparent px-4 pr-10 text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none"
                                :class="{ 'border-red-500': !!form.errors.school_year_id }">
                                <option disabled value="">Pilih tahun ajaran</option>
                                <option v-for="y in schoolYears" :key="y.id" :value="y.id">{{ y.name }}</option>
                            </select>
                            <span
                                class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline pointer-events-none"
                                >expand_more</span
                            >
                        </div>
                        <span v-if="form.errors.school_year_id" class="text-red-500 text-xs">{{
                            form.errors.school_year_id
                        }}</span>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-label-sm font-label-sm text-on-surface">
                            Berkas Excel <span class="text-error">*</span>
                        </label>
                        <input
                            type="file"
                            accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
                            class="text-body-md text-on-surface file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary file:text-on-primary"
                            @change="onFile" />
                        <span v-if="form.errors.file" class="text-red-500 text-xs">{{ form.errors.file }}</span>
                    </div>

                    <div class="flex justify-end gap-3 mt-2">
                        <Link
                            :href="index().url"
                            class="px-5 py-2.5 rounded-lg border border-outline-variant text-on-surface font-label-sm text-label-sm hover:bg-surface-container-low transition-colors"
                            >Batal</Link
                        >
                        <button
                            :disabled="form.processing"
                            type="submit"
                            class="bg-primary hover:bg-primary-container text-on-primary font-label-sm text-label-sm px-6 py-2.5 rounded-lg flex items-center gap-2 shadow-[0_4px_6px_rgba(0,0,0,0.05)] transition-all disabled:opacity-70">
                            <span v-if="form.processing" class="material-symbols-outlined animate-spin text-[18px]"
                                >progress_activity</span
                            >
                            <span v-else class="material-symbols-outlined text-[18px]">upload_file</span>
                            Unggah &amp; impor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
