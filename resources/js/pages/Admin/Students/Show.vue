<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    cancel as attendanceManualCancel,
    store as attendanceManualStore,
    update as attendanceManualUpdate,
} from '@/actions/App/Http/Controllers/Admin/StudentAttendanceManualStatusController';
import {
    edit,
    index,
} from '@/actions/App/Http/Controllers/Admin/StudentController';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type {
    AttendanceManualStatusRow,
    AttendanceRecord,
    AttendanceSite,
    DailyAttendanceRow,
    DailyAttendanceSummaryRow,
    StudentProfile,
} from '@/types/models';

defineOptions({ layout: AppSidebarLayout });

const props = defineProps<{
    student: StudentProfile;
    attendanceRecords: AttendanceRecord[];
    dailyAttendances: DailyAttendanceRow[];
    manualAttendanceStatuses: AttendanceManualStatusRow[];
    dailyAttendanceSummary: DailyAttendanceSummaryRow[];
    attendanceSitesForManual: Pick<AttendanceSite, 'id' | 'name'>[];
}>();

const editingManualId = ref<number | null>(null);

const createForm = useForm({
    date: new Date().toISOString().slice(0, 10),
    type: 'excused' as 'excused' | 'sick' | 'dispensation',
    reason: '',
    notes: '',
    attendance_site_id: '' as string,
});

const editForm = useForm({
    date: '',
    type: 'excused' as 'excused' | 'sick' | 'dispensation',
    reason: '',
    notes: '',
    attendance_site_id: '' as string,
});

function submitCreateManual(): void {
    createForm
        .transform((data) => ({
            ...data,
            attendance_site_id:
                data.attendance_site_id === ''
                    ? null
                    : Number(data.attendance_site_id),
        }))
        .post(attendanceManualStore.url({ student: props.student }), {
            preserveScroll: true,
            onSuccess: () => {
                createForm.reset();
                createForm.date = new Date().toISOString().slice(0, 10);
                createForm.type = 'excused';
            },
        });
}

function startEditManual(row: AttendanceManualStatusRow): void {
    if (row.status !== 'approved') {
        return;
    }

    editingManualId.value = row.id;
    editForm.date = row.date.includes('T') ? row.date.slice(0, 10) : row.date;
    editForm.type = row.type;
    editForm.reason = row.reason;
    editForm.notes = row.notes ?? '';
    editForm.attendance_site_id =
        row.attendance_site_id != null ? String(row.attendance_site_id) : '';
}

function cancelEditManual(): void {
    editingManualId.value = null;
    editForm.reset();
}

function submitEditManual(): void {
    if (editingManualId.value === null) {
        return;
    }

    editForm
        .transform((data) => ({
            ...data,
            attendance_site_id:
                data.attendance_site_id === ''
                    ? null
                    : Number(data.attendance_site_id),
        }))
        .put(
            attendanceManualUpdate.url({
                student: props.student,
                manualStatus: editingManualId.value,
            }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    cancelEditManual();
                },
            },
        );
}

function cancelManualRow(row: AttendanceManualStatusRow): void {
    if (!window.confirm('Batalkan status manual ini?')) {
        return;
    }

    router.patch(
        attendanceManualCancel.url({
            student: props.student,
            manualStatus: row.id,
        }),
        {},
        { preserveScroll: true },
    );
}

function attendanceTypeLabel(type?: string | null): string {
    if (type === 'check_in') {
        return 'Masuk';
    }

    if (type === 'check_out') {
        return 'Pulang';
    }

    return '-';
}

function attendanceStatusLabel(status?: string | null): string {
    if (status === 'approved') {
        return 'Disetujui';
    }

    if (status === 'rejected') {
        return 'Ditolak';
    }

    return '-';
}

function attendanceStatusClass(status?: string | null): string {
    if (status === 'approved') {
        return 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200';
    }

    if (status === 'rejected') {
        return 'bg-rose-100 text-rose-700 ring-1 ring-rose-200';
    }

    return 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';
}

function attendanceReasonLabel(record: AttendanceRecord): string {
    if (record.status === 'approved') {
        return 'Berhasil';
    }

    return record.reason_detail?.trim() || '-';
}

