<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { edit, index } from '@/actions/App/Http/Controllers/Admin/AcademicCalendarEventController';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import {
    academicEventDurationLabel,
    academicEventTypeLabel,
    formatAcademicDateRange,
    formatIndonesianLongDate,
} from '@/lib/academicCalendarDisplay';
import type { AcademicCalendarEvent } from '@/types/models';

defineOptions({ layout: AppSidebarLayout });

defineProps<{
    event: AcademicCalendarEvent;
}>();
</script>

<template>
    <Head title="Detail Event Kalender" />
    <div class="mx-auto w-full max-w-2xl space-y-6">
        <Link :href="index().url" class="text-on-surface-variant text-sm hover:text-primary">← Kembali ke daftar</Link>
        <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
            <h1 class="text-2xl font-semibold text-on-surface leading-tight">{{ event.name }}</h1>
            <Link :href="edit(event).url" class="text-on-primary rounded-lg bg-primary px-4 py-2 text-sm font-medium hover:bg-primary-container">
                Ubah data
            </Link>
        </div>
        <dl class="border-on-surface-variant/15 space-y-4 rounded-xl border bg-surface-container-lowest p-5 shadow-sm">
            <div>
                <dt class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wide">Rentang tanggal</dt>
                <dd class="font-body-md text-body-md mt-1 font-medium text-on-surface">
                    {{ formatAcademicDateRange(event.start_date, event.end_date) }}
                </dd>
                <dd class="font-body-sm text-body-sm text-on-surface-variant mt-1">
                    {{ academicEventDurationLabel(event.start_date, event.end_date) }}
                </dd>
                <dd
                    v-if="event.start_date !== event.end_date"
                    class="font-body-sm text-body-sm text-on-surface-variant mt-2 border-t border-outline-variant/30 pt-2"
                >
                    Terperinci:
                    {{ formatIndonesianLongDate(event.start_date) }}
                    —
                    {{ formatIndonesianLongDate(event.end_date) }}
                </dd>
            </div>
            <div>
                <dt class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wide">Jenis kegiatan</dt>
                <dd class="font-body-md text-body-md mt-1 text-on-surface">
                    {{ academicEventTypeLabel(event.event_type) }}
                </dd>
            </div>
            <div>
                <dt class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wide">Status</dt>
                <dd class="font-body-md text-body-md mt-1 text-on-surface">
                    {{ event.is_active ? 'Aktif dipakai oleh sistem' : 'Nonaktif (diabaikan)' }}
                </dd>
            </div>
            <div>
                <dt class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wide">Absensi pada tanggal ini</dt>
                <dd class="font-body-md text-body-md mt-1 text-on-surface">
                    {{ event.allow_attendance ? 'Absensi siswa/guru dibuka seperti hari biasa.' : 'Tidak memerlukan / tidak membuka absensi.' }}
                </dd>
            </div>
            <div>
                <dt class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wide">Jadwal pelajaran</dt>
                <dd class="font-body-md text-body-md mt-1 text-on-surface">
                    {{
                        event.override_schedule
                            ? 'Jadwal normal boleh diabaikan (misalnya hari libur atau kegiatan mengganti jam pelajaran).'
                            : 'Jadwal pelajaran tetap mengikuti hari biasa.'
                    }}
                </dd>
            </div>
            <div>
                <dt class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wide">Catatan</dt>
                <dd class="font-body-md text-body-md mt-1 whitespace-pre-wrap text-on-surface">
                    {{ event.notes?.trim() ? event.notes : '—' }}
                </dd>
            </div>
        </dl>
    </div>
</template>
