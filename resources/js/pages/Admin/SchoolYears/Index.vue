<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import {
    create,
    edit,
    index,
    destroy,
    setActive,
} from '@/actions/App/Http/Controllers/Admin/SchoolYearController';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import TablePagination from '@/components/TablePagination.vue';
import { firstVisitErrorMessage, isFlashErrorPage } from '@/lib/inertiaVisitHelpers';
import type { PaginatedData } from '@/types';
import type { SchoolYear } from '@/types/models';

defineOptions({ layout: AppSidebarLayout });

const props = defineProps<{
    schoolYears: PaginatedData<SchoolYear>;
    filters: { search?: string | null };
}>();

const dateFormatter = new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
});

function formatDate(value?: string | null): string {
    if (!value) {
        return '—';
    }

    const date = new Date(`${value}T00:00:00`);
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return dateFormatter.format(date);
}

const search = ref(props.filters.search?.trim() ?? '');

watch(
    () => props.filters,
    (f) => {
        search.value = f.search?.trim() ?? '';
    },
    { deep: true },
);

let debounceTimer: ReturnType<typeof setTimeout> | undefined;
function queueSearch(): void {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }

    debounceTimer = setTimeout(() => {
        const q: Record<string, string> = {};
        const s = search.value.trim();
        if (s !== '') {
            q.search = s;
        }
        router.get(index().url, q, { preserveScroll: true, preserveState: true, replace: true });
    }, 350);
}

const confirmDeleteRef = ref<InstanceType<typeof ConfirmDialog> | null>(null);
const handleDelete = async (row: SchoolYear) => {
    const confirmed = await confirmDeleteRef.value?.open();
    if (!confirmed) {
        return;
    }

    router.delete(destroy(row).url, {
        onError: (errors) => {
            toast.error(firstVisitErrorMessage(errors as Record<string, string | string[]>, 'Gagal menghapus tahun ajaran.'));
        },
    });
};

const confirmSetActiveRef = ref<InstanceType<typeof ConfirmDialog> | null>(null);
const handleSetActive = async (row: SchoolYear) => {
    const confirmed = await confirmSetActiveRef.value?.open();
    if (!confirmed) {
        return;
    }

    router.patch(setActive(row).url, {}, {
        onSuccess: (page) => {
            if (isFlashErrorPage(page)) {
                return;
            }
        },
        onError: (errors) => {
            toast.error(firstVisitErrorMessage(errors as Record<string, string | string[]>, 'Gagal mengubah tahun ajaran aktif.'));
        },
    });
};
</script>

<template>
    <Head title="Tahun Ajaran" />

    <div class="flex flex-col gap-stack-lg max-w-7xl mx-auto w-full">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
            <div>
                <h2 class="font-h2 text-h2 text-on-surface">Tahun Ajaran</h2>
                <p class="font-body-md text-body-md text-on-surface-variant mt-1">Kelola tahun ajaran dan tentukan tahun ajaran aktif.</p>
            </div>
            <Link
                :href="create().url"
                class="bg-primary hover:bg-primary-container text-on-primary font-label-sm text-label-sm px-5 py-2.5 rounded-lg flex items-center gap-2 shadow-[0_4px_6px_rgba(0,0,0,0.05)] transition-all"
            >
                <span class="material-symbols-outlined text-[18px]">add</span>
                Tambah Tahun Ajaran
            </Link>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">
            <div class="lg:col-span-5 bg-surface-container-lowest p-2 rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest flex items-center relative">
                <span class="material-symbols-outlined absolute left-4 text-outline">search</span>
                <input
                    v-model="search"
                    class="w-full pl-10 pr-4 py-2 bg-transparent border-none focus:ring-0 font-body-md text-body-md text-on-surface placeholder:text-outline-variant outline-none"
                    placeholder="Cari tahun ajaran, contoh: 2025/2026"
                    type="text"
                    @input="queueSearch"
                />
            </div>
        </div>

        <div class="bg-surface-container-lowest rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest overflow-hidden flex flex-col">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[900px]">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-surface-container-highest">
                            <th class="font-table-header text-table-header text-on-surface-variant py-3 px-6 uppercase tracking-wider w-44">Nama</th>
                            <th class="font-table-header text-table-header text-on-surface-variant py-3 px-6 uppercase tracking-wider w-44">Mulai</th>
                            <th class="font-table-header text-table-header text-on-surface-variant py-3 px-6 uppercase tracking-wider w-44">Selesai</th>
                            <th class="font-table-header text-table-header text-on-surface-variant py-3 px-6 uppercase tracking-wider w-40">Status</th>
                            <th class="font-table-header text-table-header text-on-surface-variant py-3 px-6 uppercase tracking-wider text-right w-44">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container-highest">
                        <tr
                            v-for="(row, i) in schoolYears.data"
                            :key="row.id"
                            :class="i % 2 === 0 ? 'bg-surface-container-lowest' : 'bg-surface-container-low/40'"
                            class="hover:bg-surface-bright transition-colors"
                        >
                            <td class="py-4 px-6 font-medium text-on-surface">{{ row.name }}</td>
                            <td class="py-4 px-6 text-on-surface-variant">{{ formatDate(row.start_date) }}</td>
                            <td class="py-4 px-6 text-on-surface-variant">{{ formatDate(row.end_date) }}</td>
                            <td class="py-4 px-6">
                                <span
                                    class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold"
                                    :class="row.is_active ? 'bg-primary/15 text-primary' : 'bg-surface-container text-on-surface-variant'"
                                >
                                    <span class="block h-2 w-2 rounded-full" :class="row.is_active ? 'bg-primary' : 'bg-outline-variant'" />
                                    {{ row.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button
                                        type="button"
                                        class="px-3 py-2 rounded-lg border border-outline-variant hover:bg-surface-container-low transition-colors text-sm"
                                        @click="handleSetActive(row)"
                                    >
                                        {{ row.is_active ? 'Nonaktifkan' : 'Jadikan aktif' }}
                                    </button>
                                    <Link
                                        :href="edit(row).url"
                                        class="p-1.5 text-outline hover:text-primary transition-colors rounded-md hover:bg-surface-container"
                                        title="Edit"
                                    >
                                        <span class="material-symbols-outlined text-[20px]">edit</span>
                                    </Link>
                                    <button
                                        class="p-1.5 text-outline hover:text-error transition-colors rounded-md hover:bg-surface-container"
                                        title="Hapus"
                                        type="button"
                                        @click="handleDelete(row)"
                                    >
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="schoolYears.data.length === 0">
                            <td colspan="5" class="text-center py-8 text-on-surface-variant">Belum ada tahun ajaran.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <TablePagination
                :from="schoolYears.from"
                :to="schoolYears.to"
                :total="schoolYears.total"
                :links="schoolYears.links"
                item-label="tahun ajaran"
            />
        </div>
    </div>

    <ConfirmDialog
        ref="confirmDeleteRef"
        title="Hapus Tahun Ajaran"
        message="Data tahun ajaran ini akan dihapus permanen. Apakah Anda yakin?"
        confirm-text="Ya, Hapus"
        cancel-text="Batal"
        variant="danger"
    />

    <ConfirmDialog
        ref="confirmSetActiveRef"
        title="Jadikan Tahun Ajaran Aktif"
        message="Tahun ajaran aktif akan dipakai sebagai default untuk jadwal dan data terkait. Lanjutkan?"
        confirm-text="Ya, Jadikan Aktif"
        cancel-text="Batal"
    />
</template>

