<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    edit,
    index,
} from '@/actions/App/Http/Controllers/Admin/AttendanceDayOverrideController';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { AttendanceDayOverride } from '@/types/models';

defineOptions({ layout: AppSidebarLayout });

defineProps<{
    override: AttendanceDayOverride;
}>();

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
</script>

<template>
    <Head title="Detail Override Absensi" />
    <div class="max-w-3xl space-y-6">
        <div class="flex items-center justify-between">
            <Link :href="index().url" class="text-sm text-on-surface-variant"
                >Kembali</Link
            >
            <Link
                :href="edit(override).url"
                class="rounded bg-primary px-4 py-2 text-on-primary"
                >Edit</Link
            >
        </div>
        <h1 class="text-2xl font-semibold">{{ override.name }}</h1>

        <div class="grid grid-cols-2 gap-3 rounded border p-4 text-sm">
            <div><strong>Tanggal:</strong> {{ override.date }}</div>
            <div>
                <strong>Jenis kejadian:</strong>
                {{ eventTypeLabel(override.event_type) }}
            </div>
            <div><strong>Aktif:</strong> {{ override.is_active ? 'Ya' : 'Tidak' }}</div>
            <div><strong>Titik absensi:</strong> {{ override.attendance_site?.name ?? 'Semua titik' }}</div>
            <div><strong>Timpa policy absensi:</strong> {{ override.override_attendance_policy ? 'Ya' : 'Tidak' }}</div>
            <div><strong>Timpa jadwal pelajaran:</strong> {{ override.override_schedule ? 'Ya' : 'Tidak' }}</div>
            <div><strong>Izinkan check-in:</strong> {{ override.allow_check_in ? 'Ya' : 'Tidak' }}</div>
            <div><strong>Izinkan check-out:</strong> {{ override.allow_check_out ? 'Ya' : 'Tidak' }}</div>
            <div><strong>Bebaskan check-out manual:</strong> {{ override.waive_check_out ? 'Ya' : 'Tidak' }}</div>
            <div><strong>Siswa pulang lebih awal:</strong> {{ override.dismiss_students_early ? 'Ya' : 'Tidak' }}</div>
            <div><strong>Jam check-in (buka/tepat waktu/tutup):</strong> {{ override.check_in_open_at ?? '-' }} / {{ override.check_in_on_time_until ?? '-' }} / {{ override.check_in_close_at ?? '-' }}</div>
            <div><strong>Jam check-out (buka/tutup):</strong> {{ override.check_out_open_at ?? '-' }} / {{ override.check_out_close_at ?? '-' }}</div>
            <div class="col-span-2"><strong>Catatan:</strong> {{ override.notes ?? '-' }}</div>
        </div>
    </div>
</template>

