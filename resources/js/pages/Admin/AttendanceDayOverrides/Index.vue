<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import { toast } from 'vue-sonner';
import {
    create,
    destroy,
    edit,
    index,
    show,
    toggleActive,
} from '@/actions/App/Http/Controllers/Admin/AttendanceDayOverrideController';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import TablePagination from '@/components/TablePagination.vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { PaginatedData } from '@/types';
import type { AttendanceDayOverride } from '@/types/models';

defineOptions({ layout: AppSidebarLayout });

const props = defineProps<{
    overrides: PaginatedData<AttendanceDayOverride>;
    filters?: {
        search?: string;
        status?: string;
        event_type?: string;
    };
}>();

const filters = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
    event_type: props.filters?.event_type ?? '',
});

function eventTypeLabel(type: string): string {
    switch (type) {
        case 'early_dismissal':
            return 'Pulang lebih awal';
        case 'teacher_meeting':
            return 'Rapat guru';
        case 'special_event':
            return 'Kegiatan khusus';
        case 'holiday_override':
            return 'Override libur';
        case 'attendance_closed':
            return 'Absensi ditutup';
        default:
            return 'Kustom';
    }
}

const applyFilters = (): void => {
    router.get(
        index().url,
        {
            search: filters.search || undefined,
            status: filters.status || undefined,
            event_type: filters.event_type || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const resetFilters = (): void => {
    filters.search = '';
    filters.status = '';
    filters.event_type = '';
    applyFilters();
};

const confirmDeleteRef = ref<InstanceType<typeof ConfirmDialog> | null>(null);

const handleDelete = async (item: AttendanceDayOverride) => {
    const confirmed = await confirmDeleteRef.value?.open();

    if (!confirmed) {
        return;
    }

    router.delete(destroy(item).url, {
        onSuccess: () => toast.success(`Override "${item.name}" berhasil dihapus.`),
        onError: () => toast.error('Gagal menghapus override.'),
    });
};

const handleToggle = (item: AttendanceDayOverride) => {
    router.patch(toggleActive(item).url, {}, {
        onSuccess: () => toast.success(`Status "${item.name}" berhasil diperbarui.`),
        onError: () => toast.error('Gagal mengubah status.'),
    });
};
</script>

<template>
    <Head title="Override Absensi Harian" />

    <div class="flex flex-col gap-stack-lg max-w-7xl mx-auto w-full">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
            <div>
                <h2 class="font-h2 text-h2 text-on-surface">Override Absensi Harian</h2>
                <p class="font-body-md text-body-md text-on-surface-variant mt-1">Kelola pengecualian dan aturan khusus absensi harian.</p>
            </div>
            <Link
                :href="create().url"
                class="bg-primary hover:bg-primary-container text-on-primary font-label-sm text-label-sm px-5 py-2.5 rounded-lg flex items-center gap-2 shadow-[0_4px_6px_rgba(0,0,0,0.05)] transition-all"
            >
                <span class="material-symbols-outlined text-[18px]">add</span>
                Tambah Override
            </Link>
        </div>

        <!-- Filters -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">
            <div class="lg:col-span-4 bg-surface-container-lowest p-2 rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest flex items-center relative">
                <span class="material-symbols-outlined absolute left-4 text-outline">search</span>
                <input
                    v-model="filters.search"
                    class="w-full pl-10 pr-4 py-2 bg-transparent border-none focus:ring-0 font-body-md text-body-md text-on-surface placeholder:text-outline-variant outline-none"
                    placeholder="Cari nama kejadian..."
                    type="text"
                    @keyup.enter="applyFilters"
                />
            </div>
            <div class="lg:col-span-8 bg-surface-container-lowest p-2 rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest flex flex-wrap items-center gap-2">
                <div class="relative flex-1 min-w-[140px]">
                    <select v-model="filters.status" class="w-full appearance-none bg-surface-bright border border-outline-variant rounded-lg font-body-md text-body-md px-4 py-2 pr-10 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all" @change="applyFilters">
                        <option value="">Status (Semua)</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                    <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline pointer-events-none">expand_more</span>
                </div>
                <div class="relative flex-1 min-w-[140px]">
                    <select v-model="filters.event_type" class="w-full appearance-none bg-surface-bright border border-outline-variant rounded-lg font-body-md text-body-md px-4 py-2 pr-10 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all" @change="applyFilters">
                        <option value="">Jenis (Semua)</option>
                        <option value="early_dismissal">Pulang lebih awal</option>
                        <option value="teacher_meeting">Rapat guru</option>
                        <option value="special_event">Kegiatan khusus</option>
                        <option value="holiday_override">Override libur</option>
                        <option value="attendance_closed">Absensi ditutup</option>
                        <option value="custom">Kustom</option>
                    </select>
                    <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline pointer-events-none">expand_more</span>
                </div>
                <button class="px-3 py-2 rounded-lg border border-outline-variant text-on-surface hover:bg-surface-container transition-colors text-sm" type="button" @click="applyFilters">Terapkan</button>
                <button class="px-3 py-2 rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-colors text-sm" type="button" @click="resetFilters">Reset</button>
            </div>
        </div>

        <!-- Data Table -->
        <div class="bg-surface-container-lowest rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest overflow-hidden flex flex-col">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[800px]">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-surface-container-highest">
                            <th class="font-table-header text-table-header text-on-surface-variant py-3 px-6 uppercase tracking-wider w-36">Tanggal</th>
                            <th class="font-table-header text-table-header text-on-surface-variant py-3 px-6 uppercase tracking-wider">Nama Kejadian</th>
                            <th class="font-table-header text-table-header text-on-surface-variant py-3 px-6 uppercase tracking-wider w-40">Jenis</th>
                            <th class="font-table-header text-table-header text-on-surface-variant py-3 px-6 uppercase tracking-wider w-28">Status</th>
                            <th class="font-table-header text-table-header text-on-surface-variant py-3 px-6 uppercase tracking-wider text-right w-40">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container-highest">
                        <tr
                            v-for="(item, idx) in overrides.data"
                            :key="item.id"
                            :class="idx % 2 === 0 ? 'bg-surface-container-lowest' : 'bg-surface-container-low/40'"
                            class="hover:bg-surface-bright transition-colors group"
                        >
                            <td class="font-body-md text-body-md text-on-surface-variant py-4 px-6 font-mono text-sm">{{ item.date }}</td>
                            <td class="font-body-md text-body-md text-on-surface py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-tertiary-container text-on-tertiary-container flex items-center justify-center">
                                        <span class="material-symbols-outlined text-[16px]">event_note</span>
                                    </div>
                                    <span class="font-medium">{{ item.name }}</span>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full font-label-sm text-label-sm bg-surface-container text-on-surface-variant">{{ eventTypeLabel(item.event_type) }}</span>
                            </td>
                            <td class="py-4 px-6">
                                <span v-if="item.is_active" class="inline-flex items-center px-2.5 py-1 rounded-full font-label-sm text-label-sm bg-secondary-container text-on-secondary-container">Aktif</span>
                                <span v-else class="inline-flex items-center px-2.5 py-1 rounded-full font-label-sm text-label-sm bg-error-container text-on-error-container">Nonaktif</span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="flex items-center justify-end gap-1 opacity-70 group-hover:opacity-100 transition-opacity">
                                    <Link :href="show(item).url" class="p-1.5 text-outline hover:text-primary transition-colors rounded-md hover:bg-surface-container" title="Detail">
                                        <span class="material-symbols-outlined text-[20px]">visibility</span>
                                    </Link>
                                    <Link :href="edit(item).url" class="p-1.5 text-outline hover:text-primary transition-colors rounded-md hover:bg-surface-container" title="Edit">
                                        <span class="material-symbols-outlined text-[20px]">edit</span>
                                    </Link>
                                    <button class="p-1.5 text-outline hover:text-tertiary transition-colors rounded-md hover:bg-surface-container" :title="item.is_active ? 'Nonaktifkan' : 'Aktifkan'" @click="handleToggle(item)">
                                        <span class="material-symbols-outlined text-[20px]">{{ item.is_active ? 'toggle_on' : 'toggle_off' }}</span>
                                    </button>
                                    <button class="p-1.5 text-outline hover:text-error transition-colors rounded-md hover:bg-surface-container" title="Hapus" @click="handleDelete(item)">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="overrides.data.length === 0">
                            <td colspan="5" class="text-center py-8 text-on-surface-variant">Belum ada data override.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <TablePagination
                :from="overrides.from"
                :to="overrides.to"
                :total="overrides.total"
                :links="overrides.links"
                item-label="override"
            />
        </div>
    </div>

    <ConfirmDialog
        ref="confirmDeleteRef"
        title="Hapus Override"
        message="Data override ini akan dihapus secara permanen. Apakah Anda yakin?"
        confirm-text="Ya, Hapus"
        cancel-text="Batal"
        variant="danger"
    />
</template>