function attendanceSiteName(record: AttendanceRecord): string {
    return record.site?.name ?? record.attendance_site?.name ?? '-';
}

function formatAttendanceTime(value?: string | null): {
    date: string;
    time: string;
} {
    if (!value) {
        return { date: '-', time: '-' };
    }

    const normalized = value
        .replace('T', ' ')
        .replace(/\.\d+Z$/, '')
        .replace('Z', '');

    const [datePart, timePart = ''] = normalized.split(' ');

    if (!datePart) {
        return { date: value, time: '-' };
    }

    const [year, month, day] = datePart.split('-');
    const hhmm = timePart.slice(0, 5);

    const monthNames = [
        'Jan',
        'Feb',
        'Mar',
        'Apr',
        'Mei',
        'Jun',
        'Jul',
        'Agu',
        'Sep',
        'Okt',
        'Nov',
        'Des',
    ];
    const monthIndex = Number(month) - 1;
    const monthLabel = monthNames[monthIndex] ?? month;

    return {
        date: `${day} ${monthLabel} ${year}`,
        time: hhmm || '-',
    };
}

function manualTypeLabel(type: string): string {
    if (type === 'excused') {
        return 'Izin';
    }

    if (type === 'sick') {
        return 'Sakit';
    }

    if (type === 'dispensation') {
        return 'Dispensasi';
    }

    return type;
}

function manualRecordStatusLabel(status: string): string {
    if (status === 'approved') {
        return 'Disetujui';
    }

    if (status === 'cancelled') {
        return 'Dibatalkan';
    }

    return status;
}

function summarySourceLabel(source: string): string {
    if (source === 'holiday') {
        return 'Libur (kalender)';
    }

    if (source === 'manual_status') {
        return 'Manual';
    }

    if (source === 'daily_attendance') {
        return 'Fisik';
    }

    if (source === 'absent') {
        return 'Alpa';
    }

    return source;
}

function dailyPhysicalStatusLabel(status: string | null | undefined): string {
    if (status === 'present') {
        return 'Hadir';
    }

    if (status === 'late') {
        return 'Terlambat';
    }

    return '-';
}
</script>

