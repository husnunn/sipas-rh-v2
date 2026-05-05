<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { store, update, index } from '@/actions/App/Http/Controllers/Admin/ScheduleController';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { ClassRoom, Schedule, SchoolYear, Subject, TeacherProfile } from '@/types/models';

defineOptions({ layout: AppSidebarLayout });

const props = defineProps<{
    mode: 'create' | 'edit';
    schedule: Schedule | null;
    schoolYears: SchoolYear[];
    classes: ClassRoom[];
    subjects: Subject[];
    teachers: TeacherProfile[];
    days: Record<string, string>;
}>();

const form = useForm({
    school_year_id: props.schedule?.school_year_id ?? '',
    semester: props.schedule?.semester ?? ('' as number | ''),
    class_id: props.schedule?.class_id ?? '',
    subject_id: props.schedule?.subject_id ?? ('' as number | ''),
    teacher_profile_id: props.schedule?.teacher_profile_id ?? ('' as number | ''),
    day_of_week: props.schedule?.day_of_week ?? ('' as number | ''),
    start_time: props.schedule?.start_time?.substring(0, 5) ?? '',
    end_time: props.schedule?.end_time?.substring(0, 5) ?? '',
    room: props.schedule?.room ?? '',
    notes: props.schedule?.notes ?? '',
    is_active: props.schedule?.is_active ?? true,
});

// Filter teachers based on selected subject
const filteredTeachers = computed(() => {
    if (!form.subject_id) {
        return [];
    }

    return props.teachers.filter((teacher) =>
        teacher.subjects?.some((s) => s.id === Number(form.subject_id))
    );
});

// Reset teacher when subject changes (only if current teacher doesn't teach the new subject)
const onSubjectChange = () => {
    if (form.teacher_profile_id) {
        const stillValid = filteredTeachers.value.some(
            (t) => t.id === Number(form.teacher_profile_id)
        );

        if (!stillValid) {
            form.teacher_profile_id = '';
        }
    }
};

const submit = () => {
    if (props.mode === 'create') {
        form.post(store().url);

        return;
    }

    if (props.schedule) {
        form.put(update(props.schedule).url);
    }
};
</script>

