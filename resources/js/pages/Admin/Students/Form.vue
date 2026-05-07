<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { store, update, index } from '@/actions/App/Http/Controllers/Admin/StudentController';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { useWilayahCascade } from '@/composables/useWilayahCascade';
import type { ClassRoom, SchoolYear, StudentProfile, StudentParentRow } from '@/types/models';
import { computed, onBeforeUnmount, onMounted, ref, toRefs, watch } from 'vue';

defineOptions({ layout: AppSidebarLayout });

const props = defineProps<{
    mode: 'create' | 'edit';
    student: StudentProfile | null;
    classes: ClassRoom[];
    schoolYears: SchoolYear[];
    incomeBandOptions: Record<string, string>;
}>();

const activeTab = ref<'utama' | 'tambahan' | 'ortu'>('utama');

const incomeEntries = computed(() => Object.entries(props.incomeBandOptions ?? {}));

const getMotherRow = (): StudentParentRow | undefined =>
    props.student?.parents?.find((p) => p.relation === 'mother');

const getFatherRow = (): StudentParentRow | undefined =>
    props.student?.parents?.find((p) => p.relation === 'father');

const ext = computed(() => props.student?.extension);

const form = useForm({
    name: props.student?.user?.name ?? props.student?.full_name ?? '',
    username: props.student?.user?.username ?? '',
    email: props.student?.user?.email ?? '',
    password: '',
    nis: props.student?.nis ?? '',
    nisn: props.student?.nisn ?? '',
    gender: props.student?.gender ?? '',
    birth_date: props.student?.birth_date?.substring(0, 10) ?? '',
    birth_place: props.student?.birth_place ?? '',
    phone: props.student?.phone ?? '',
    address: props.student?.address ?? '',
    parent_name: props.student?.parent_name ?? '',
    parent_phone: props.student?.parent_phone ?? '',
    class_id: props.student?.classes?.[0]?.id ?? '',
    school_year_id: props.student?.classes?.[0]?.school_year_id ?? '',
    street_address: ext.value?.street_address ?? '',
    rt: ext.value?.rt ?? '',
    rw: ext.value?.rw ?? '',
    village: ext.value?.village ?? '',
    district: ext.value?.district ?? '',
    city: ext.value?.city ?? '',
    province: ext.value?.province ?? '',
    wilayah_village_id: ext.value?.wilayah_village_id ?? '',
    postal_code: ext.value?.postal_code ?? '',
    religion: ext.value?.religion ?? '',
    blood_type: ext.value?.blood_type ?? '',
    profile_photo: null as File | null,
    remove_profile_photo: false,
    mother_full_name: getMotherRow()?.full_name ?? '',
    mother_occupation: getMotherRow()?.occupation ?? '',
    mother_monthly_income_band: getMotherRow()?.monthly_income_band ?? '',
    mother_nik: getMotherRow()?.nik ?? '',
    mother_birth_date: getMotherRow()?.birth_date?.substring(0, 10) ?? '',
    father_full_name: getFatherRow()?.full_name ?? '',
    father_occupation: getFatherRow()?.occupation ?? '',
    father_monthly_income_band: getFatherRow()?.monthly_income_band ?? '',
    father_nik: getFatherRow()?.nik ?? '',
    father_birth_date: getFatherRow()?.birth_date?.substring(0, 10) ?? '',
});

const profilePhotoExistingUrl = computed(() =>
    props.mode === 'edit' && ext.value?.profile_photo_path ? `/storage/${ext.value.profile_photo_path}` : null,
);
const profilePhotoPreviewUrl = ref<string | null>(null);
const profilePhotoDisplayUrl = computed(() => {
    if (form.remove_profile_photo) {
        return null;
    }

    return profilePhotoPreviewUrl.value ?? profilePhotoExistingUrl.value;
});

const pageTitle = computed(() => (props.mode === 'create' ? 'Tambah Siswa Baru' : 'Edit Data Siswa'));

