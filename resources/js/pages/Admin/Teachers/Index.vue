<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import {
    bulkDestroy,
    create,
    destroy,
    edit,
    index,
    show,
} from '@/actions/App/Http/Controllers/Admin/TeacherController';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import TablePagination from '@/components/TablePagination.vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { firstVisitErrorMessage, isFlashErrorPage } from '@/lib/inertiaVisitHelpers';
import type { PaginatedData } from '@/types';
import type { TeacherProfile } from '@/types/models';

defineOptions({
    layout: AppSidebarLayout,
});

type UiFilters = {
    search: string;
    account_status: string;
};

const props = defineProps<{
    teachers: PaginatedData<TeacherProfile>;
    filters: {
        search?: string | null;
        account_status?: string | null;
    };
}>();

function fromServer(f: typeof props.filters): UiFilters {
    return {
        search: f.search?.trim() ?? '',
        account_status: f.account_status ?? '',
    };
}

const filterForm = ref<UiFilters>(fromServer(props.filters));

watch(
    () => props.filters,
    (f) => {
        filterForm.value = fromServer(f);
    },
    { deep: true },
);

function toQuery(f: UiFilters): Record<string, string> {
    const q: Record<string, string> = {};
    const s = f.search.trim();
    if (s !== '') {
        q.search = s;
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

let debounceTimer: ReturnType<typeof setTimeout> | undefined;
function queueSearch(): void {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }
    debounceTimer = setTimeout(() => applyFilters(), 350);
}

function genderLabel(v?: string | null): string {
    if (v === 'male') {
        return 'Laki-laki';
    }
    if (v === 'female') {
        return 'Perempuan';
    }

    return '—';
}

const selectedIds = ref<number[]>([]);

const allSelected = computed({
    get: () => props.teachers.data.length > 0 && selectedIds.value.length === props.teachers.data.length,
    set: (value) => {
        if (value) {
            selectedIds.value = props.teachers.data.map((item) => item.id);
        } else {
            selectedIds.value = [];
        }
    },
});

watch(
    () => props.teachers.data,
    () => {
        selectedIds.value = selectedIds.value.filter((id) => props.teachers.data.some((t) => t.id === id));
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
                    'Gagal menghapus data guru. Periksa data lalu coba lagi.',
                ),
            );
        },
    });
};

const confirmDeleteRef = ref<InstanceType<typeof ConfirmDialog> | null>(null);

const handleDelete = async (teacher: TeacherProfile) => {
    const confirmed = await confirmDeleteRef.value?.open();

    if (!confirmed) {
        return;
    }

    router.delete(destroy(teacher).url, {
        onError: (errors) => {
            toast.error(
                firstVisitErrorMessage(
                    errors as Record<string, string | string[]>,
                    'Gagal menghapus data guru. Periksa data lalu coba lagi.',
                ),
            );
        },
    });
};
</script>

