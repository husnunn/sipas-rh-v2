<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    index,
    store,
    update,
} from '@/actions/App/Http/Controllers/Admin/AttendanceDayOverrideController';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { AttendanceDayOverride, AttendanceSite } from '@/types/models';

defineOptions({ layout: AppSidebarLayout });

const props = defineProps<{
    mode: 'create' | 'edit';
    override: AttendanceDayOverride | null;
    eventTypes: string[];
    attendanceSites: Pick<AttendanceSite, 'id' | 'name'>[];
}>();

const form = useForm({
    name: props.override?.name ?? '',
    date: props.override?.date ?? '',
    event_type: props.override?.event_type ?? props.eventTypes[0] ?? 'custom',
    is_active: props.override?.is_active ?? true,
    attendance_site_id: props.override?.attendance_site_id ?? null,
    override_attendance_policy: props.override?.override_attendance_policy ?? false,
    override_schedule: props.override?.override_schedule ?? false,
    allow_check_in: props.override?.allow_check_in ?? true,
    allow_check_out: props.override?.allow_check_out ?? true,
    waive_check_out: props.override?.waive_check_out ?? false,
    dismiss_students_early: props.override?.dismiss_students_early ?? false,
    check_in_open_at: props.override?.check_in_open_at ?? '',
    check_in_on_time_until: props.override?.check_in_on_time_until ?? '',
    check_in_close_at: props.override?.check_in_close_at ?? '',
    check_out_open_at: props.override?.check_out_open_at ?? '',
    check_out_close_at: props.override?.check_out_close_at ?? '',
    notes: props.override?.notes ?? '',
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

function submit(): void {
    if (props.mode === 'create') {
        form.post(store().url);

        return;
    }

    if (props.override) {
        form.put(update(props.override).url);
    }
}
</script>

<template>
    <Head :title="mode === 'create' ? 'Tambah Override' : 'Edit Override'" />
    <div class="max-w-3xl space-y-6">
        <Link :href="index().url" class="text-sm text-on-surface-variant"
            >Kembali</Link
        >
        <h1 class="text-2xl font-semibold">
            {{ mode === 'create' ? 'Tambah Override Absensi Harian' : 'Edit Override Absensi Harian' }}
        </h1>

        <form class="space-y-5" @submit.prevent="submit">
            <div class="space-y-1">
                <label class="text-sm font-medium text-on-surface-variant"
                    >Nama override</label
                >
                <input
                    v-model="form.name"
                    type="text"
                    placeholder="Contoh: Rapat guru mendadak"
                    class="w-full rounded border px-3 py-2"
                />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1">
                    <label class="text-sm font-medium text-on-surface-variant"
                        >Tanggal berlaku</label
                    >
                    <input
                        v-model="form.date"
                        type="date"
                        class="w-full rounded border px-3 py-2"
                    />
                </div>
                <div class="space-y-1">
                    <label class="text-sm font-medium text-on-surface-variant"
                        >Jenis kejadian</label
                    >
                    <select
                        v-model="form.event_type"
                        class="w-full rounded border px-3 py-2"
                    >
                        <option
                            v-for="type in eventTypes"
                            :key="type"
                            :value="type"
                        >
                            {{ eventTypeLabel(type) }}
                        </option>
                    </select>
                </div>
            </div>

            <div class="space-y-1">
                <label class="text-sm font-medium text-on-surface-variant"
                    >Titik absensi (opsional)</label
                >
                <select
                    v-model="form.attendance_site_id"
                    class="w-full rounded border px-3 py-2"
                >
                    <option :value="null">Semua titik absensi</option>
                    <option
                        v-for="site in attendanceSites"
                        :key="site.id"
                        :value="site.id"
                    >
                        {{ site.name }}
                    </option>
                </select>
            </div>

            <div class="space-y-2">
                <p class="text-sm font-medium text-on-surface-variant">
                    Jam override (isi jika policy absensi di-override)
                </p>
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="text-xs text-on-surface-variant"
                            >Jam buka check-in</label
                        >
                        <input
                            v-model="form.check_in_open_at"
                            type="time"
                            class="w-full rounded border px-3 py-2"
                        />
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs text-on-surface-variant"
                            >Batas tepat waktu check-in</label
                        >
                        <input
                            v-model="form.check_in_on_time_until"
                            type="time"
                            class="w-full rounded border px-3 py-2"
                        />
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs text-on-surface-variant"
                            >Jam tutup check-in</label
                        >
                        <input
                            v-model="form.check_in_close_at"
                            type="time"
                            class="w-full rounded border px-3 py-2"
                        />
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs text-on-surface-variant"
                            >Jam buka check-out</label
                        >
                        <input
                            v-model="form.check_out_open_at"
                            type="time"
                            class="w-full rounded border px-3 py-2"
                        />
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs text-on-surface-variant"
                            >Jam tutup check-out</label
                        >
                        <input
                            v-model="form.check_out_close_at"
                            type="time"
                            class="w-full rounded border px-3 py-2"
                        />
                    </div>
                </div>
            </div>

            <div class="space-y-1">
                <label class="text-sm font-medium text-on-surface-variant"
                    >Catatan</label
                >
                <textarea
                    v-model="form.notes"
                    class="w-full rounded border px-3 py-2"
                    placeholder="Contoh: Siswa dipulangkan lebih awal karena rapat guru"
                />
            </div>

            <div class="space-y-2">
                <p class="text-sm font-medium text-on-surface-variant">
                    Pengaturan perilaku override
                </p>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <label
                        ><input v-model="form.is_active" type="checkbox" />
                        Nyalakan Event</label
                    >
                    <label
                        ><input
                            v-model="form.override_attendance_policy"
                            type="checkbox"
                        />
                        Jam di atas dipakai</label
                    >
                    <label
                        ><input v-model="form.override_schedule" type="checkbox" />
                        Jadwal Normal Berubah</label
                    >
                    <label
                        ><input v-model="form.allow_check_in" type="checkbox" />
                        boleh absen masuk</label
                    >
                    <label
                        ><input v-model="form.allow_check_out" type="checkbox" />
                        boleh absen pulang</label
                    >
                    <label
                        ><input v-model="form.waive_check_out" type="checkbox" />
                        tidak wajib absen pulang</label
                    >
                    <label
                        ><input
                            v-model="form.dismiss_students_early"
                            type="checkbox"
                        />
                        Siswa pulang lebih awal</label
                    >
                </div>
            </div>

            <button
                :disabled="form.processing"
                type="submit"
                class="rounded bg-primary px-4 py-2 text-on-primary"
            >
                Simpan
            </button>
        </form>
    </div>
</template>

