<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { store, update, index } from '@/actions/App/Http/Controllers/Admin/StudentController';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { ClassRoom, SchoolYear, StudentProfile } from '@/types/models';

defineOptions({ layout: AppSidebarLayout });

const props = defineProps<{
    mode: 'create' | 'edit';
    student: StudentProfile | null;
    classes: ClassRoom[];
    schoolYears: SchoolYear[];
}>();

const form = useForm({
    name: props.student?.user?.name ?? props.student?.full_name ?? '',
    username: props.student?.user?.username ?? '',
    email: props.student?.user?.email ?? '',
    password: '',
    nis: props.student?.nis ?? '',
    nisn: props.student?.nisn ?? '',
    gender: props.student?.gender ?? '',
    class_id: props.student?.classes?.[0]?.id ?? '',
    school_year_id: props.student?.classes?.[0]?.school_year_id ?? '',
});

const submit = () => {
    if (props.mode === 'create') {
        form.post(store().url);

        return;
    }

    if (props.student) {
        form.put(update(props.student).url);
    }
};
</script>

<template>
    <Head :title="mode === 'create' ? 'Tambah Siswa' : 'Edit Siswa'" />
    <div class="flex flex-col gap-stack-lg max-w-4xl mx-auto w-full">
        <div class="flex flex-col gap-2">
            <Link :href="index().url" class="text-on-surface-variant hover:text-primary transition-colors flex items-center gap-1 text-sm font-medium w-fit">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                Kembali ke Daftar Siswa
            </Link>
            <h2 class="font-h2 text-h2 text-on-surface">{{ mode === 'create' ? 'Tambah Siswa Baru' : 'Edit Data Siswa' }}</h2>
            <p class="font-body-md text-body-md text-on-surface-variant">{{ mode === 'create' ? 'Lengkapi data siswa untuk mendaftarkan ke sistem.' : 'Perbarui data siswa yang sudah terdaftar.' }}</p>
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
                            <label class="text-label-sm font-label-sm text-on-surface">Password {{ mode === 'edit' ? '(Kosongkan jika tidak diubah)' : '(Opsional, default: NIS)' }}</label>
                            <input v-model="form.password" type="password" class="h-[44px] rounded-lg border border-outline-variant bg-transparent px-4 text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" placeholder="Minimal 6 karakter" :class="{'border-red-500': form.errors.password}" />
                            <span v-if="form.errors.password" class="text-red-500 text-xs">{{ form.errors.password }}</span>
                        </div>
                    </div>
                </div>

                <hr class="border-surface-container-highest" />

                <!-- Data Profil -->
                <div>
                    <h3 class="text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider mb-4">Data Profil Siswa</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-label-sm font-label-sm text-on-surface">NIS <span class="text-error">*</span></label>
                            <input v-model="form.nis" type="text" class="h-[44px] rounded-lg border border-outline-variant bg-transparent px-4 text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" placeholder="Nomor Induk Siswa" :class="{'border-red-500': form.errors.nis}" />
                            <span v-if="form.errors.nis" class="text-red-500 text-xs">{{ form.errors.nis }}</span>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-label-sm font-label-sm text-on-surface">NISN</label>
                            <input v-model="form.nisn" type="text" class="h-[44px] rounded-lg border border-outline-variant bg-transparent px-4 text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" placeholder="Nomor Induk Siswa Nasional" :class="{'border-red-500': form.errors.nisn}" />
                            <span v-if="form.errors.nisn" class="text-red-500 text-xs">{{ form.errors.nisn }}</span>
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
                    </div>
                </div>

                <hr class="border-surface-container-highest" />

                <!-- Kelas -->
                <div>
                    <h3 class="text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider mb-4">Penempatan Kelas</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-label-sm font-label-sm text-on-surface">Kelas</label>
                            <div class="relative">
                                <select v-model="form.class_id" class="w-full h-[44px] appearance-none rounded-lg border border-outline-variant bg-transparent px-4 pr-10 text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" :class="{'border-red-500': form.errors.class_id}">
                                    <option value="">Pilih Kelas</option>
                                    <option v-for="item in classes" :key="item.id" :value="item.id">{{ item.name }}</option>
                                </select>
                                <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline pointer-events-none">expand_more</span>
                            </div>
                            <span v-if="form.errors.class_id" class="text-red-500 text-xs">{{ form.errors.class_id }}</span>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-label-sm font-label-sm text-on-surface">Tahun Ajaran</label>
                            <div class="relative">
                                <select v-model="form.school_year_id" class="w-full h-[44px] appearance-none rounded-lg border border-outline-variant bg-transparent px-4 pr-10 text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" :class="{'border-red-500': form.errors.school_year_id}">
                                    <option value="">Pilih Tahun Ajaran</option>
                                    <option v-for="item in schoolYears" :key="item.id" :value="item.id">{{ item.name }}</option>
                                </select>
                                <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline pointer-events-none">expand_more</span>
                            </div>
                            <span v-if="form.errors.school_year_id" class="text-red-500 text-xs">{{ form.errors.school_year_id }}</span>
                        </div>
                    </div>
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