<template>
    <Head title="Manajemen Guru" />

    <div class="flex max-w-7xl flex-col gap-stack-lg">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <h2 class="font-h2 text-h2 text-on-surface">Manajemen Guru</h2>
                <p class="mt-1 font-body-md text-body-md text-on-surface-variant">
                    Cari guru dengan nama lengkap profil, NIP, nama akun, atau username.
                </p>
            </div>
            <Link
                :href="create().url"
                class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-primary px-5 py-2.5 font-label-sm text-label-sm text-on-primary shadow-[0_4px_6px_rgba(0,0,0,0.05)] transition-all hover:bg-primary-container">
                <span class="material-symbols-outlined text-[18px]">person_add</span>
                Tambah guru
            </Link>
        </div>

        <div class="grid grid-cols-1 gap-gutter xl:grid-cols-12">
            <div
                class="xl:col-span-5 rounded-xl border border-surface-container-highest bg-surface-container-lowest p-2 shadow-[0_4px_6px_rgba(0,0,0,0.05)]">
                <div class="relative flex items-center">
                    <span class="material-symbols-outlined pointer-events-none absolute left-4 text-outline">search</span>
                    <input
                        v-model="filterForm.search"
                        class="w-full border-none bg-transparent py-3 pl-11 pr-4 font-body-md text-body-md text-on-surface outline-none placeholder:text-outline-variant focus:ring-0"
                        placeholder="Nama (profil atau akun), username, atau NIP…"
                        type="text"
                        @input="queueSearch" />
                </div>
            </div>
            <div
                class="xl:col-span-7 rounded-xl border border-surface-container-highest bg-surface-container-lowest p-3 shadow-[0_4px_6px_rgba(0,0,0,0.05)]">
                <label class="mb-2 block px-0.5 text-label-xs text-on-surface-variant">Status akun</label>
                <div class="relative max-w-xs">
                    <select
                        v-model="filterForm.account_status"
                        class="w-full appearance-none rounded-lg border border-outline-variant bg-surface-bright px-4 py-2.5 pr-10 font-body-md text-body-md text-on-surface outline-none transition-all focus:border-primary focus:ring-1 focus:ring-primary"
                        @change="applyFilters()">
                        <option value="">Semua</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                    <span class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-outline">
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
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg bg-error-container px-4 py-2 font-label-sm text-label-sm text-error transition-colors hover:bg-error-container-hover"
                    @click="bulkDelete">
                    <span class="material-symbols-outlined text-[18px]">delete</span>
                    Hapus terpilih
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[800px] w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b border-surface-container-highest bg-surface-container-low">
                            <th class="w-12 px-6 py-3 text-center">
                                <input
                                    v-model="allSelected"
                                    type="checkbox"
                                    class="h-4 w-4 cursor-pointer rounded border-outline-variant text-primary focus:ring-primary" />
                            </th>
                            <th class="font-table-header text-table-header w-36 px-6 py-3 uppercase tracking-wider text-on-surface-variant">
                                NIP
                            </th>
                            <th class="font-table-header text-table-header px-6 py-3 uppercase tracking-wider text-on-surface-variant">
                                Nama
                            </th>
                            <th class="font-table-header text-table-header w-36 px-6 py-3 uppercase tracking-wider text-on-surface-variant">
                                Jenis kelamin
                            </th>
                            <th class="font-table-header text-table-header w-48 px-6 py-3 uppercase tracking-wider text-on-surface-variant">
                                Mapel
                            </th>
                            <th class="font-table-header text-table-header w-28 px-6 py-3 text-right uppercase tracking-wider text-on-surface-variant">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container-highest">
                        <tr
                            v-for="(teacher, index) in teachers.data"
                            :key="teacher.id"
                            :class="[
                                index % 2 === 0 ? 'bg-surface-container-lowest' : 'bg-surface-container-low/40',
                                selectedIds.includes(teacher.id) ? 'bg-primary-container/10' : '',
                            ]"
                            class="group transition-colors hover:bg-surface-bright">
                            <td class="px-6 py-4 text-center">
                                <input
                                    v-model="selectedIds"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-outline-variant text-primary focus:ring-primary"
                                    :value="teacher.id" />
                            </td>
                            <td class="px-6 py-4 font-body-md text-body-md font-medium text-on-surface">
                                {{ teacher.nip ?? '—' }}
                            </td>
                            <td class="px-6 py-4 font-body-md text-body-md text-on-surface">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-tertiary-container text-xs font-bold uppercase text-on-tertiary-container">
                                        {{ teacher.full_name?.substring(0, 2) ?? 'TC' }}
                                    </div>
                                    <span class="font-medium">{{ teacher.full_name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-body-md text-body-md text-on-surface-variant">
                                {{ genderLabel(teacher.gender) }}
                            </td>
                            <td class="px-6 py-4 font-body-md text-body-md text-on-surface-variant">
                                <span class="break-words">{{ teacher.subjects?.map((s) => s.name).join(', ') || '—' }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div
                                    class="flex items-center justify-end gap-1 opacity-70 transition-opacity group-hover:opacity-100">
                                    <Link
                                        :href="show(teacher).url"
                                        title="Lihat"
                                        class="rounded-md p-1.5 text-outline transition-colors hover:bg-surface-container hover:text-primary">
                                        <span class="material-symbols-outlined text-[20px]">visibility</span>
                                    </Link>
                                    <Link
                                        :href="edit(teacher).url"
                                        title="Edit"
                                        class="rounded-md p-1.5 text-outline transition-colors hover:bg-surface-container hover:text-tertiary-container">
                                        <span class="material-symbols-outlined text-[20px]">edit</span>
                                    </Link>
                                    <button
                                        type="button"
                                        title="Hapus"
                                        class="rounded-md p-1.5 text-outline transition-colors hover:bg-surface-container hover:text-error"
                                        @click="handleDelete(teacher)">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="teachers.data.length === 0">
                            <td colspan="6" class="py-8 text-center text-on-surface-variant">
                                Tidak ada guru yang cocok dengan pencarian.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <TablePagination
                :from="teachers.from"
                :to="teachers.to"
                :total="teachers.total"
                :links="teachers.links"
                item-label="guru" />
        </div>
    </div>

    <ConfirmDialog
        ref="confirmDeleteRef"
        title="Hapus Data Guru"
        message="Data guru ini akan dihapus secara permanen. Apakah Anda yakin?"
        confirm-text="Ya, Hapus"
        cancel-text="Batal"
        variant="danger" />

    <ConfirmDialog
        ref="confirmBulkDeleteRef"
        title="Hapus Massal Data Guru"
        :message="`${selectedIds.length} data guru terpilih akan dihapus secara permanen. Apakah Anda yakin?`"
        confirm-text="Ya, Hapus Semua"
        cancel-text="Batal"
        variant="danger" />
</template>