const pageSubtitle = computed(() =>
    props.mode === 'create'
        ? 'Lengkapi data siswa untuk mendaftarkan ke sistem.'
        : 'Update profil dan informasi akademik siswa.',
);

const fieldBase =
    'w-full rounded-lg border border-outline-variant bg-surface-container-lowest px-3 py-2 font-body-md text-body-md text-on-surface outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary';

const religionPresets = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'] as const;

const showReligionOtherPreset = computed(() => {
    const r = form.religion;

    return typeof r === 'string' && r !== '' && !religionPresets.includes(r as (typeof religionPresets)[number]);
});

const wilayahState = useWilayahCascade(form);
const {
    provinceOptions,
    regencyOptions,
    districtOptions,
    villageOptions,
    wilayahProvinceId,
    wilayahRegencyId,
    wilayahDistrictId,
    wilayahVillageId,
    wilayahLoading,
} = toRefs(wilayahState);
const { bootstrapWilayah, onWilayahProvinceChange, onWilayahRegencyChange, onWilayahDistrictChange } = wilayahState;

onMounted(() => {
    void bootstrapWilayah();
});

const onProfilePhotoChange = (e: Event) => {
    const input = e.target as HTMLInputElement;
    const nextFile = input.files?.[0] ?? null;
    form.profile_photo = nextFile;
    form.remove_profile_photo = false;

    if (profilePhotoPreviewUrl.value) {
        URL.revokeObjectURL(profilePhotoPreviewUrl.value);
        profilePhotoPreviewUrl.value = null;
    }

    if (nextFile) {
        profilePhotoPreviewUrl.value = URL.createObjectURL(nextFile);
    }
};

watch(
    () => form.remove_profile_photo,
    (shouldRemove) => {
        if (!shouldRemove) {
            return;
        }

        form.profile_photo = null;
        if (profilePhotoPreviewUrl.value) {
            URL.revokeObjectURL(profilePhotoPreviewUrl.value);
            profilePhotoPreviewUrl.value = null;
        }
    },
);

onBeforeUnmount(() => {
    if (profilePhotoPreviewUrl.value) {
        URL.revokeObjectURL(profilePhotoPreviewUrl.value);
        profilePhotoPreviewUrl.value = null;
    }
});

const submit = () => {
    const needsMultipart = form.profile_photo instanceof File;
    const options = needsMultipart ? { forceFormData: true } : {};

    if (props.mode === 'create') {
        form.post(store().url, options);

        return;
    }

    if (props.student) {
        form.put(update(props.student).url, options);
    }
};
</script>

