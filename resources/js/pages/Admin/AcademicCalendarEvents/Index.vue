<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { create, destroy, edit, show } from '@/actions/App/Http/Controllers/Admin/AcademicCalendarEventController';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import TablePagination from '@/components/TablePagination.vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import {
    academicEventDurationLabel,
    academicEventTypeBadgeClass,
    academicEventTypeLabel,
    formatAcademicDateRange,
} from '@/lib/academicCalendarDisplay';
import type { PaginatedData } from '@/types';
import type { AcademicCalendarEvent } from '@/types/models';

defineOptions({ layout: AppSidebarLayout });

defineProps<{
    events: PaginatedData<AcademicCalendarEvent>;
}>();

const confirmDeleteRef = ref<InstanceType<typeof ConfirmDialog> | null>(null);

const handleDelete = async (event: AcademicCalendarEvent): Promise<void> => {
    const confirmed = await confirmDeleteRef.value?.open();

    if (!confirmed) {
        return;
    }

    router.delete(destroy(event).url, {
        onSuccess: () => {
            toast.success(`“${event.name}” berhasil dihapus.`);
        },
        onError: () => {
            toast.error('Tidak dapat menghapus kegiatan ini. Coba lagi atau periksa data terkait.');
        },
    });
};
</script>

<template>
    <Head title="Kalender Akademik" />

    <div class="mx-auto flex w-full max-w-7xl flex-col gap-stack-lg">
        <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <h2 class="font-h2 text-h2 text-on-surface">Kalender akademik</h2>
                <p class="font-body-md text-body-md mt-1 text-on-surface-variant">
                    Kelola hari libur, ujian, dan kegiatan khusus yang memengaruhi jadwal & absensi.
                </p>
            </div>
            <Link
                :href="create().url"
                class="font-label-sm text-label-sm flex items-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-on-primary shadow-[0_4px_6px_rgba(0,0,0,0.05)] transition-all hover:bg-primary-container"
            >
                <span class="material-symbols-outlined text-[18px]">calendar_add_on</span>
                Tambah kegiatan
            </Link>
        </div>

        <div
            class="bg-surface-container-lowest flex flex-col overflow-hidden rounded-xl border border-surface-container-highest shadow-[0_4px_6px_rgba(0,0,0,0.05)]"
        >
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] border-collapse text-left">
                    <thead>
                        <tr class="border-b border-surface-container-highest bg-surface-container-low">
                            <th
                                class="font-table-header text-table-header text-on-surface-variant w-[min(28%,280px)] px-6 py-3 uppercase tracking-wider"
                            >
                                Nama kegiatan
                            </th>
                            <th
                                class="font-table-header text-table-header text-on-surface-variant w-56 px-6 py-3 uppercase tracking-wider"
                            >
                                Tanggal
                            </th>
                            <th
                                class="font-table-header text-table-header text-on-surface-variant w-44 px-6 py-3 uppercase tracking-wider"
                            >
                                Jenis
                            </th>
                            <th
                                class="font-table-header text-table-header text-on-surface-variant w-40 px-6 py-3 uppercase tracking-wider"
                            >
                                Absensi
                            </th>
                            <th
                                class="font-table-header text-table-header text-on-surface-variant w-32 px-6 py-3 text-right uppercase tracking-wider"
                            >
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container-highest">
                        <tr
                            v-for="(event, idx) in events.data"
                            :key="event.id"
                            :class="[
                                idx % 2 === 0 ? 'bg-surface-container-lowest' : 'bg-surface-container-low/40',
                                !event.is_active ? 'opacity-[0.72]' : '',
                            ]"
                            class="group transition-colors hover:bg-surface-bright"
                        >
                            <td class="px-6 py-4 align-top">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="bg-tertiary-container text-on-tertiary-container mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                                    >
                                        <span class="material-symbols-outlined text-[18px]">event</span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-body-md text-body-md text-on-surface font-semibold leading-snug">
                                            {{ event.name }}
                                        </p>
                                        <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                            <span
                                                v-if="!event.is_active"
                                                class="font-label-sm text-label-sm inline-flex items-center rounded-full bg-surface-container-high px-2 py-0.5 text-on-surface-variant"
                                            >
                                                Nonaktif
                                            </span>
                                            <span
                                                v-if="event.override_schedule"
                                                class="font-label-sm text-label-sm inline-flex items-center rounded-full border border-outline-variant bg-surface-container-low px-2 py-0.5 text-on-surface-variant"
                                                title="Jadwal pelajaran pada tanggal ini boleh diabaikan sesuai pengaturan sistem"
                                            >
                                                Jadwal diabaikan
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 align-top">
                                <p
                                    class="font-body-md text-body-md text-on-surface font-medium leading-snug"
                                >
                                    {{ formatAcademicDateRange(event.start_date, event.end_date) }}
                                </p>
                                <p class="font-body-sm text-body-sm mt-1 text-on-surface-variant">
                                    {{ academicEventDurationLabel(event.start_date, event.end_date) }}
                                </p>
                            </td>
                            <td class="px-6 py-4 align-top">
                                <span
                                    :class="[
                                        'font-label-sm text-label-sm inline-flex max-w-full items-center rounded-full px-2.5 py-1',
                                        academicEventTypeBadgeClass(event.event_type),
                                    ]"
                                >
                                    {{ academicEventTypeLabel(event.event_type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 align-top">
                                <span
                                    v-if="event.allow_attendance"
                                    class="font-label-sm text-label-sm bg-secondary-container/70 text-on-secondary-container inline-flex items-center rounded-full px-2.5 py-1"
                                >
                                    Absensi dibuka
                                </span>
                                <span
                                    v-else
                                    class="font-label-sm text-label-sm bg-error-container/55 text-on-error-container inline-flex items-center rounded-full px-2.5 py-1"
                                >
                                    Absensi ditutup
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right align-top">
                                <div
                                    class="flex items-center justify-end gap-1 opacity-70 transition-opacity group-hover:opacity-100"
                                >
                                    <Link
                                        :href="show(event).url"
                                        class="text-outline hover:text-primary rounded-md p-1.5 transition-colors hover:bg-surface-container"
                                        title="Lihat detail"
                                    >
                                        <span class="material-symbols-outlined text-[20px]">visibility</span>
                                    </Link>
                                    <Link
                                        :href="edit(event).url"
                                        class="text-outline hover:text-primary rounded-md p-1.5 transition-colors hover:bg-surface-container"
                                        title="Ubah"
                                    >
                                        <span class="material-symbols-outlined text-[20px]">edit</span>
                                    </Link>
                                    <button
                                        type="button"
                                        class="text-outline hover:text-error rounded-md p-1.5 transition-colors hover:bg-surface-container"
                                        title="Hapus"
                                        @click="handleDelete(event)"
                                    >
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="events.data.length === 0">
                            <td colspan="5" class="text-on-surface-variant px-6 py-10 text-center font-body-md">
                                Belum ada kegiatan di kalender. Tambahkan libur, ujian, atau acara sekolah lewat
                                tombol di atas.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <TablePagination
                v-if="events.data.length > 0"
                :from="events.from"
                :to="events.to"
                :total="events.total"
                :links="events.links"
                item-label="kegiatan"
            />
        </div>
    </div>

    <ConfirmDialog
        ref="confirmDeleteRef"
        title="Hapus kegiatan"
        message="Data kegiatan ini akan dihapus secara permanen. Lanjutkan?"
        confirm-text="Ya, hapus"
        cancel-text="Batal"
        variant="danger"
    />
</template>
