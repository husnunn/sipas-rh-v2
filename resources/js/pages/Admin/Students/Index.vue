<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import {
    bulkDestroy,
    create,
    destroy,
    edit,
    importForm,
    index,
    show,
} from '@/actions/App/Http/Controllers/Admin/StudentController';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import TablePagination from '@/components/TablePagination.vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { firstVisitErrorMessage, isFlashErrorPage } from '@/lib/inertiaVisitHelpers';
import type { PaginatedData } from '@/types';
import type { ClassRoom, SchoolYear, StudentProfile } from '@/types/models';

defineOptions({
    layout: AppSidebarLayout,
});

/** Kosong = tidak memfilter (nilai option select). */
type UiFilters = {
    search: string;
    class_id: string;
    school_year_id: string;
    gender: string;
    account_status: string;
};

const props = defineProps<{
    students: PaginatedData<StudentProfile>;
    classOptions: Pick<ClassRoom, 'id' | 'name' | 'school_year_id'>[];
    schoolYears: Pick<SchoolYear, 'id' | 'name'>[];
    filters: {
        search?: string | null;
        class_id?: number | null;
        school_year_id?: number | null;
        gender?: string | null;
        account_status?: string | null;
    };
}>();

function fromServerFilters(f: typeof props.filters): UiFilters {
    return {
        search: f.search?.trim() ?? '',
        class_id: f.class_id != null ? String(f.class_id) : '',
        school_year_id: f.school_year_id != null ? String(f.school_year_id) : '',
        gender: f.gender ?? '',
        account_status: f.account_status ?? '',
    };
}

const filterForm = ref<UiFilters>(fromServerFilters(props.filters));

watch(
    () => props.filters,
    (f) => {
        filterForm.value = fromServerFilters(f);
    },
    { deep: true },
);

function toQuery(f: UiFilters): Record<string, string> {
    const q: Record<string, string> = {};
    const search = f.search.trim();
    if (search !== '') {
        q.search = search;
    }
    if (f.class_id !== '') {
        q.class_id = f.class_id;
    }
    if (f.school_year_id !== '') {
        q.school_year_id = f.school_year_id;
    }
    if (f.gender !== '') {
        q.gender = f.gender;
    }
    if (f.account_status !== '') {
        q.account_status = f.account_status;
    }

    return q;
}

