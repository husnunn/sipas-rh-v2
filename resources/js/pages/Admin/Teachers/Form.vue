<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { store, update, index } from '@/actions/App/Http/Controllers/Admin/TeacherController';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { useWilayahCascade } from '@/composables/useWilayahCascade';
import type { Subject, TeacherProfile } from '@/types/models';
import { computed, onBeforeUnmount, onMounted, ref, toRefs, watch } from 'vue';

defineOptions({ layout: AppSidebarLayout });

const props = defineProps<{
    mode: 'create' | 'edit';
    teacher: TeacherProfile | null;
    subjects: Subject[];
}>();

const activeTab = ref<'utama' | 'tambahan'>('utama');

const ext = computed(() => props.teacher?.extension);

const form = useForm({
    // "name" is reserved in Inertia shared props (was app title) and can prevent the user name from being submitted; use account_name.
    account_name: props.teacher?.user?.name ?? props.teacher?.full_name ?? '',
    username: props.teacher?.user?.username ?? '',
    email: props.teacher?.user?.email ?? '',
    password: '',
    nip: props.teacher?.nip ?? '',
    gender: props.teacher?.gender ?? '',
    phone: props.teacher?.phone ?? '',
    address: props.teacher?.address ?? '',
    birth_date: ext.value?.birth_date?.substring(0, 10) ?? '',
    birth_place: ext.value?.birth_place ?? '',
    street_address: ext.value?.street_address ?? '',
    rt: ext.value?.rt ?? '',
    rw: ext.value?.rw ?? '',
    province: ext.value?.province ?? '',
    city: ext.value?.city ?? '',
    district: ext.value?.district ?? '',
    village: ext.value?.village ?? '',
    wilayah_village_id: ext.value?.wilayah_village_id ?? '',
    postal_code: ext.value?.postal_code ?? '',
    religion: ext.value?.religion ?? '',
    blood_type: ext.value?.blood_type ?? '',
    profile_photo: null as File | null,
    remove_profile_photo: false,
    subject_ids: props.teacher?.subjects?.map((item) => item.id) ?? [],
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

const pageTitle = computed(() => (props.mode === 'create' ? 'Tambah Guru Baru' : 'Edit Data Guru'));

const pageSubtitle = computed(() =>
    props.mode === 'create'
        ? 'Lengkapi data guru untuk mendaftarkan ke sistem.'
        : 'Perbarui informasi akademik dan personal staf pengajar.',
);

const accountStatus = computed(() => {
    const u = props.teacher?.user as { is_active?: boolean } | undefined;

    if (typeof u?.is_active !== 'boolean') {
        return null;
    }

    return {
        label: u.is_active ? 'Aktif' : 'Nonaktif',
        active: u.is_active,
    };
});

const fieldBase =
    'w-full rounded-lg border border-outline-variant bg-surface-container-lowest px-4 py-2.5 font-body-md text-body-md text-on-surface outline-none transition-shadow focus:border-primary focus:ring-1 focus:ring-primary';

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

/**
 * Inertia only serializes keys that exist on the initial form object (`defaults`).
 * Only use multipart when a new file is selected — otherwise send JSON so all fields
 * reliably reach Laravel (some PHP/host setups mishandle multipart PUT).
 */
const submit = () => {
    const needsMultipart = form.profile_photo instanceof File;
    const options = needsMultipart ? { forceFormData: true } : {};

    if (props.mode === 'create') {
        form.post(store().url, options);

        return;
    }

    if (props.teacher) {
        form.put(update(props.teacher).url, options);
    }
};
</script>

<template>
    <Head :title="mode === 'create' ? 'Tambah Guru' : 'Edit Guru'" />
    <div class="flex w-full flex-col gap-stack-lg pb-stack-lg">
        <Link
            :href="index().url"
            class="flex w-fit items-center gap-1 text-sm font-medium text-on-surface-variant transition-colors hover:text-primary"
        >
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            Kembali ke Daftar Guru
        </Link>

        <form class="flex flex-col gap-stack-lg" @submit.prevent="submit">
            <div class="flex flex-col gap-stack-md lg:flex-row lg:items-end lg:justify-between">
                <div class="min-w-0 flex-1">
                    <h1 class="m-0 font-h1 text-h1 text-on-background">{{ pageTitle }}</h1>
                    <p class="mt-1 font-body-md text-body-md text-on-surface-variant">{{ pageSubtitle }}</p>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end">
                    <div
                        v-if="mode === 'edit' && accountStatus"
                        class="flex items-center gap-stack-sm rounded-full border border-outline-variant bg-surface-container px-3 py-1.5 text-sm text-on-surface-variant"
                    >
                        <span class="block h-2 w-2 rounded-full" :class="accountStatus.active ? 'bg-primary' : 'bg-outline-variant'" />
                        Status:
                        <span class="font-semibold text-on-surface">{{ accountStatus.label }}</span>
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
            </div>

            <div class="flex gap-stack-sm border-b border-outline-variant">
                <button
                    class="tab-btn flex items-center gap-2 rounded-t-lg px-4 py-3 font-label-sm text-label-sm transition-colors"
                    :class="
                        activeTab === 'utama'
                            ? 'border-b-2 border-primary bg-primary/10 text-primary'
                            : 'border-b-2 border-transparent text-on-surface-variant hover:bg-surface-container hover:text-on-surface'
                    "
                    type="button"
                    @click="activeTab = 'utama'"
                >
                    <span class="material-symbols-outlined text-[20px]">badge</span>
                    Data Utama
                </button>
                <button
                    class="tab-btn flex items-center gap-2 rounded-t-lg px-4 py-3 font-label-sm text-label-sm transition-colors"
                    :class="
                        activeTab === 'tambahan'
                            ? 'border-b-2 border-primary bg-primary/10 text-primary'
                            : 'border-b-2 border-transparent text-on-surface-variant hover:bg-surface-container hover:text-on-surface'
                    "
                    type="button"
                    @click="activeTab = 'tambahan'"
                >
                    <span class="material-symbols-outlined text-[20px]">contact_page</span>
                    Data Tambahan
                </button>
            </div>

            <!-- Tab: Data Utama -->
            <div
                v-show="activeTab === 'utama'"
                class="overflow-hidden rounded-xl border border-surface-variant bg-surface shadow-sm"
            >
                <div class="p-stack-lg">
                    <div class="mb-stack-lg">
                        <h3 class="mb-stack-md flex items-center gap-2 font-h3 text-h3 text-on-surface">
                            <span class="material-symbols-outlined text-xl text-primary">login</span>
                            Akun Login
                        </h3>
                        <div class="grid grid-cols-1 gap-stack-lg md:grid-cols-2">
                            <div>
                                <label class="mb-stack-sm block font-label-sm text-label-sm text-on-surface">Nama Lengkap <span class="text-error">*</span></label>
                                <input v-model="form.account_name" type="text" :class="[fieldBase, form.errors.account_name && 'border-red-500']" placeholder="Nama lengkap" />
                                <span v-if="form.errors.account_name" class="mt-1 block text-xs text-red-500">{{ form.errors.account_name }}</span>
                            </div>
                            <div>
                                <label class="mb-stack-sm block font-label-sm text-label-sm text-on-surface">Username <span class="text-error">*</span></label>
                                <input v-model="form.username" type="text" :class="[fieldBase, form.errors.username && 'border-red-500']" placeholder="Username untuk login" />
                                <span v-if="form.errors.username" class="mt-1 block text-xs text-red-500">{{ form.errors.username }}</span>
                            </div>
                            <div>
                                <label class="mb-stack-sm block font-label-sm text-label-sm text-on-surface">Email</label>
                                <input v-model="form.email" type="email" :class="[fieldBase, form.errors.email && 'border-red-500']" placeholder="alamat@email.com" />
                                <span v-if="form.errors.email" class="mt-1 block text-xs text-red-500">{{ form.errors.email }}</span>
                            </div>
                            <div>
                                <label class="mb-stack-sm block font-label-sm text-label-sm text-on-surface">
                                    Password {{ mode === 'edit' ? '(kosongkan jika tidak diubah)' : '(opsional, default: NIP/username)' }}
                                </label>
                                <input v-model="form.password" type="password" :class="[fieldBase, form.errors.password && 'border-red-500']" placeholder="Minimal 6 karakter" />
                                <span v-if="form.errors.password" class="mt-1 block text-xs text-red-500">{{ form.errors.password }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-stack-lg md:grid-cols-2">
                        <div>
                            <label class="mb-stack-sm block font-label-sm text-label-sm text-on-surface">NIP</label>
                            <input v-model="form.nip" type="text" :class="[fieldBase, form.errors.nip && 'border-red-500']" placeholder="Nomor Induk Pegawai" />
                            <span v-if="form.errors.nip" class="mt-1 block text-xs text-red-500">{{ form.errors.nip }}</span>
                        </div>
                        <div>
                            <label class="mb-stack-sm block font-label-sm text-label-sm text-on-surface">Jenis Kelamin</label>
                            <div class="relative">
                                <select v-model="form.gender" :class="[fieldBase, 'appearance-none pr-10', form.errors.gender && 'border-red-500']">
                                    <option value="">Pilih jenis kelamin</option>
                                    <option value="male">Laki-laki</option>
                                    <option value="female">Perempuan</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-on-surface-variant">
                                    <span class="material-symbols-outlined text-[20px]">expand_more</span>
                                </div>
                            </div>
                            <span v-if="form.errors.gender" class="mt-1 block text-xs text-red-500">{{ form.errors.gender }}</span>
                        </div>
                    </div>

                    <div class="mt-stack-lg border-t border-outline-variant/40 pt-stack-lg">
                        <h3 class="mb-stack-md font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Mata Pelajaran yang Diampu</h3>
                        <p class="mb-3 text-xs text-on-surface-variant">Pilih satu atau lebih. Setiap mapel maksimal diajar oleh 2 guru.</p>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            <label
                                v-for="item in subjects"
                                :key="item.id"
                                class="flex cursor-pointer items-center gap-3 rounded-lg border px-4 py-3 transition-colors"
                                :class="form.subject_ids.includes(item.id) ? 'border-primary bg-primary/5' : 'border-outline-variant hover:bg-surface-container-low'"
                            >
                                <input v-model="form.subject_ids" type="checkbox" :value="item.id" class="h-5 w-5 rounded border-outline-variant text-primary focus:ring-primary" />
                                <div class="flex flex-col">
                                    <span class="font-medium text-body-md text-on-surface">{{ item.name }}</span>
                                    <span class="text-xs text-on-surface-variant">{{ item.code }}</span>
                                </div>
                            </label>
                        </div>
                        <span v-if="form.errors.subject_ids" class="mt-1 block text-xs text-red-500">{{ form.errors.subject_ids }}</span>
                    </div>
                </div>
            </div>

            <!-- Tab: Data Tambahan -->
            <div
                v-show="activeTab === 'tambahan'"
                class="overflow-hidden rounded-xl border border-surface-variant bg-surface shadow-sm"
            >
                <div class="p-stack-lg">
                    <div class="grid grid-cols-1 gap-stack-lg lg:grid-cols-3">
                        <div class="flex flex-col gap-stack-lg lg:col-span-2">
                            <div class="grid grid-cols-1 gap-stack-lg md:grid-cols-2">
                                <div>
                                    <label class="mb-stack-sm block font-label-sm text-label-sm text-on-surface">Tempat Lahir</label>
                                    <input v-model="form.birth_place" type="text" :class="[fieldBase, form.errors.birth_place && 'border-red-500']" />
                                    <span v-if="form.errors.birth_place" class="mt-1 block text-xs text-red-500">{{ form.errors.birth_place }}</span>
                                </div>
                                <div>
                                    <label class="mb-stack-sm block font-label-sm text-label-sm text-on-surface">Tanggal Lahir</label>
                                    <input v-model="form.birth_date" type="date" :class="[fieldBase, form.errors.birth_date && 'border-red-500']" />
                                    <span v-if="form.errors.birth_date" class="mt-1 block text-xs text-red-500">{{ form.errors.birth_date }}</span>
                                </div>
                            </div>

                            <div>
                                <label class="mb-stack-sm block font-label-sm text-label-sm text-on-surface">Alamat Lengkap</label>
                                <textarea
                                    v-model="form.street_address"
                                    rows="3"
                                    :class="[fieldBase, 'resize-y', form.errors.street_address && 'border-red-500']"
                                    placeholder="Jalan, RT/RW, kelurahan, kecamatan, kota/kabupaten"
                                />
                                <span v-if="form.errors.street_address" class="mt-1 block text-xs text-red-500">{{ form.errors.street_address }}</span>
                            </div>

                            <div class="grid grid-cols-1 gap-stack-lg md:grid-cols-2">
                                <div>
                                    <label class="mb-stack-sm block font-label-sm text-label-sm text-on-surface">RT</label>
                                    <input v-model="form.rt" type="text" :class="[fieldBase, form.errors.rt && 'border-red-500']" />
                                    <span v-if="form.errors.rt" class="mt-1 block text-xs text-red-500">{{ form.errors.rt }}</span>
                                </div>
                                <div>
                                    <label class="mb-stack-sm block font-label-sm text-label-sm text-on-surface">RW</label>
                                    <input v-model="form.rw" type="text" :class="[fieldBase, form.errors.rw && 'border-red-500']" />
                                    <span v-if="form.errors.rw" class="mt-1 block text-xs text-red-500">{{ form.errors.rw }}</span>
                                </div>
                                <div class="md:col-span-2">
                                    <h4 class="mb-stack-sm font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Wilayah (master data)</h4>
                                    <p class="mb-stack-md text-[12px] text-on-surface-variant">
                                        Pilih berjenjang. Nama wilayah tersimpan otomatis.
                                        <span v-if="wilayahLoading" class="ml-1 text-primary">Memuat…</span>
                                    </p>
                                    <div class="grid grid-cols-1 gap-stack-lg md:grid-cols-2">
                                        <div>
                                            <label class="mb-stack-sm block font-label-sm text-label-sm text-on-surface">Provinsi</label>
                                            <div class="relative">
                                                <select
                                                    v-model="wilayahProvinceId"
                                                    :class="[fieldBase, 'appearance-none pr-10']"
                                                    @change="onWilayahProvinceChange"
                                                >
                                                    <option value="">Pilih provinsi</option>
                                                    <option v-for="opt in provinceOptions" :key="opt.id" :value="opt.id">{{ opt.name }}</option>
                                                </select>
                                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-on-surface-variant">
                                                    <span class="material-symbols-outlined text-[20px]">expand_more</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="mb-stack-sm block font-label-sm text-label-sm text-on-surface">Kabupaten / Kota</label>
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
                                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-on-surface-variant">
                                                    <span class="material-symbols-outlined text-[20px]">expand_more</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="mb-stack-sm block font-label-sm text-label-sm text-on-surface">Kecamatan</label>
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
                                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-on-surface-variant">
                                                    <span class="material-symbols-outlined text-[20px]">expand_more</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="mb-stack-sm block font-label-sm text-label-sm text-on-surface">Desa / Kelurahan</label>
                                            <div class="relative">
                                                <select
                                                    v-model="wilayahVillageId"
                                                    :class="[fieldBase, 'appearance-none pr-10', form.errors.wilayah_village_id && 'border-red-500']"
                                                    :disabled="!wilayahDistrictId"
                                                >
                                                    <option value="">Pilih desa/kelurahan</option>
                                                    <option v-for="opt in villageOptions" :key="opt.id" :value="opt.id">{{ opt.name }}</option>
                                                </select>
                                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-on-surface-variant">
                                                    <span class="material-symbols-outlined text-[20px]">expand_more</span>
                                                </div>
                                            </div>
                                            <span v-if="form.errors.wilayah_village_id" class="mt-1 block text-xs text-red-500">{{ form.errors.wilayah_village_id }}</span>
                                        </div>
                                    </div>
                                    <p v-if="form.province && !form.wilayah_village_id" class="mt-2 text-[12px] text-on-surface-variant">
                                        Alamat tercatat tanpa kode desa master. Pilih desa untuk menautkan data wilayah.
                                    </p>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="mb-stack-sm block font-label-sm text-label-sm text-on-surface">Ringkasan wilayah (tersimpan)</label>
                                    <div class="rounded-lg border border-outline-variant/60 bg-surface-container-low px-4 py-2.5 font-body-md text-body-md text-on-surface">
                                        <span v-if="form.province || form.city || form.district || form.village">
                                            {{ [form.village, form.district, form.city, form.province].filter(Boolean).join(' — ') }}
                                        </span>
                                        <span v-else class="text-on-surface-variant">—</span>
                                    </div>
                                    <span v-if="form.errors.province" class="mt-1 block text-xs text-red-500">{{ form.errors.province }}</span>
                                    <span v-if="form.errors.city" class="mt-1 block text-xs text-red-500">{{ form.errors.city }}</span>
                                    <span v-if="form.errors.district" class="mt-1 block text-xs text-red-500">{{ form.errors.district }}</span>
                                    <span v-if="form.errors.village" class="mt-1 block text-xs text-red-500">{{ form.errors.village }}</span>
                                </div>
                                <div>
                                    <label class="mb-stack-sm block font-label-sm text-label-sm text-on-surface">Kode pos</label>
                                    <input v-model="form.postal_code" type="text" :class="[fieldBase, form.errors.postal_code && 'border-red-500']" />
                                    <span v-if="form.errors.postal_code" class="mt-1 block text-xs text-red-500">{{ form.errors.postal_code }}</span>
                                </div>
                                <div>
                                    <label class="mb-stack-sm block font-label-sm text-label-sm text-on-surface">Agama</label>
                                    <input v-model="form.religion" type="text" :class="[fieldBase, form.errors.religion && 'border-red-500']" />
                                    <span v-if="form.errors.religion" class="mt-1 block text-xs text-red-500">{{ form.errors.religion }}</span>
                                </div>
                                <div>
                                    <label class="mb-stack-sm block font-label-sm text-label-sm text-on-surface">Golongan darah</label>
                                    <input v-model="form.blood_type" type="text" maxlength="5" :class="[fieldBase, form.errors.blood_type && 'border-red-500']" />
                                    <span v-if="form.errors.blood_type" class="mt-1 block text-xs text-red-500">{{ form.errors.blood_type }}</span>
                                </div>
                            </div>

                            <div>
                                <label class="mb-stack-sm block font-label-sm text-label-sm text-on-surface">Alamat ringkas</label>
                                <input v-model="form.address" type="text" :class="[fieldBase, form.errors.address && 'border-red-500']" placeholder="Ringkasan untuk catatan cepat" />
                                <span v-if="form.errors.address" class="mt-1 block text-xs text-red-500">{{ form.errors.address }}</span>
                            </div>

                            <div class="grid grid-cols-1 gap-stack-lg md:grid-cols-2">
                                <div>
                                    <label class="mb-stack-sm block font-label-sm text-label-sm text-on-surface">Email aktif</label>
                                    <div class="relative">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-on-surface-variant">
                                            <span class="material-symbols-outlined text-[18px]">mail</span>
                                        </div>
                                        <input v-model="form.email" type="email" :class="[fieldBase, 'pl-10', form.errors.email && 'border-red-500']" />
                                    </div>
                                    <span v-if="form.errors.email" class="mt-1 block text-xs text-red-500">{{ form.errors.email }}</span>
                                </div>
                                <div>
                                    <label class="mb-stack-sm block font-label-sm text-label-sm text-on-surface">Nomor Telepon / WhatsApp</label>
                                    <div class="relative">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-on-surface-variant">
                                            <span class="material-symbols-outlined text-[18px]">call</span>
                                        </div>
                                        <input v-model="form.phone" type="tel" :class="[fieldBase, 'pl-10', form.errors.phone && 'border-red-500']" placeholder="08xxxxxxxxxx" />
                                    </div>
                                    <span v-if="form.errors.phone" class="mt-1 block text-xs text-red-500">{{ form.errors.phone }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-1">
                            <label class="mb-stack-sm block font-label-sm text-label-sm text-on-surface">Foto profil formal</label>
                            <label
                                class="group flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-outline-variant bg-surface-container-lowest p-6 text-center transition-colors hover:border-primary hover:bg-surface"
                            >
                                <div
                                    class="relative mb-4 h-24 w-24 overflow-hidden rounded-full border-2 border-surface shadow-sm group-hover:opacity-90"
                                >
                                    <img
                                        v-if="profilePhotoDisplayUrl"
                                        :src="profilePhotoDisplayUrl"
                                        alt="Foto guru"
                                        class="h-full w-full object-cover"
                                    />
                                    <div v-else class="flex h-full w-full items-center justify-center bg-surface-container-low text-on-surface-variant">
                                        <span class="material-symbols-outlined text-[36px]">person</span>
                                    </div>
                                    <div
                                        class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 transition-opacity group-hover:opacity-100"
                                    >
                                        <span class="material-symbols-outlined text-white">edit</span>
                                    </div>
                                </div>
                                <span class="material-symbols-outlined mb-2 text-[32px] text-on-surface-variant transition-colors group-hover:text-primary">cloud_upload</span>
                                <p class="mb-1 font-label-sm text-label-sm text-primary">Klik untuk mengunggah</p>
                                <p class="font-body-md text-[12px] text-on-surface-variant">Gambar, maks. 5 MB</p>
                                <input type="file" accept="image/*" class="hidden" @change="onProfilePhotoChange" />
                            </label>
                            <label v-if="mode === 'edit' && profilePhotoExistingUrl" class="mt-3 flex items-center gap-2 text-body-sm text-on-surface-variant">
                                <input v-model="form.remove_profile_photo" type="checkbox" class="rounded border-outline-variant" />
                                Hapus foto tersimpan
                            </label>
                            <span v-if="form.errors.profile_photo" class="mt-2 block text-xs text-red-500">{{ form.errors.profile_photo }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="flex items-center justify-end gap-stack-md border-t border-surface-variant pt-stack-sm pb-stack-lg"
            >
                <Link
                    :href="index().url"
                    class="rounded-lg border border-outline px-6 py-2.5 font-label-sm text-label-sm text-on-surface transition-colors hover:bg-surface-container active:scale-95"
                >
                    Batal
                </Link>
                <button
                    :disabled="form.processing"
                    class="flex items-center gap-2 rounded-lg bg-primary px-6 py-2.5 font-label-sm text-label-sm text-on-primary shadow-sm transition-all hover:bg-primary/90 hover:shadow disabled:opacity-70 active:scale-95"
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
