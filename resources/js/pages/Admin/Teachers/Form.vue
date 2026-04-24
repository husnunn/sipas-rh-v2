<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { store, update, index } from '@/actions/App/Http/Controllers/Admin/TeacherController';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { Subject, TeacherProfile } from '@/types/models';

defineOptions({ layout: AppSidebarLayout });

const props = defineProps<{
    mode: 'create' | 'edit';
    teacher: TeacherProfile | null;
    subjects: Subject[];
}>();

const form = useForm({
    name: props.teacher?.user?.name ?? props.teacher?.full_name ?? '',
    username: props.teacher?.user?.username ?? '',
    email: props.teacher?.user?.email ?? '',
    password: '',
    nip: props.teacher?.nip ?? '',
    gender: props.teacher?.gender ?? '',
    phone: props.teacher?.phone ?? '',
    address: props.teacher?.address ?? '',
    subject_ids: props.teacher?.subjects?.map((item) => item.id) ?? [],
});

const submit = () => {
    if (props.mode === 'create') {
        form.post(store().url);

        return;
    }

    if (props.teacher) {
        form.put(update(props.teacher).url);
    }
};
</script>

<template>
    <Head :title="mode === 'create' ? 'Tambah Guru' : 'Edit Guru'" />
    <div class="flex flex-col gap-stack-lg max-w-4xl mx-auto w-full">
        <div class="flex flex-col gap-2">
            <Link :href="index().url" class="text-on-surface-variant hover:text-primary transition-colors flex items-center gap-1 text-sm font-medium w-fit">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                Kembali ke Daftar Guru
            </Link>
            <h2 class="font-h2 text-h2 text-on-surface">{{ mode === 'create' ? 'Tambah Guru Baru' : 'Edit Data Guru' }}</h2>
            <p class="font-body-md text-body-md text-on-surface-variant">{{ mode === 'create' ? 'Lengkapi data guru untuk mendaftarkan ke sistem.' : 'Perbarui data guru yang sudah terdaftar.' }}</p>
        </div>

        <div class="bg-surface-container-lowest rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest overflow-hidden">
            <form @submit.prevent="submit" class="p-6 md:p-8 flex flex-col gap-6">
                <!-- Data Akun -->
                <div>
                    <h3 class="text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider mb-4">Data Akun Login</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-label-sm font-label-sm text-on-surface">Nama Lengkap <span class="text-error">*</span></label>
                            <input v-model="form.name" type="text" class="h-[44px] rounded-lg border border-outline-variant bg-transparent px-4 text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" placeholder="Masukkan nama lengkap" :class="{'border-red-500': form.errors.name}" />
                            <span v-if="form.errors.name" class="text-red-500 text-xs">{{ form.errors.name }}</span>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-label-sm font-label-sm text-on-surface">Username <span class="text-error">*</span></label>
                            <input v-model="form.username" type="text" class="h-[44px] rounded-lg border border-outline-variant bg-transparent px-4 text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" placeholder="Username untuk login" :class="{'border-red-500': form.errors.username}" />
                            <span v-if="form.errors.username" class="text-red-500 text-xs">{{ form.errors.username }}</span>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-label-sm font-label-sm text-on-surface">Email</label>
                            <input v-model="form.email" type="email" class="h-[44px] rounded-lg border border-outline-variant bg-transparent px-4 text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" placeholder="alamat@email.com" :class="{'border-red-500': form.errors.email}" />
                            <span v-if="form.errors.email" class="text-red-500 text-xs">{{ form.errors.email }}</span>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-label-sm font-label-sm text-on-surface">Password {{ mode === 'edit' ? '(Kosongkan jika tidak diubah)' : '(Opsional, default: NIP/Username)' }}</label>
                            <input v-model="form.password" type="password" class="h-[44px] rounded-lg border border-outline-variant bg-transparent px-4 text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" placeholder="Minimal 6 karakter" :class="{'border-red-500': form.errors.password}" />
                            <span v-if="form.errors.password" class="text-red-500 text-xs">{{ form.errors.password }}</span>
                        </div>
                    </div>
                </div>

                <hr class="border-surface-container-highest" />

                <!-- Data Profil -->
                <div>
                    <h3 class="text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider mb-4">Data Profil Guru</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-label-sm font-label-sm text-on-surface">NIP</label>
                            <input v-model="form.nip" type="text" class="h-[44px] rounded-lg border border-outline-variant bg-transparent px-4 text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" placeholder="Nomor Induk Pegawai" :class="{'border-red-500': form.errors.nip}" />
                            <span v-if="form.errors.nip" class="text-red-500 text-xs">{{ form.errors.nip }}</span>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-label-sm font-label-sm text-on-surface">Jenis Kelamin</label>
                            <div class="relative">
                                <select v-model="form.gender" class="w-full h-[44px] appearance-none rounded-lg border border-outline-variant bg-transparent px-4 pr-10 text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" :class="{'border-red-500': form.errors.gender}">
                                    <option value="">Pilih Jenis Kelamin</option>
                                    <option value="male">Laki-laki</option>
                                    <option value="female">Perempuan</option>
                                </select>
                                <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline pointer-events-none">expand_more</span>
                            </div>
                            <span v-if="form.errors.gender" class="text-red-500 text-xs">{{ form.errors.gender }}</span>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-label-sm font-label-sm text-on-surface">No. Telepon</label>
                            <input v-model="form.phone" type="text" class="h-[44px] rounded-lg border border-outline-variant bg-transparent px-4 text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" placeholder="08xxxxxxxxxx" :class="{'border-red-500': form.errors.phone}" />
                            <span v-if="form.errors.phone" class="text-red-500 text-xs">{{ form.errors.phone }}</span>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-label-sm font-label-sm text-on-surface">Alamat</label>
                            <input v-model="form.address" type="text" class="h-[44px] rounded-lg border border-outline-variant bg-transparent px-4 text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" placeholder="Alamat tempat tinggal" :class="{'border-red-500': form.errors.address}" />
                            <span v-if="form.errors.address" class="text-red-500 text-xs">{{ form.errors.address }}</span>
                        </div>
                    </div>
                </div>

                <hr class="border-surface-container-highest" />

                <!-- Mata Pelajaran -->
                <div>
                    <h3 class="text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider mb-4">Mata Pelajaran yang Diampu</h3>
                    <p class="text-xs text-on-surface-variant mb-3">Pilih satu atau lebih mata pelajaran. Setiap mapel maksimal diajar oleh 2 guru.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                        <label v-for="item in subjects" :key="item.id" class="flex items-center gap-3 cursor-pointer border border-outline-variant rounded-lg px-4 py-3 hover:bg-surface-container-low transition-colors" :class="form.subject_ids.includes(item.id) ? 'border-primary bg-primary/5' : ''">
                            <input v-model="form.subject_ids" type="checkbox" :value="item.id" class="w-5 h-5 rounded border-outline-variant text-primary focus:ring-primary" />
                            <div class="flex flex-col">
                                <span class="text-body-md font-medium text-on-surface">{{ item.name }}</span>
                                <span class="text-xs text-on-surface-variant">{{ item.code }}</span>
                            </div>
                        </label>
                    </div>
                    <span v-if="form.errors.subject_ids" class="text-red-500 text-xs mt-1 block">{{ form.errors.subject_ids }}</span>
                </div>

                <!-- Aksi -->
                <div class="flex justify-end gap-3 mt-4">
                    <Link :href="index().url" class="px-5 py-2.5 rounded-lg border border-outline-variant text-on-surface font-label-sm text-label-sm hover:bg-surface-container-low transition-colors">Batal</Link>
                    <button :disabled="form.processing" type="submit" class="bg-primary hover:bg-primary-container text-on-primary font-label-sm text-label-sm px-6 py-2.5 rounded-lg flex items-center gap-2 shadow-[0_4px_6px_rgba(0,0,0,0.05)] transition-all disabled:opacity-70">
                        <span v-if="form.processing" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                        <span v-else class="material-symbols-outlined text-[18px]">save</span>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
