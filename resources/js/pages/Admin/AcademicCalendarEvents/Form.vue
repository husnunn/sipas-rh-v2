<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { index, store, update } from '@/actions/App/Http/Controllers/Admin/AcademicCalendarEventController';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { academicEventTypeLabel } from '@/lib/academicCalendarDisplay';
import type { AcademicCalendarEvent } from '@/types/models';

defineOptions({ layout: AppSidebarLayout });

const props = defineProps<{
    mode: 'create' | 'edit';
    event: AcademicCalendarEvent | null;
    eventTypes: string[];
}>();

const form = useForm({
    name: props.event?.name ?? '',
    start_date: props.event?.start_date ?? '',
    end_date: props.event?.end_date ?? '',
    event_type: props.event?.event_type ?? props.eventTypes[0],
    is_active: props.event?.is_active ?? true,
    allow_attendance: props.event?.allow_attendance ?? false,
    override_schedule: props.event?.override_schedule ?? false,
    notes: props.event?.notes ?? '',
});

const submit = () => {
    if (props.mode === 'create') {
        form.post(store().url);

        return;
    }

    if (props.event) {
        form.put(update(props.event).url);
    }
};
</script>

<template>
    <Head :title="mode === 'create' ? 'Tambah Event Kalender' : 'Edit Event Kalender'" />
    <div class="space-y-6 max-w-2xl">
        <Link :href="index().url" class="text-sm text-on-surface-variant">Kembali</Link>
        <h1 class="text-2xl font-semibold">{{ mode === 'create' ? 'Tambah Event Kalender' : 'Edit Event Kalender' }}</h1>
        <form class="space-y-4" @submit.prevent="submit">
            <input v-model="form.name" type="text" placeholder="Nama event/libur" class="w-full rounded border px-3 py-2" />
            <div class="grid grid-cols-2 gap-3">
                <input v-model="form.start_date" type="date" class="rounded border px-3 py-2" />
                <input v-model="form.end_date" type="date" class="rounded border px-3 py-2" />
            </div>
            <select v-model="form.event_type" class="w-full rounded border px-3 py-2">
                <option v-for="type in eventTypes" :key="type" :value="type">
                    {{ academicEventTypeLabel(type) }}
                </option>
            </select>
            <textarea v-model="form.notes" class="w-full rounded border px-3 py-2" placeholder="Catatan" />
            <label class="block"><input v-model="form.is_active" type="checkbox" /> Aktif</label>
            <label class="block"><input v-model="form.allow_attendance" type="checkbox" /> Izinkan absensi</label>
            <label class="block"><input v-model="form.override_schedule" type="checkbox" /> Override jadwal normal</label>
            <button :disabled="form.processing" type="submit" class="rounded bg-primary px-4 py-2 text-on-primary">Simpan</button>
        </form>
    </div>
</template>
