<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import { toast } from 'vue-sonner';
import { create, destroy, edit, index, show, toggleActive } from '@/actions/App/Http/Controllers/Admin/AttendanceSiteController';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import TablePagination from '@/components/TablePagination.vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { PaginatedData } from '@/types';
import type { AttendanceSite } from '@/types/models';

defineOptions({ layout: AppSidebarLayout });

const props = defineProps<{
    sites: PaginatedData<AttendanceSite>;
    filters?: {
        search?: string;
        status?: string;
    };
}>();

const filters = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
});

const applyFilters = (): void => {
    router.get(
        index().url,
        {
            search: filters.search || undefined,
            status: filters.status || undefined,
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
    applyFilters();
};

const confirmDeleteRef = ref<InstanceType<typeof ConfirmDialog> | null>(null);

const handleDelete = async (site: AttendanceSite) => {
    const confirmed = await confirmDeleteRef.value?.open();

    if (!confirmed) {
        return;
    }

    router.delete(destroy(site).url, {
        onSuccess: () => toast.success(`Titik absensi "${site.name}" berhasil dihapus.`),
        onError: () => toast.error('Gagal menghapus titik absensi.'),
    });
};

const handleToggle = (site: AttendanceSite) => {
    router.patch(toggleActive(site).url, {}, {
        onSuccess: () => toast.success(`Status "${site.name}" berhasil diperbarui.`),
        onError: () => toast.error('Gagal mengubah status titik absensi.'),
    });
};
</script>

<template>
    <Head title="Titik Absensi" />

    <div class="flex flex-col gap-stack-lg max-w-7xl mx-auto w-full">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
            <div>
                <h2 class="font-h2 text-h2 text-on-surface">Titik Absensi</h2>
                <p class="font-body-md text-body-md text-on-surface-variant mt-1">Kelola lokasi titik absensi dan konfigurasi area GPS.</p>
            </div>
            <Link
                :href="create().url"
                class="bg-primary hover:bg-primary-container text-on-primary font-label-sm text-label-sm px-5 py-2.5 rounded-lg flex items-center gap-2 shadow-[0_4px_6px_rgba(0,0,0,0.05)] transition-all"
            >
                <span class="material-symbols-outlined text-[18px]">add_location</span>
                Tambah Titik
            </Link>
        </div>

        <!-- Filters -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">
            <div class="lg:col-span-5 bg-surface-container-lowest p-2 rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest flex items-center relative">
                <span class="material-symbols-outlined absolute left-4 text-outline">search</span>
                <input
                    v-model="filters.search"
                    class="w-full pl-10 pr-4 py-2 bg-transparent border-none focus:ring-0 font-body-md text-body-md text-on-surface placeholder:text-outline-variant outline-none"
                    placeholder="Cari titik absensi..."
                    type="text"
                    @keyup.enter="applyFilters"
                />
            </div>
            <div class="lg:col-span-7 bg-surface-container-lowest p-2 rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest flex flex-wrap items-center gap-2">
                <div class="relative flex-1 min-w-[140px]">
                    <select v-model="filters.status" class="w-full appearance-none bg-surface-bright border border-outline-variant rounded-lg font-body-md text-body-md px-4 py-2 pr-10 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all" @change="applyFilters">
                        <option value="">Status (Semua)</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
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
                <table class="w-full text-left border-collapse min-w-[700px]">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-surface-container-highest">
                            <th class="font-table-header text-table-header text-on-surface-variant py-3 px-6 uppercase tracking-wider">Nama</th>
                            <th class="font-table-header text-table-header text-on-surface-variant py-3 px-6 uppercase tracking-wider w-32">Radius</th>
                            <th class="font-table-header text-table-header text-on-surface-variant py-3 px-6 uppercase tracking-wider w-32">Status</th>
                            <th class="font-table-header text-table-header text-on-surface-variant py-3 px-6 uppercase tracking-wider text-right w-40">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container-highest">
                        <tr
                            v-for="(site, idx) in sites.data"
                            :key="site.id"
                            :class="idx % 2 === 0 ? 'bg-surface-container-lowest' : 'bg-surface-container-low/40'"
                            class="hover:bg-surface-bright transition-colors group"
                        >
                            <td class="font-body-md text-body-md text-on-surface py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-primary-container text-on-primary-container flex items-center justify-center">
                                        <span class="material-symbols-outlined text-[16px]">location_on</span>
                                    </div>
                                    <span class="font-medium">{{ site.name }}</span>
                                </div>
                            </td>
                            <td class="font-body-md text-body-md text-on-surface-variant py-4 px-6">{{ site.radius_m }} m</td>
                            <td class="py-4 px-6">
                                <span v-if="site.is_active" class="inline-flex items-center px-2.5 py-1 rounded-full font-label-sm text-label-sm bg-secondary-container text-on-secondary-container">Aktif</span>
                                <span v-else class="inline-flex items-center px-2.5 py-1 rounded-full font-label-sm text-label-sm bg-error-container text-on-error-container">Nonaktif</span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="flex items-center justify-end gap-1 opacity-70 group-hover:opacity-100 transition-opacity">
                                    <Link :href="show(site).url" class="p-1.5 text-outline hover:text-primary transition-colors rounded-md hover:bg-surface-container" title="Detail">
                                        <span class="material-symbols-outlined text-[20px]">visibility</span>
                                    </Link>
                                    <Link :href="edit(site).url" class="p-1.5 text-outline hover:text-primary transition-colors rounded-md hover:bg-surface-container" title="Edit">
                                        <span class="material-symbols-outlined text-[20px]">edit</span>
                                    </Link>
                                    <button class="p-1.5 text-outline hover:text-tertiary transition-colors rounded-md hover:bg-surface-container" :title="site.is_active ? 'Nonaktifkan' : 'Aktifkan'" @click="handleToggle(site)">
                                        <span class="material-symbols-outlined text-[20px]">{{ site.is_active ? 'toggle_on' : 'toggle_off' }}</span>
                                    </button>
                                    <button class="p-1.5 text-outline hover:text-error transition-colors rounded-md hover:bg-surface-container" title="Hapus" @click="handleDelete(site)">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="sites.data.length === 0">
                            <td colspan="4" class="text-center py-8 text-on-surface-variant">Belum ada titik absensi.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <TablePagination
                :from="sites.from"
                :to="sites.to"
                :total="sites.total"
                :links="sites.links"
                item-label="lokasi absensi"
            />
        </div>
    </div>

    <ConfirmDialog
        ref="confirmDeleteRef"
        title="Hapus Titik Absensi"
        message="Data titik absensi ini akan dihapus secara permanen. Apakah Anda yakin?"
        confirm-text="Ya, Hapus"
        cancel-text="Batal"
        variant="danger"
    />
</template>