<template>
    <Head :title="mode === 'create' ? 'Tambah Siswa' : 'Edit Siswa'" />
    <div class="flex w-full flex-col gap-stack-lg pb-stack-lg">
        <Link
            :href="index().url"
            class="flex w-fit items-center gap-1 text-sm font-medium text-on-surface-variant transition-colors hover:text-primary"
        >
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            Kembali ke Daftar Siswa
        </Link>

        <form id="student-admin-form" class="flex flex-col gap-stack-lg" @submit.prevent="submit">
            <div class="flex flex-col gap-stack-sm sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="font-h1 text-h1 text-on-background">{{ pageTitle }}</h1>
                    <p class="mt-1 font-body-md text-body-md text-on-surface-variant">{{ pageSubtitle }}</p>
                </div>
                <div class="flex shrink-0 gap-3">
                    <Link
                        :href="index().url"
                        class="rounded-lg border border-outline px-4 py-2 font-label-sm text-label-sm text-on-surface transition-colors hover:bg-surface-container"
                    >
                        Batal
                    </Link>
                    <button
                        :disabled="form.processing"
                        class="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 font-label-sm text-label-sm text-on-primary shadow-sm transition-colors hover:bg-primary/90 disabled:opacity-70"
                        type="submit"
                    >
                        <span v-if="form.processing" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                        <span v-else class="material-symbols-outlined text-[18px]">save</span>
                        {{ mode === 'create' ? 'Simpan' : 'Simpan Perubahan' }}
                    </button>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-surface-variant/50 bg-surface shadow-[0_4px_6px_rgba(0,0,0,0.05)]">
                <div class="flex gap-6 border-b border-outline-variant/30 px-stack-lg max-sm:flex-wrap">
                    <button
                        class="tab-btn cursor-pointer py-4 font-h3 text-h3 transition-colors"
                        :class="
                            activeTab === 'utama'
                                ? 'border-b-2 border-primary text-primary'
                                : 'border-b-2 border-transparent text-on-surface-variant hover:text-on-surface'
                        "
                        type="button"
                        @click="activeTab = 'utama'"
                    >
                        Data Utama
                    </button>
                    <button
                        class="tab-btn cursor-pointer py-4 font-h3 text-h3 transition-colors"
                        :class="
                            activeTab === 'tambahan'
                                ? 'border-b-2 border-primary text-primary'
                                : 'border-b-2 border-transparent text-on-surface-variant hover:text-on-surface'
                        "
                        type="button"
                        @click="activeTab = 'tambahan'"
                    >
                        Data Tambahan
                    </button>
                    <button
                        class="tab-btn cursor-pointer py-4 font-h3 text-h3 transition-colors"
                        :class="
                            activeTab === 'ortu'
                                ? 'border-b-2 border-primary text-primary'
                                : 'border-b-2 border-transparent text-on-surface-variant hover:text-on-surface'
                        "
                        type="button"
                        @click="activeTab = 'ortu'"
                    >
                        Data Orang Tua
                    </button>
                </div>

                <div class="p-stack-lg">
                    <!-- Tab: Data Utama -->
                    <div v-show="activeTab === 'utama'" class="flex flex-col gap-stack-lg">
                        <div>
                            <h3 class="mb-stack-md flex items-center gap-2 font-h3 text-h3 text-on-surface">
                                <span class="material-symbols-outlined text-xl text-primary">login</span>
                                Akun Login
                            </h3>
                            <div class="grid grid-cols-1 gap-stack-md md:grid-cols-2">
                                <div class="flex flex-col gap-1">
                                    <label class="font-label-sm text-label-sm text-on-surface">Nama Lengkap <span class="text-error">*</span></label>
                                    <input v-model="form.name" type="text" :class="[fieldBase, form.errors.name && 'border-red-500']" placeholder="Nama sesuai dokumen" />
                                    <span v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="font-label-sm text-label-sm text-on-surface">Username <span class="text-error">*</span></label>
                                    <input v-model="form.username" type="text" :class="[fieldBase, form.errors.username && 'border-red-500']" placeholder="Username untuk login" />
                                    <span v-if="form.errors.username" class="text-xs text-red-500">{{ form.errors.username }}</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="font-label-sm text-label-sm text-on-surface">Email</label>
                                    <input v-model="form.email" type="email" :class="[fieldBase, form.errors.email && 'border-red-500']" placeholder="alamat@email.com" />
                                    <span v-if="form.errors.email" class="text-xs text-red-500">{{ form.errors.email }}</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="font-label-sm text-label-sm text-on-surface">
                                        Password {{ mode === 'edit' ? '(kosongkan jika tidak diubah)' : '(opsional, default: NIS)' }}
                                    </label>
                                    <input v-model="form.password" type="password" :class="[fieldBase, form.errors.password && 'border-red-500']" placeholder="Minimal 6 karakter" />
                                    <span v-if="form.errors.password" class="text-xs text-red-500">{{ form.errors.password }}</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="mb-stack-md flex items-center gap-2 font-h3 text-h3 text-on-surface">
                                <span class="material-symbols-outlined text-xl text-primary">badge</span>
                                Data Utama Siswa
                            </h3>
                            <div class="grid grid-cols-1 gap-stack-md md:grid-cols-2">
                                <div class="flex flex-col gap-1">
                                    <label class="font-label-sm text-label-sm text-on-surface">NIS <span class="text-error">*</span></label>
                                    <input v-model="form.nis" type="text" :class="[fieldBase, form.errors.nis && 'border-red-500']" placeholder="Nomor Induk Siswa" />
                                    <span v-if="form.errors.nis" class="text-xs text-red-500">{{ form.errors.nis }}</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="font-label-sm text-label-sm text-on-surface">NISN</label>
                                    <input v-model="form.nisn" type="text" :class="[fieldBase, form.errors.nisn && 'border-red-500']" placeholder="Nomor Induk Siswa Nasional" />
                                    <span v-if="form.errors.nisn" class="text-xs text-red-500">{{ form.errors.nisn }}</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="font-label-sm text-label-sm text-on-surface">Jenis Kelamin</label>
                                    <div class="relative">
                                        <select v-model="form.gender" :class="[fieldBase, 'appearance-none pr-10', form.errors.gender && 'border-red-500']">
                                            <option value="">Pilih jenis kelamin</option>
                                            <option value="male">Laki-laki</option>
                                            <option value="female">Perempuan</option>
                                        </select>
                                        <span class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-outline">expand_more</span>
                                    </div>
                                    <span v-if="form.errors.gender" class="text-xs text-red-500">{{ form.errors.gender }}</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="font-label-sm text-label-sm text-on-surface">Agama</label>
                                    <div class="relative">
                                        <select v-model="form.religion" :class="[fieldBase, 'appearance-none pr-10', form.errors.religion && 'border-red-500']">
                                            <option value="">Pilih agama</option>
                                            <option v-if="showReligionOtherPreset" :value="form.religion">{{ form.religion }}</option>
                                            <option value="Islam">Islam</option>
                                            <option value="Kristen">Kristen</option>
                                            <option value="Katolik">Katolik</option>
                                            <option value="Hindu">Hindu</option>
                                            <option value="Buddha">Buddha</option>
                                            <option value="Konghucu">Konghucu</option>
                                        </select>
                                        <span class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-outline">expand_more</span>
                                    </div>
                                    <span v-if="form.errors.religion" class="text-xs text-red-500">{{ form.errors.religion }}</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="font-label-sm text-label-sm text-on-surface">Tempat Lahir</label>
                                    <input v-model="form.birth_place" type="text" :class="[fieldBase, form.errors.birth_place && 'border-red-500']" placeholder="Kota kelahiran" />
                                    <span v-if="form.errors.birth_place" class="text-xs text-red-500">{{ form.errors.birth_place }}</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="font-label-sm text-label-sm text-on-surface">Tanggal Lahir</label>
                                    <input v-model="form.birth_date" type="date" :class="[fieldBase, form.errors.birth_date && 'border-red-500']" />
                                    <span v-if="form.errors.birth_date" class="text-xs text-red-500">{{ form.errors.birth_date }}</span>
                                </div>
                                <div class="flex flex-col gap-1 md:col-span-2">
                                    <label class="font-label-sm text-label-sm text-on-surface">Nama wali (catatan cepat)</label>
                                    <input v-model="form.parent_name" type="text" :class="[fieldBase, form.errors.parent_name && 'border-red-500']" placeholder="Opsional" />
                                    <span v-if="form.errors.parent_name" class="text-xs text-red-500">{{ form.errors.parent_name }}</span>
                                </div>
                                <div class="flex flex-col gap-1 md:col-span-2">
                                    <label class="font-label-sm text-label-sm text-on-surface">Telepon wali (catatan cepat)</label>
                                    <input v-model="form.parent_phone" type="text" :class="[fieldBase, form.errors.parent_phone && 'border-red-500']" placeholder="Opsional" />
                                    <span v-if="form.errors.parent_phone" class="text-xs text-red-500">{{ form.errors.parent_phone }}</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="mb-stack-md flex items-center gap-2 font-h3 text-h3 text-on-surface">
                                <span class="material-symbols-outlined text-xl text-primary">class</span>
                                Penempatan Kelas
                            </h3>
                            <div class="grid grid-cols-1 gap-stack-md md:grid-cols-2">
                                <div class="flex flex-col gap-1">
                                    <label class="font-label-sm text-label-sm text-on-surface">Kelas</label>
                                    <div class="relative">
                                        <select v-model="form.class_id" :class="[fieldBase, 'appearance-none pr-10', form.errors.class_id && 'border-red-500']">
                                            <option value="">Pilih kelas</option>
                                            <option v-for="item in classes" :key="item.id" :value="item.id">{{ item.name }}</option>
                                        </select>
                                        <span class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-outline">expand_more</span>
                                    </div>
                                    <span v-if="form.errors.class_id" class="text-xs text-red-500">{{ form.errors.class_id }}</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="font-label-sm text-label-sm text-on-surface">Tahun Ajaran</label>
                                    <div class="relative">
                                        <select v-model="form.school_year_id" :class="[fieldBase, 'appearance-none pr-10', form.errors.school_year_id && 'border-red-500']">
                                            <option value="">Pilih tahun ajaran</option>
                                            <option v-for="item in schoolYears" :key="item.id" :value="item.id">{{ item.name }}</option>
                                        </select>
                                        <span class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-outline">expand_more</span>
                                    </div>
                                    <span v-if="form.errors.school_year_id" class="text-xs text-red-500">{{ form.errors.school_year_id }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Data Tambahan -->
                    <div v-show="activeTab === 'tambahan'" class="flex flex-col gap-stack-lg">
                        <h3 class="mb-stack-md flex items-center gap-2 font-h3 text-h3 text-on-surface">
                            <span class="material-symbols-outlined text-xl text-primary">contact_mail</span>
                            Data Tambahan
                        </h3>
                        <div class="flex flex-col items-start gap-stack-lg md:flex-row">
                            <div class="flex w-full shrink-0 flex-col items-center md:w-48">
                                <label class="group relative mb-4 block h-32 w-32 cursor-pointer overflow-hidden rounded-full border-4 border-surface-variant bg-surface-container-low">
                                    <img
                                        v-if="profilePhotoDisplayUrl"
                                        :src="profilePhotoDisplayUrl"
                                        alt="Foto formal"
                                        class="h-full w-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="flex h-full w-full items-center justify-center bg-surface-container-low text-on-surface-variant"
                                    >
                                        <span class="material-symbols-outlined text-[40px]">person</span>
                                    </div>
                                    <div
                                        class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 transition-opacity group-hover:opacity-100"
                                    >
                                        <span class="material-symbols-outlined text-white">photo_camera</span>
                                    </div>
                                    <input type="file" accept="image/*" class="sr-only" @change="onProfilePhotoChange" />
                                </label>
                                <span class="font-label-sm text-label-sm text-on-surface-variant">Unggah foto formal</span>
                                <label
                                    v-if="mode === 'edit' && profilePhotoExistingUrl"
                                    class="mt-2 flex cursor-pointer items-center gap-2 text-body-sm text-on-surface-variant"
                                >
                                    <input v-model="form.remove_profile_photo" type="checkbox" class="rounded border-outline-variant" />
                                    Hapus foto tersimpan
                                </label>
                                <span v-if="form.errors.profile_photo" class="mt-1 text-xs text-red-500">{{ form.errors.profile_photo }}</span>
                            </div>
                            <div class="flex min-w-0 flex-1 flex-col gap-stack-md">
                                <div class="flex flex-col gap-1">
                                    <label class="font-label-sm text-label-sm text-on-surface">Nomor Telepon Siswa</label>
                                    <input v-model="form.phone" type="tel" :class="[fieldBase, 'max-w-md', form.errors.phone && 'border-red-500']" placeholder="08xxxxxxxxxx" />
                                    <span v-if="form.errors.phone" class="text-xs text-red-500">{{ form.errors.phone }}</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="font-label-sm text-label-sm text-on-surface">Alamat singkat</label>
                                    <textarea
                                        v-model="form.address"
                                        rows="3"
                                        :class="[fieldBase, 'max-w-md resize-none', form.errors.address && 'border-red-500']"
                                        placeholder="Ringkasan alamat"
                                    />
                                    <span v-if="form.errors.address" class="text-xs text-red-500">{{ form.errors.address }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-stack-md md:grid-cols-2">
                            <div class="flex flex-col gap-1 md:col-span-2">
                                <label class="font-label-sm text-label-sm text-on-surface">Jalan / blok / nomor</label>
                                <textarea v-model="form.street_address" rows="2" :class="[fieldBase, 'resize-y', form.errors.street_address && 'border-red-500']" />
                                <span v-if="form.errors.street_address" class="text-xs text-red-500">{{ form.errors.street_address }}</span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="font-label-sm text-label-sm text-on-surface">RT</label>
                                <input v-model="form.rt" type="text" :class="[fieldBase, form.errors.rt && 'border-red-500']" />
                                <span v-if="form.errors.rt" class="text-xs text-red-500">{{ form.errors.rt }}</span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="font-label-sm text-label-sm text-on-surface">RW</label>
                                <input v-model="form.rw" type="text" :class="[fieldBase, form.errors.rw && 'border-red-500']" />
                                <span v-if="form.errors.rw" class="text-xs text-red-500">{{ form.errors.rw }}</span>
                            </div>
                            <div class="md:col-span-2">
                                <h4 class="mb-stack-sm font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Wilayah (master data)</h4>
                                <p class="mb-stack-md text-[12px] text-on-surface-variant">
                                    Pilih berjenjang. Nama provinsi, kab/kota, kecamatan, dan desa/kelurahan akan terisi otomatis untuk penyimpanan.
                                    <span v-if="wilayahLoading" class="ml-1 text-primary">Memuat…</span>
                                </p>
                                <div class="grid grid-cols-1 gap-stack-md md:grid-cols-2">
                                    <div class="flex flex-col gap-1">
                                        <label class="font-label-sm text-label-sm text-on-surface">Provinsi</label>
                                        <div class="relative">
                                            <select
                                                v-model="wilayahProvinceId"
                                                :class="[fieldBase, 'appearance-none pr-10']"
                                                @change="onWilayahProvinceChange"
                                            >
                                                <option value="">Pilih provinsi</option>
                                                <option v-for="opt in provinceOptions" :key="opt.id" :value="opt.id">{{ opt.name }}</option>
                                            </select>
                                            <span class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-outline">expand_more</span>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="font-label-sm text-label-sm text-on-surface">Kabupaten / Kota</label>
                                        <div class="relative">
                                            <select
                                                v-model="wilayahRegencyId"
                                                :class="[fieldBase, 'appearance-none pr-10']"
                                                :disabled="!wilayahProvinceId"
                                                @change="onWilayahRegencyChange"
                                            >
                                                <option value="">Pilih kabupaten/kota</option>
                                                <option v-for="opt in regencyOptions" :key="opt.id" :value="opt.id">{{ opt.name }}</option>
                                            </select>
                                            <span class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-outline">expand_more</span>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="font-label-sm text-label-sm text-on-surface">Kecamatan</label>
                                        <div class="relative">
                                            <select
                                                v-model="wilayahDistrictId"
                                                :class="[fieldBase, 'appearance-none pr-10']"
                                                :disabled="!wilayahRegencyId"
                                                @change="onWilayahDistrictChange"
                                            >
                                                <option value="">Pilih kecamatan</option>
                                                <option v-for="opt in districtOptions" :key="opt.id" :value="opt.id">{{ opt.name }}</option>
                                            </select>
                                            <span class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-outline">expand_more</span>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="font-label-sm text-label-sm text-on-surface">Desa / Kelurahan</label>
                                        <div class="relative">
                                            <select
                                                v-model="wilayahVillageId"
                                                :class="[fieldBase, 'appearance-none pr-10', form.errors.wilayah_village_id && 'border-red-500']"
                                                :disabled="!wilayahDistrictId"
                                            >
                                                <option value="">Pilih desa/kelurahan</option>
                                                <option v-for="opt in villageOptions" :key="opt.id" :value="opt.id">{{ opt.name }}</option>
                                            </select>
                                            <span class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-outline">expand_more</span>
                                        </div>
                                        <span v-if="form.errors.wilayah_village_id" class="text-xs text-red-500">{{ form.errors.wilayah_village_id }}</span>
                                    </div>
                                </div>
                                <p v-if="form.province && !form.wilayah_village_id" class="mt-2 text-[12px] text-on-surface-variant">
                                    Catatan: alamat tercatat tanpa kode desa master. Pilih desa di atas untuk menautkan data wilayah.
                                </p>
                            </div>
                            <div class="flex flex-col gap-1 md:col-span-2">
                                <label class="font-label-sm text-label-sm text-on-surface">Ringkasan wilayah (tersimpan)</label>
                                <div class="rounded-lg border border-outline-variant/60 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
                                    <span v-if="form.province || form.city || form.district || form.village">
                                        {{ [form.village, form.district, form.city, form.province].filter(Boolean).join(' — ') }}
                                    </span>
                                    <span v-else class="text-on-surface-variant">—</span>
                                </div>
                                <span v-if="form.errors.province" class="text-xs text-red-500">{{ form.errors.province }}</span>
                                <span v-if="form.errors.city" class="text-xs text-red-500">{{ form.errors.city }}</span>
                                <span v-if="form.errors.district" class="text-xs text-red-500">{{ form.errors.district }}</span>
                                <span v-if="form.errors.village" class="text-xs text-red-500">{{ form.errors.village }}</span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="font-label-sm text-label-sm text-on-surface">Kode pos</label>
                                <input v-model="form.postal_code" type="text" :class="[fieldBase, form.errors.postal_code && 'border-red-500']" />
                                <span v-if="form.errors.postal_code" class="text-xs text-red-500">{{ form.errors.postal_code }}</span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="font-label-sm text-label-sm text-on-surface">Golongan darah</label>
                                <input
                                    v-model="form.blood_type"
                                    type="text"
                                    maxlength="5"
                                    placeholder="A / B / AB / O"
                                    :class="[fieldBase, form.errors.blood_type && 'border-red-500']"
                                />
                                <span v-if="form.errors.blood_type" class="text-xs text-red-500">{{ form.errors.blood_type }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Orang tua -->
                    <div v-show="activeTab === 'ortu'" class="flex flex-col gap-stack-lg">
                        <h3 class="mb-stack-md flex items-center gap-2 font-h3 text-h3 text-on-surface">
                            <span class="material-symbols-outlined text-xl text-primary">family_restroom</span>
                            Data Orang Tua Siswa
                        </h3>

                        <div>
                            <h4 class="mb-stack-md font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Ibu / Wali perempuan</h4>
                            <div class="grid grid-cols-1 gap-stack-md md:grid-cols-2">
                                <div class="flex flex-col gap-1 md:col-span-2">
                                    <label class="font-label-sm text-label-sm text-on-surface">Nama lengkap</label>
                                    <input v-model="form.mother_full_name" type="text" :class="[fieldBase, form.errors.mother_full_name && 'border-red-500']" />
                                    <span v-if="form.errors.mother_full_name" class="text-xs text-red-500">{{ form.errors.mother_full_name }}</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="font-label-sm text-label-sm text-on-surface">Pekerjaan</label>
                                    <input v-model="form.mother_occupation" type="text" :class="[fieldBase, form.errors.mother_occupation && 'border-red-500']" />
                                    <span v-if="form.errors.mother_occupation" class="text-xs text-red-500">{{ form.errors.mother_occupation }}</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="font-label-sm text-label-sm text-on-surface">Penghasilan per bulan</label>
                                    <div class="relative">
                                        <select v-model="form.mother_monthly_income_band" :class="[fieldBase, 'appearance-none pr-10', form.errors.mother_monthly_income_band && 'border-red-500']">
                                            <option value="">Pilih rentang</option>
                                            <option v-for="[value, label] in incomeEntries" :key="value" :value="value">{{ label }}</option>
                                        </select>
                                        <span class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-outline">expand_more</span>
                                    </div>
                                    <span v-if="form.errors.mother_monthly_income_band" class="text-xs text-red-500">{{ form.errors.mother_monthly_income_band }}</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="font-label-sm text-label-sm text-on-surface">NIK (16 digit)</label>
                                    <input
                                        v-model="form.mother_nik"
                                        type="text"
                                        maxlength="16"
                                        inputmode="numeric"
                                        placeholder="16 digit"
                                        :class="[fieldBase, form.errors.mother_nik && 'border-red-500']"
                                    />
                                    <span v-if="form.errors.mother_nik" class="text-xs text-red-500">{{ form.errors.mother_nik }}</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="font-label-sm text-label-sm text-on-surface">Tanggal lahir</label>
                                    <input v-model="form.mother_birth_date" type="date" :class="[fieldBase, form.errors.mother_birth_date && 'border-red-500']" />
                                    <span v-if="form.errors.mother_birth_date" class="text-xs text-red-500">{{ form.errors.mother_birth_date }}</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h4 class="mb-stack-md font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Ayah / Wali laki-laki</h4>
                            <div class="grid grid-cols-1 gap-stack-md md:grid-cols-2">
                                <div class="flex flex-col gap-1 md:col-span-2">
                                    <label class="font-label-sm text-label-sm text-on-surface">Nama lengkap</label>
                                    <input v-model="form.father_full_name" type="text" :class="[fieldBase, form.errors.father_full_name && 'border-red-500']" />
                                    <span v-if="form.errors.father_full_name" class="text-xs text-red-500">{{ form.errors.father_full_name }}</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="font-label-sm text-label-sm text-on-surface">Pekerjaan</label>
                                    <input v-model="form.father_occupation" type="text" :class="[fieldBase, form.errors.father_occupation && 'border-red-500']" />
                                    <span v-if="form.errors.father_occupation" class="text-xs text-red-500">{{ form.errors.father_occupation }}</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="font-label-sm text-label-sm text-on-surface">Penghasilan per bulan</label>
                                    <div class="relative">
                                        <select v-model="form.father_monthly_income_band" :class="[fieldBase, 'appearance-none pr-10', form.errors.father_monthly_income_band && 'border-red-500']">
                                            <option value="">Pilih rentang</option>
                                            <option v-for="[value, label] in incomeEntries" :key="value" :value="value">{{ label }}</option>
                                        </select>
                                        <span class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-outline">expand_more</span>
                                    </div>
                                    <span v-if="form.errors.father_monthly_income_band" class="text-xs text-red-500">{{ form.errors.father_monthly_income_band }}</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="font-label-sm text-label-sm text-on-surface">NIK (16 digit)</label>
                                    <input
                                        v-model="form.father_nik"
                                        type="text"
                                        maxlength="16"
                                        inputmode="numeric"
                                        placeholder="16 digit"
                                        :class="[fieldBase, form.errors.father_nik && 'border-red-500']"
                                    />
                                    <span v-if="form.errors.father_nik" class="text-xs text-red-500">{{ form.errors.father_nik }}</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="font-label-sm text-label-sm text-on-surface">Tanggal lahir</label>
                                    <input v-model="form.father_birth_date" type="date" :class="[fieldBase, form.errors.father_birth_date && 'border-red-500']" />
                                    <span v-if="form.errors.father_birth_date" class="text-xs text-red-500">{{ form.errors.father_birth_date }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-stack-md border-t border-surface-variant pt-stack-sm">
                <Link
                    :href="index().url"
                    class="rounded-lg border border-outline px-6 py-2.5 font-label-sm text-label-sm text-on-surface transition-colors hover:bg-surface-container"
                >
                    Batal
                </Link>
                <button
                    :disabled="form.processing"
                    class="flex items-center gap-2 rounded-lg bg-primary px-6 py-2.5 font-label-sm text-label-sm text-on-primary shadow-sm transition-all hover:bg-primary/90 disabled:opacity-70"
                    type="submit"
                >
                    <span v-if="form.processing" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                    <span v-else class="material-symbols-outlined text-[18px]">save</span>
                    {{ mode === 'create' ? 'Simpan' : 'Simpan Perubahan' }}
                </button>
            </div>
        </form>
    </div>
</template>