function applyFilters(patch: Partial<UiFilters> = {}): void {
    Object.assign(filterForm.value, patch);
    router.get(index().url, toQuery(filterForm.value), {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
}

function onSchoolYearChange(): void {
    const year = filterForm.value.school_year_id;
    if (filterForm.value.class_id !== '' && year !== '') {
        const meta = props.classOptions.find((c) => Number(c.id) === Number(filterForm.value.class_id));
        if (meta && String(meta.school_year_id) !== year) {
            filterForm.value.class_id = '';
        }
    }
    applyFilters();
}

const classSelectOptions = computed(() => {
    const year = filterForm.value.school_year_id;
    if (year === '') {
        return props.classOptions;
    }

    return props.classOptions.filter((c) => String(c.school_year_id) === year);
});

let searchDebounceTimer: ReturnType<typeof setTimeout> | undefined;
function queueSearchFilters(): void {
    if (searchDebounceTimer) {
        clearTimeout(searchDebounceTimer);
    }
    searchDebounceTimer = setTimeout(() => applyFilters(), 350);
}

function genderLabel(gender?: string | null): string {
    if (gender === 'male') {
        return 'Laki-laki';
    }
    if (gender === 'female') {
        return 'Perempuan';
    }

    return '—';
}

const selectedIds = ref<number[]>([]);

const allSelected = computed({
    get: () => props.students.data.length > 0 && selectedIds.value.length === props.students.data.length,
    set: (value) => {
        if (value) {
            selectedIds.value = props.students.data.map((item) => item.id);
        } else {
            selectedIds.value = [];
        }
    },
});

watch(
    () => props.students.data,
    () => {
        selectedIds.value = selectedIds.value.filter((id) => props.students.data.some((r) => r.id === id));
    },
);

const confirmBulkDeleteRef = ref<InstanceType<typeof ConfirmDialog> | null>(null);

const bulkDelete = async () => {
    const confirmed = await confirmBulkDeleteRef.value?.open();

    if (!confirmed) {
        return;
    }

    router.delete(bulkDestroy().url, {
        data: { ids: selectedIds.value },
        onSuccess: (page) => {
            if (isFlashErrorPage(page)) {
                return;
            }

            selectedIds.value = [];
        },
        onError: (errors) => {
            toast.error(
                firstVisitErrorMessage(
                    errors as Record<string, string | string[]>,
                    'Gagal menghapus data siswa. Periksa data lalu coba lagi.',
                ),
            );
        },
    });
};

const confirmDeleteRef = ref<InstanceType<typeof ConfirmDialog> | null>(null);

const handleDelete = async (student: StudentProfile) => {
    const confirmed = await confirmDeleteRef.value?.open();

    if (!confirmed) {
        return;
    }

    router.delete(destroy(student).url, {
        onError: (errors) => {
            toast.error(
                firstVisitErrorMessage(
                    errors as Record<string, string | string[]>,
                    'Gagal menghapus data siswa. Periksa data lalu coba lagi.',
                ),
            );
        },
    });
};
</script>

<template>
    <Head title="Manajemen Siswa" />

    <div class="flex flex-col gap-stack-lg max-w-7xl mx-auto w-full">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
            <div>
                <h2 class="font-h2 text-h2 text-on-surface">Manajemen Siswa</h2>
                <p class="font-body-md text-body-md text-on-surface-variant mt-1">
                    Cari dan saring siswa berdasarkan kelas, tahun ajaran, jenis kelamin, dan status akun.
                </p>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                <Link
                    :href="importForm().url"
                    class="bg-surface-container-low hover:bg-surface-container text-on-surface font-label-sm text-label-sm px-5 py-2.5 rounded-lg flex items-center justify-center gap-2 border border-outline-variant shadow-[0_4px_6px_rgba(0,0,0,0.05)] transition-all">
                    <span class="material-symbols-outlined text-[18px]">upload_file</span>
                    Import Excel
                </Link>
                <Link
                    :href="create().url"
                    class="bg-primary hover:bg-primary-container text-on-primary font-label-sm text-label-sm px-5 py-2.5 rounded-lg flex items-center justify-center gap-2 shadow-[0_4px_6px_rgba(0,0,0,0.05)] transition-all">
                    <span class="material-symbols-outlined text-[18px]">person_add</span>
                    Tambah Siswa
                </Link>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-gutter xl:grid-cols-12">
            <div
                class="xl:col-span-4 rounded-xl border border-surface-container-highest bg-surface-container-lowest p-2 shadow-[0_4px_6px_rgba(0,0,0,0.05)]">
                <div class="relative flex items-center">
                    <span class="material-symbols-outlined pointer-events-none absolute left-4 text-outline">search</span>
                    <input
                        v-model="filterForm.search"
                        class="w-full border-none bg-transparent py-3 pl-11 pr-4 font-body-md text-body-md text-on-surface outline-none placeholder:text-outline-variant focus:ring-0"
                        placeholder="Nama, username, NIS, atau NISN…"
                        type="text"
                        @input="queueSearchFilters" />
                </div>
            </div>
            <div
                class="xl:col-span-8 flex flex-col gap-3 rounded-xl border border-surface-container-highest bg-surface-container-lowest p-3 shadow-[0_4px_6px_rgba(0,0,0,0.05)] sm:flex-row sm:flex-wrap">
                <div class="relative min-w-[160px] flex-1">
                    <label class="text-label-xs text-on-surface-variant mb-1 block px-0.5">Tahun ajaran</label>
                    <select
                        v-model="filterForm.school_year_id"
                        class="w-full appearance-none rounded-lg border border-outline-variant bg-surface-bright px-4 py-2.5 pr-10 font-body-md text-body-md text-on-surface outline-none transition-all focus:border-primary focus:ring-1 focus:ring-primary"
                        @change="onSchoolYearChange">
                        <option value="">Semua tahun ajaran</option>
                        <option v-for="y in schoolYears" :key="y.id" :value="String(y.id)">{{ y.name }}</option>
                    </select>
                    <span
                        class="material-symbols-outlined pointer-events-none absolute bottom-2.5 right-3 text-outline"
                        aria-hidden="true">
                        expand_more
                    </span>
                </div>
                <div class="relative min-w-[160px] flex-[1.2]">
                    <label class="text-label-xs text-on-surface-variant mb-1 block px-0.5">Kelas</label>
                    <select
                        v-model="filterForm.class_id"
                        class="w-full appearance-none rounded-lg border border-outline-variant bg-surface-bright px-4 py-2.5 pr-10 font-body-md text-body-md text-on-surface outline-none transition-all focus:border-primary focus:ring-1 focus:ring-primary"
                        @change="applyFilters()">
                        <option value="">Semua kelas</option>
                        <option v-for="c in classSelectOptions" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                    </select>
                    <span
                        class="material-symbols-outlined pointer-events-none absolute bottom-2.5 right-3 text-outline"
                        aria-hidden="true">
                        expand_more
                    </span>
                </div>
                <div class="relative min-w-[140px] flex-1">
                    <label class="text-label-xs text-on-surface-variant mb-1 block px-0.5">Jenis kelamin</label>
                    <select
                        v-model="filterForm.gender"
                        class="w-full appearance-none rounded-lg border border-outline-variant bg-surface-bright px-4 py-2.5 pr-10 font-body-md text-body-md text-on-surface outline-none transition-all focus:border-primary focus:ring-1 focus:ring-primary"
                        @change="applyFilters()">
                        <option value="">Semua</option>
                        <option value="male">Laki-laki</option>
                        <option value="female">Perempuan</option>
                    </select>
                    <span
                        class="material-symbols-outlined pointer-events-none absolute bottom-2.5 right-3 text-outline"
                        aria-hidden="true">
                        expand_more
                    </span>
                </div>
                <div class="relative min-w-[140px] flex-1">
                    <label class="text-label-xs text-on-surface-variant mb-1 block px-0.5">Status akun</label>
                    <select
                        v-model="filterForm.account_status"
                        class="w-full appearance-none rounded-lg border border-outline-variant bg-surface-bright px-4 py-2.5 pr-10 font-body-md text-body-md text-on-surface outline-none transition-all focus:border-primary focus:ring-1 focus:ring-primary"
                        @change="applyFilters()">
                        <option value="">Semua</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                    <span
                        class="material-symbols-outlined pointer-events-none absolute bottom-2.5 right-3 text-outline"
                        aria-hidden="true">
                        expand_more
                    </span>
                </div>
            </div>
        </div>

        <div
            class="flex flex-col overflow-hidden rounded-xl border border-surface-container-highest bg-surface-container-lowest shadow-[0_4px_6px_rgba(0,0,0,0.05)]">
            <div
                v-if="selectedIds.length > 0"
                class="flex items-center justify-between border-b border-surface-container-highest bg-primary-container/20 px-6 py-3">
                <span class="font-medium text-on-surface text-sm">{{ selectedIds.length }} data terpilih</span>
                <div class="flex gap-2">
                    <button
                        type="button"
                        class="font-label-sm text-label-sm text-error bg-error-container hover:bg-error-container-hover px-4 py-2 rounded-lg transition-colors flex items-center gap-2"
                        @click="bulkDelete">
                        <span class="material-symbols-outlined text-[18px]">delete</span>
                        Hapus Terpilih
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[800px] border-collapse text-left">
                    <thead>
                        <tr class="border-b border-surface-container-highest bg-surface-container-low">
                            <th class="w-12 py-3 px-6 text-center">
                                <input
                                    v-model="allSelected"
                                    type="checkbox"
                                    class="h-4 w-4 cursor-pointer rounded border-outline-variant text-primary focus:ring-primary" />
                            </th>
                            <th
                                class="font-table-header text-table-header w-32 px-6 py-3 uppercase tracking-wider text-on-surface-variant">
                                NISN / NIS
                            </th>
                            <th class="font-table-header text-table-header px-6 py-3 uppercase tracking-wider text-on-surface-variant">
                                Nama
                            </th>
                            <th
                                class="font-table-header text-table-header w-40 px-6 py-3 uppercase tracking-wider text-on-surface-variant">
                                Kelas
                            </th>
                            <th
                                class="font-table-header text-table-header w-36 px-6 py-3 uppercase tracking-wider text-on-surface-variant">
                                Jenis kelamin
                            </th>
                            <th
                                class="font-table-header text-table-header w-32 px-6 py-3 text-right uppercase tracking-wider text-on-surface-variant">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container-highest">
                        <tr
                            v-for="(student, index) in students.data"
                            :key="student.id"
                            :class="[
                                index % 2 === 0 ? 'bg-surface-container-lowest' : 'bg-surface-container-low/40',
                                selectedIds.includes(student.id) ? 'bg-primary-container/10' : '',
                            ]"
                            class="group hover:bg-surface-bright transition-colors">
                            <td class="px-6 py-4 text-center">
                                <input
                                    v-model="selectedIds"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-outline-variant text-primary focus:ring-primary"
                                    :value="student.id" />
                            </td>
                            <td class="px-6 py-4 font-body-md text-body-md font-medium text-on-surface">
                                {{ student.nisn || student.nis }}
                            </td>
                            <td class="px-6 py-4 font-body-md text-body-md text-on-surface">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-container text-xs font-bold uppercase text-on-primary-container">
                                        {{ student.user?.name?.substring(0, 2) || 'ST' }}
                                    </div>
                                    <span class="font-medium">{{ student.user?.name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-body-md text-body-md text-on-surface-variant">
                                {{ student.classes?.length ? student.classes[0].name : '—' }}
                            </td>
                            <td class="px-6 py-4 font-body-md text-body-md text-on-surface-variant">
                                {{ genderLabel(student.gender) }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div
                                    class="flex items-center justify-end gap-1 opacity-70 transition-opacity group-hover:opacity-100">
                                    <Link
                                        :href="show(student).url"
                                        class="rounded-md p-1.5 text-outline transition-colors hover:bg-surface-container hover:text-primary"
                                        title="Lihat">
                                        <span class="material-symbols-outlined text-[20px]">visibility</span>
                                    </Link>
                                    <Link
                                        :href="edit(student).url"
                                        class="rounded-md p-1.5 text-outline transition-colors hover:bg-surface-container hover:text-tertiary-container"
                                        title="Edit">
                                        <span class="material-symbols-outlined text-[20px]">edit</span>
                                    </Link>
                                    <button
                                        type="button"
                                        class="rounded-md p-1.5 text-outline transition-colors hover:bg-surface-container hover:text-error"
                                        title="Hapus"
                                        @click="handleDelete(student)">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="students.data.length === 0">
                            <td colspan="6" class="py-8 text-center text-on-surface-variant">
                                Tidak ada siswa yang cocok dengan filter.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <TablePagination
                :from="students.from"
                :to="students.to"
                :total="students.total"
                :links="students.links"
                item-label="siswa" />
        </div>
    </div>

    <ConfirmDialog
        ref="confirmDeleteRef"
        title="Hapus Data Siswa"
        message="Data siswa ini akan dihapus secara permanen. Apakah Anda yakin?"
        confirm-text="Ya, Hapus"
        cancel-text="Batal"
        variant="danger" />
    <ConfirmDialog
        ref="confirmBulkDeleteRef"
        title="Hapus Massal Data Siswa"
        :message="`${selectedIds.length} data siswa terpilih akan dihapus secara permanen. Apakah Anda yakin?`"
        confirm-text="Ya, Hapus Semua"
        cancel-text="Batal"
        variant="danger" />
</template>