<template>
    <Head title="Detail Siswa" />

    <div
        class="mx-auto max-w-6xl space-y-8 rounded-xl bg-white p-6 shadow dark:bg-slate-900"
    >
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">
            Detail Siswa
        </h1>

        <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
            <div
                class="rounded-lg border border-slate-200 p-4 dark:border-slate-700"
            >
                <p><strong>Nama:</strong> {{ student.user?.name }}</p>
                <p class="mt-2">
                    <strong>Username:</strong> {{ student.user?.username }}
                </p>
                <p class="mt-2"><strong>NIS:</strong> {{ student.nis }}</p>
            </div>

            <div
                class="rounded-lg border border-slate-200 p-4 dark:border-slate-700"
            >
                <p><strong>NISN:</strong> {{ student.nisn || '-' }}</p>
                <p class="mt-2">
                    <strong>Kelas:</strong>
                    {{ student.classes?.[0]?.name || '-' }}
                </p>
            </div>
        </div>

        <section class="space-y-3">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                Input status manual (izin / sakit / dispensasi)
            </h2>
            <form
                class="grid max-w-2xl grid-cols-1 gap-3 rounded-lg border border-slate-200 p-4 md:grid-cols-2 dark:border-slate-700"
                @submit.prevent="submitCreateManual"
            >
                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                        >Tanggal</label
                    >
                    <input
                        v-model="createForm.date"
                        type="date"
                        required
                        class="w-full rounded border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800"
                    />
                </div>
                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                        >Jenis</label
                    >
                    <select
                        v-model="createForm.type"
                        class="w-full rounded border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800"
                    >
                        <option value="excused">Izin</option>
                        <option value="sick">Sakit</option>
                        <option value="dispensation">Dispensasi</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label
                        class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                        >Alasan</label
                    >
                    <input
                        v-model="createForm.reason"
                        type="text"
                        required
                        class="w-full rounded border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800"
                    />
                </div>
                <div class="md:col-span-2">
                    <label
                        class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                        >Catatan (opsional)</label
                    >
                    <textarea
                        v-model="createForm.notes"
                        rows="2"
                        class="w-full rounded border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800"
                    />
                </div>
                <div class="md:col-span-2">
                    <label
                        class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                        >Lokasi absensi (opsional)</label
                    >
                    <select
                        v-model="createForm.attendance_site_id"
                        class="w-full rounded border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800"
                    >
                        <option value="">—</option>
                        <option
                            v-for="s in attendanceSitesForManual"
                            :key="s.id"
                            :value="String(s.id)"
                        >
                            {{ s.name }}
                        </option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <button
                        type="submit"
                        class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                        :disabled="createForm.processing"
                    >
                        Simpan status manual
                    </button>
                </div>
            </form>
        </section>

        <section v-if="editingManualId !== null" class="space-y-3">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                Edit status manual
            </h2>
            <form
                class="grid max-w-2xl grid-cols-1 gap-3 rounded-lg border border-amber-200 p-4 md:grid-cols-2 dark:border-amber-900/50"
                @submit.prevent="submitEditManual"
            >
                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                        >Tanggal</label
                    >
                    <input
                        v-model="editForm.date"
                        type="date"
                        required
                        class="w-full rounded border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800"
                    />
                </div>
                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                        >Jenis</label
                    >
                    <select
                        v-model="editForm.type"
                        class="w-full rounded border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800"
                    >
                        <option value="excused">Izin</option>
                        <option value="sick">Sakit</option>
                        <option value="dispensation">Dispensasi</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label
                        class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                        >Alasan</label
                    >
                    <input
                        v-model="editForm.reason"
                        type="text"
                        required
                        class="w-full rounded border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800"
                    />
                </div>
                <div class="md:col-span-2">
                    <label
                        class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                        >Catatan</label
                    >
                    <textarea
                        v-model="editForm.notes"
                        rows="2"
                        class="w-full rounded border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800"
                    />
                </div>
                <div class="md:col-span-2">
                    <label
                        class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                        >Lokasi absensi</label
                    >
                    <select
                        v-model="editForm.attendance_site_id"
                        class="w-full rounded border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800"
                    >
                        <option value="">—</option>
                        <option
                            v-for="s in attendanceSitesForManual"
                            :key="s.id"
                            :value="String(s.id)"
                        >
                            {{ s.name }}
                        </option>
                    </select>
                </div>
                <div class="flex flex-wrap gap-2 md:col-span-2">
                    <button
                        type="submit"
                        class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                        :disabled="editForm.processing"
                    >
                        Simpan perubahan
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm dark:border-slate-600"
                        @click="cancelEditManual"
                    >
                        Batal edit
                    </button>
                </div>
            </form>
        </section>

        <section class="space-y-3">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                Riwayat status manual
            </h2>
            <div
                class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700"
            >
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/60">
                        <tr
                            class="text-left text-slate-700 dark:text-slate-200"
                        >
                            <th class="px-4 py-3 font-semibold">Tanggal</th>
                            <th class="px-4 py-3 font-semibold">Jenis</th>
                            <th class="px-4 py-3 font-semibold">Alasan</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 font-semibold">Oleh</th>
                            <th class="px-4 py-3 font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-slate-200 dark:divide-slate-700"
                    >
                        <tr
                            v-for="m in manualAttendanceStatuses"
                            :key="m.id"
                            class="align-top"
                        >
                            <td
                                class="px-4 py-3 text-slate-900 dark:text-slate-100"
                            >
                                {{
                                    m.date.includes('T')
                                        ? m.date.slice(0, 10)
                                        : m.date
                                }}
                            </td>
                            <td class="px-4 py-3">
                                {{ manualTypeLabel(m.type) }}
                            </td>
                            <td
                                class="max-w-xs px-4 py-3 text-slate-700 dark:text-slate-200"
                            >
                                {{ m.reason }}
                            </td>
                            <td class="px-4 py-3">
                                {{ manualRecordStatusLabel(m.status) }}
                            </td>
                            <td
                                class="px-4 py-3 text-slate-600 dark:text-slate-400"
                            >
                                {{ m.created_by_user?.name ?? '—' }}
                            </td>
                            <td class="space-x-2 px-4 py-3 whitespace-nowrap">
                                <button
                                    v-if="m.status === 'approved'"
                                    type="button"
                                    class="text-sm text-emerald-700 underline"
                                    @click="startEditManual(m)"
                                >
                                    Edit
                                </button>
                                <button
                                    v-if="m.status === 'approved'"
                                    type="button"
                                    class="text-sm text-rose-700 underline"
                                    @click="cancelManualRow(m)"
                                >
                                    Batalkan
                                </button>
                                <span
                                    v-if="m.status !== 'approved'"
                                    class="text-slate-400"
                                    >—</span
                                >
                            </td>
                        </tr>
                        <tr v-if="manualAttendanceStatuses.length === 0">
                            <td
                                colspan="6"
                                class="px-4 py-6 text-center text-slate-500"
                            >
                                Belum ada status manual.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="space-y-3">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                Rekap status akhir harian (90 hari)
            </h2>
            <div
                class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700"
            >
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/60">
                        <tr
                            class="text-left text-slate-700 dark:text-slate-200"
                        >
                            <th class="px-4 py-3 font-semibold">Tanggal</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 font-semibold">Check-in</th>
                            <th class="px-4 py-3 font-semibold">Check-out</th>
                            <th class="px-4 py-3 font-semibold">Sumber</th>
                            <th class="px-4 py-3 font-semibold">Catatan</th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-slate-200 dark:divide-slate-700"
                    >
                        <tr
                            v-for="(row, idx) in dailyAttendanceSummary"
                            :key="row.date + String(idx)"
                            class="align-top"
                        >
                            <td
                                class="px-4 py-3 text-slate-900 dark:text-slate-100"
                            >
                                {{ row.date }}
                            </td>
                            <td class="px-4 py-3 font-medium">
                                {{ row.label }}
                            </td>
                            <td
                                class="px-4 py-3 text-slate-700 dark:text-slate-200"
                            >
                                {{
                                    row.check_in_at
                                        ? formatAttendanceTime(row.check_in_at)
                                              .time
                                        : '—'
                                }}
                            </td>
                            <td
                                class="px-4 py-3 text-slate-700 dark:text-slate-200"
                            >
                                {{
                                    row.check_out_at
                                        ? formatAttendanceTime(row.check_out_at)
                                              .time
                                        : '—'
                                }}
                            </td>
                            <td class="px-4 py-3">
                                {{ summarySourceLabel(row.source) }}
                            </td>
                            <td
                                class="max-w-xs px-4 py-3 text-slate-600 dark:text-slate-400"
                            >
                                {{ row.message ?? '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="space-y-3">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                Absensi harian (fisik)
            </h2>
            <div
                class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700"
            >
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/60">
                        <tr
                            class="text-left text-slate-700 dark:text-slate-200"
                        >
                            <th class="px-4 py-3 font-semibold">Tanggal</th>
                            <th class="px-4 py-3 font-semibold">Check-in</th>
                            <th class="px-4 py-3 font-semibold">Check-out</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 font-semibold">
                                Menit terlambat
                            </th>
                            <th class="px-4 py-3 font-semibold">Lokasi</th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-slate-200 dark:divide-slate-700"
                    >
                        <tr
                            v-for="d in dailyAttendances"
                            :key="d.id"
                            class="align-top"
                        >
                            <td
                                class="px-4 py-3 text-slate-900 dark:text-slate-100"
                            >
                                {{
                                    d.date.includes('T')
                                        ? d.date.slice(0, 10)
                                        : d.date
                                }}
                            </td>
                            <td
                                class="px-4 py-3 text-slate-700 dark:text-slate-200"
                            >
                                {{
                                    d.check_in_at
                                        ? formatAttendanceTime(d.check_in_at)
                                              .time +
                                          ' · ' +
                                          formatAttendanceTime(d.check_in_at)
                                              .date
                                        : '—'
                                }}
                            </td>
                            <td
                                class="px-4 py-3 text-slate-700 dark:text-slate-200"
                            >
                                {{
                                    d.check_out_at
                                        ? formatAttendanceTime(d.check_out_at)
                                              .time +
                                          ' · ' +
                                          formatAttendanceTime(d.check_out_at)
                                              .date
                                        : '—'
                                }}
                            </td>
                            <td class="px-4 py-3">
                                {{ dailyPhysicalStatusLabel(d.status ?? null) }}
                            </td>
                            <td class="px-4 py-3">
                                {{ d.late_minutes ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                {{ d.attendance_site?.name ?? '—' }}
                            </td>
                        </tr>
                        <tr v-if="dailyAttendances.length === 0">
                            <td
                                colspan="6"
                                class="px-4 py-6 text-center text-slate-500"
                            >
                                Belum ada absensi harian fisik.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="space-y-3">
            <div class="flex items-center justify-between">
                <h2
                    class="text-lg font-semibold text-slate-900 dark:text-white"
                >
                    Riwayat absensi (model lama / per mapel)
                </h2>

                <a
                    :href="`/admin/students/${student.id}/attendance-export`"
                    class="inline-flex items-center rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700"
                >
                    Export CSV
                </a>
            </div>

            <div
                class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700"
            >
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/60">
                        <tr class="text-slate-700 dark:text-slate-200">
                            <th
                                class="w-[170px] px-4 py-3 text-left font-semibold"
                            >
                                Waktu
                            </th>
                            <th
                                class="w-[110px] px-4 py-3 text-left font-semibold"
                            >
                                Tipe
                            </th>
                            <th
                                class="w-[130px] px-4 py-3 text-left font-semibold"
                            >
                                Status
                            </th>
                            <th
                                class="w-[150px] px-4 py-3 text-left font-semibold"
                            >
                                Lokasi
                            </th>
                            <th
                                class="min-w-[320px] px-4 py-3 text-left font-semibold"
                            >
                                Alasan
                            </th>
                        </tr>
                    </thead>

                    <tbody
                        class="divide-y divide-slate-200 dark:divide-slate-700"
                    >
                        <tr
                            v-for="record in attendanceRecords"
                            :key="record.id"
                            class="align-top hover:bg-slate-50/80 dark:hover:bg-slate-800/40"
                        >
                            <td
                                class="px-4 py-3 text-slate-900 dark:text-slate-100"
                            >
                                <div class="font-medium">
                                    {{
                                        formatAttendanceTime(
                                            record.attendance_time ??
                                                record.attendance_at,
                                        ).time
                                    }}
                                </div>
                                <div class="mt-0.5 text-xs text-slate-500">
                                    {{
                                        formatAttendanceTime(
                                            record.attendance_time ??
                                                record.attendance_at,
                                        ).date
                                    }}
                                </div>
                            </td>

                            <td
                                class="px-4 py-3 text-slate-700 dark:text-slate-200"
                            >
                                {{
                                    attendanceTypeLabel(record.attendance_type)
                                }}
                            </td>

                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="
                                        attendanceStatusClass(record.status)
                                    "
                                >
                                    {{ attendanceStatusLabel(record.status) }}
                                </span>
                            </td>

                            <td
                                class="px-4 py-3 whitespace-nowrap text-slate-700 dark:text-slate-200"
                            >
                                {{ attendanceSiteName(record) }}
                            </td>

                            <td
                                class="max-w-[420px] px-4 py-3 text-slate-700 dark:text-slate-200"
                                :title="attendanceReasonLabel(record)"
                            >
                                <p class="line-clamp-2">
                                    {{ attendanceReasonLabel(record) }}
                                </p>
                            </td>
                        </tr>

                        <tr v-if="attendanceRecords.length === 0">
                            <td
                                colspan="5"
                                class="px-4 py-6 text-center text-slate-500"
                            >
                                Belum ada riwayat absensi.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="flex justify-end gap-2">
            <Link
                :href="index().url"
                class="rounded-lg border px-4 py-2 text-sm"
            >
                Kembali
            </Link>
            <Link
                :href="edit(student).url"
                class="rounded-lg bg-emerald-700 px-4 py-2 text-sm text-white"
            >
                Edit
            </Link>
        </div>
    </div>
</template>