<template>
    <Head :title="mode === 'create' ? 'Tambah Jadwal' : 'Edit Jadwal'" />
    <div class="flex flex-col gap-stack-lg max-w-4xl mx-auto w-full">
        <div class="flex flex-col gap-2">
            <Link :href="index().url" class="text-on-surface-variant hover:text-primary transition-colors flex items-center gap-1 text-sm font-medium w-fit">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                Kembali ke Daftar Jadwal
            </Link>
            <h2 class="font-h2 text-h2 text-on-surface">{{ mode === 'create' ? 'Tambah Jadwal Baru' : 'Edit Jadwal' }}</h2>
            <p class="font-body-md text-body-md text-on-surface-variant">{{ mode === 'create' ? 'Atur jadwal pelajaran untuk kelas.' : 'Perbarui data jadwal pelajaran.' }}</p>
        </div>

        <div class="bg-surface-container-lowest rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest overflow-hidden">
            <form @submit.prevent="submit" class="p-6 md:p-8 flex flex-col gap-6">
                <!-- Tahun Ajaran & Semester -->
                <div>
                    <h3 class="text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider mb-4">Periode</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-label-sm font-label-sm text-on-surface">Tahun Ajaran <span class="text-error">*</span></label>
                            <div class="relative">
                                <select v-model="form.school_year_id" class="w-full h-[44px] appearance-none rounded-lg border border-outline-variant bg-transparent px-4 pr-10 text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" :class="{'border-red-500': form.errors.school_year_id}">
                                    <option value="">Pilih Tahun Ajaran</option>
                                    <option v-for="item in schoolYears" :key="item.id" :value="item.id">{{ item.name }}</option>
                                </select>
                                <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline pointer-events-none">expand_more</span>
                            </div>
                            <span v-if="form.errors.school_year_id" class="text-red-500 text-xs">{{ form.errors.school_year_id }}</span>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-label-sm font-label-sm text-on-surface">Semester <span class="text-error">*</span></label>
                            <div class="relative">
                                <select v-model.number="form.semester" class="w-full h-[44px] appearance-none rounded-lg border border-outline-variant bg-transparent px-4 pr-10 text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" :class="{'border-red-500': form.errors.semester}">
                                    <option value="" disabled>Pilih Semester</option>
                                    <option :value="1">Semester 1 (Ganjil)</option>
                                    <option :value="2">Semester 2 (Genap)</option>
                                </select>
                                <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline pointer-events-none">expand_more</span>
                            </div>
                            <span v-if="form.errors.semester" class="text-red-500 text-xs">{{ form.errors.semester }}</span>
                        </div>
                    </div>
                </div>

                <hr class="border-surface-container-highest" />

                <!-- Kelas, Mapel, Guru -->
                <div>
                    <h3 class="text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider mb-4">Kelas & Pengajar</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-label-sm font-label-sm text-on-surface">Kelas <span class="text-error">*</span></label>
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
                            <label class="text-label-sm font-label-sm text-on-surface">Mata Pelajaran <span class="text-error">*</span></label>
                            <div class="relative">
                                <select v-model="form.subject_id" class="w-full h-[44px] appearance-none rounded-lg border border-outline-variant bg-transparent px-4 pr-10 text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" :class="{'border-red-500': form.errors.subject_id}" @change="onSubjectChange">
                                    <option value="">Pilih Mata Pelajaran</option>
                                    <option v-for="item in subjects" :key="item.id" :value="item.id">{{ item.code }} - {{ item.name }}</option>
                                </select>
                                <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline pointer-events-none">expand_more</span>
                            </div>
                            <span v-if="form.errors.subject_id" class="text-red-500 text-xs">{{ form.errors.subject_id }}</span>
                        </div>
                        <div class="flex flex-col gap-1.5 md:col-span-2">
                            <label class="text-label-sm font-label-sm text-on-surface">Guru Pengampu <span class="text-error">*</span></label>
                            <div class="relative">
                                <select v-model="form.teacher_profile_id" class="w-full h-[44px] appearance-none rounded-lg border border-outline-variant bg-transparent px-4 pr-10 text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" :class="{'border-red-500': form.errors.teacher_profile_id}" :disabled="!form.subject_id">
                                    <option value="">{{ form.subject_id ? 'Pilih Guru' : '← Pilih Mata Pelajaran terlebih dahulu' }}</option>
                                    <option v-for="item in filteredTeachers" :key="item.id" :value="item.id">{{ item.full_name }} {{ item.nip ? `(${item.nip})` : '' }}</option>
                                </select>
                                <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline pointer-events-none">expand_more</span>
                            </div>
                            <span v-if="!form.subject_id" class="text-xs text-on-surface-variant">Pilih mata pelajaran terlebih dahulu untuk melihat daftar guru yang mengampu.</span>
                            <span v-else-if="filteredTeachers.length === 0" class="text-xs text-amber-600">Tidak ada guru yang terdaftar mengampu mata pelajaran ini.</span>
                            <span v-if="form.errors.teacher_profile_id" class="text-red-500 text-xs">{{ form.errors.teacher_profile_id }}</span>
                        </div>
                    </div>
                </div>

                <hr class="border-surface-container-highest" />

                <!-- Waktu -->
                <div>
                    <h3 class="text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider mb-4">Waktu & Tempat</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-label-sm font-label-sm text-on-surface">Hari <span class="text-error">*</span></label>
                            <div class="relative">
                                <select v-model="form.day_of_week" class="w-full h-[44px] appearance-none rounded-lg border border-outline-variant bg-transparent px-4 pr-10 text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" :class="{'border-red-500': form.errors.day_of_week}">
                                    <option value="">Pilih Hari</option>
                                    <option v-for="(label, value) in days" :key="value" :value="Number(value)">{{ label }}</option>
                                </select>
                                <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline pointer-events-none">expand_more</span>
                            </div>
                            <span v-if="form.errors.day_of_week" class="text-red-500 text-xs">{{ form.errors.day_of_week }}</span>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-label-sm font-label-sm text-on-surface">Jam Mulai <span class="text-error">*</span></label>
                            <input v-model="form.start_time" type="time" class="h-[44px] rounded-lg border border-outline-variant bg-transparent px-4 text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" :class="{'border-red-500': form.errors.start_time}" />
                            <span v-if="form.errors.start_time" class="text-red-500 text-xs">{{ form.errors.start_time }}</span>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-label-sm font-label-sm text-on-surface">Jam Selesai <span class="text-error">*</span></label>
                            <input v-model="form.end_time" type="time" class="h-[44px] rounded-lg border border-outline-variant bg-transparent px-4 text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" :class="{'border-red-500': form.errors.end_time}" />
                            <span v-if="form.errors.end_time" class="text-red-500 text-xs">{{ form.errors.end_time }}</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-label-sm font-label-sm text-on-surface">Ruangan</label>
                            <input v-model="form.room" type="text" class="h-[44px] rounded-lg border border-outline-variant bg-transparent px-4 text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" placeholder="Contoh: Lab IPA, Ruang 12" :class="{'border-red-500': form.errors.room}" />
                            <span v-if="form.errors.room" class="text-red-500 text-xs">{{ form.errors.room }}</span>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-label-sm font-label-sm text-on-surface">Catatan</label>
                            <input v-model="form.notes" type="text" class="h-[44px] rounded-lg border border-outline-variant bg-transparent px-4 text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" placeholder="Catatan tambahan (opsional)" :class="{'border-red-500': form.errors.notes}" />
                            <span v-if="form.errors.notes" class="text-red-500 text-xs">{{ form.errors.notes }}</span>
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input v-model="form.is_active" type="checkbox" class="w-5 h-5 rounded border-outline-variant text-primary focus:ring-primary" />
                        <span class="text-body-md font-medium text-on-surface">Jadwal Aktif</span>
                    </label>
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
