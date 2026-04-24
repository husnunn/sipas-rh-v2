<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { store, index } from '@/actions/App/Http/Controllers/Admin/AccountController';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import InputError from '@/components/InputError.vue';

defineOptions({ layout: AppSidebarLayout });

const form = useForm({
    name: '',
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
    roles: ['admin'] as string[],
    is_active: true,
});

const submit = () => {
    form.post(store().url);
};
</script>

<template>
    <Head title="Tambah Akun Baru" />
    <div class="flex flex-col gap-stack-lg max-w-4xl mx-auto w-full">
        <div class="flex flex-col gap-2">
            <Link :href="index().url" class="text-on-surface-variant hover:text-primary transition-colors flex items-center gap-1 text-sm font-medium w-fit">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                Kembali ke Manajemen Akun
            </Link>
            <h2 class="font-h2 text-h2 text-on-surface">Tambah Akun Baru</h2>
            <p class="font-body-md text-body-md text-on-surface-variant">Buat pengguna baru dengan peran dan akses spesifik.</p>
        </div>

        <div class="bg-surface-container-lowest rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest overflow-hidden">
            <form @submit.prevent="submit" class="p-6 md:p-8 flex flex-col gap-6">
                <!-- Data Akun Dasar -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex flex-col gap-2">
                        <label class="text-label-sm font-label-sm text-on-surface">Nama Lengkap <span class="text-error">*</span></label>
                        <input v-model="form.name" type="text" class="h-[44px] rounded-lg border border-outline-variant bg-transparent px-4 text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" placeholder="Masukkan nama lengkap" required />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-label-sm font-label-sm text-on-surface">Username <span class="text-error">*</span></label>
                        <input v-model="form.username" type="text" class="h-[44px] rounded-lg border border-outline-variant bg-transparent px-4 text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" placeholder="Masukkan username unik" required />
                        <InputError :message="form.errors.username" />
                    </div>
                    <div class="flex flex-col gap-2 md:col-span-2">
                        <label class="text-label-sm font-label-sm text-on-surface">Email <span class="text-error">*</span></label>
                        <input v-model="form.email" type="email" class="h-[44px] rounded-lg border border-outline-variant bg-transparent px-4 text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" placeholder="alamat@email.com" required />
                        <InputError :message="form.errors.email" />
                    </div>
                </div>

                <hr class="border-surface-container-highest" />

                <!-- Role dan Status -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex flex-col gap-2">
                        <label class="text-label-sm font-label-sm text-on-surface">Peran (Role) <span class="text-error">*</span></label>
                        <div class="flex flex-col gap-2">
                            <label class="flex items-center gap-3 cursor-pointer border border-outline-variant rounded-lg px-4 py-3 hover:bg-surface-container-low transition-colors" :class="form.roles.includes('admin') ? 'border-primary bg-primary/5' : ''">
                                <input v-model="form.roles" type="checkbox" value="admin" class="w-5 h-5 rounded border-outline-variant text-primary focus:ring-primary" />
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[18px]" style="font-variation-settings: 'FILL' 1;">shield_person</span>
                                    <span class="text-body-md font-medium text-on-surface">Administrator</span>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer border border-outline-variant rounded-lg px-4 py-3 hover:bg-surface-container-low transition-colors" :class="form.roles.includes('teacher') ? 'border-primary bg-primary/5' : ''">
                                <input v-model="form.roles" type="checkbox" value="teacher" class="w-5 h-5 rounded border-outline-variant text-primary focus:ring-primary" />
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[18px]" style="font-variation-settings: 'FILL' 1;">school</span>
                                    <span class="text-body-md font-medium text-on-surface">Guru (Teacher)</span>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer border border-outline-variant rounded-lg px-4 py-3 hover:bg-surface-container-low transition-colors" :class="form.roles.includes('student') ? 'border-primary bg-primary/5' : ''">
                                <input v-model="form.roles" type="checkbox" value="student" class="w-5 h-5 rounded border-outline-variant text-primary focus:ring-primary" />
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[18px]" style="font-variation-settings: 'FILL' 1;">person</span>
                                    <span class="text-body-md font-medium text-on-surface">Siswa (Student)</span>
                                </div>
                            </label>
                        </div>
                        <InputError :message="form.errors.roles" />
                    </div>
                    <div class="flex flex-col justify-center pt-6">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input v-model="form.is_active" type="checkbox" class="w-5 h-5 rounded border-outline-variant text-primary focus:ring-primary" />
                            <span class="text-body-md font-medium text-on-surface">Akun Aktif (Bisa Login)</span>
                        </label>
                    </div>
                </div>

                <hr class="border-surface-container-highest" />

                <!-- Password -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex flex-col gap-2">
                        <label class="text-label-sm font-label-sm text-on-surface">Password <span class="text-error">*</span></label>
                        <input v-model="form.password" type="password" class="h-[44px] rounded-lg border border-outline-variant bg-transparent px-4 text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" placeholder="Minimal 8 karakter" required />
                        <InputError :message="form.errors.password" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-label-sm font-label-sm text-on-surface">Konfirmasi Password <span class="text-error">*</span></label>
                        <input v-model="form.password_confirmation" type="password" class="h-[44px] rounded-lg border border-outline-variant bg-transparent px-4 text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" placeholder="Ulangi password" required />
                    </div>
                </div>

                <!-- Aksi -->
                <div class="flex justify-end gap-3 mt-4">
                    <Link :href="index().url" class="px-5 py-2.5 rounded-lg border border-outline-variant text-on-surface font-label-sm text-label-sm hover:bg-surface-container-low transition-colors">Batal</Link>
                    <button :disabled="form.processing" type="submit" class="bg-primary hover:bg-primary-container text-on-primary font-label-sm text-label-sm px-6 py-2.5 rounded-lg flex items-center gap-2 shadow-[0_4px_6px_rgba(0,0,0,0.05)] transition-all disabled:opacity-70">
                        <span v-if="form.processing" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                        <span v-else class="material-symbols-outlined text-[18px]">save</span>
                        Simpan Akun
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
