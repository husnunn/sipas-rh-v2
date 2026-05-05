<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import { show as accountShow } from '@/actions/App/Http/Controllers/Admin/AccountController';
import {
    exportCsv as attendanceExportCsv,
    index as attendanceRecordsIndex,
    printReport as attendancePrintReport,
} from '@/actions/App/Http/Controllers/Admin/AttendanceMonitoringController';
import { show as studentShow } from '@/actions/App/Http/Controllers/Admin/StudentController';
import TablePagination from '@/components/TablePagination.vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { PaginatedData } from '@/types';
import type { AdminAttendanceMonitoringRecord, ClassRoom, SchoolYear } from '@/types/models';

type MonitoringFilters = {
    school_year_id: number | null;
    class_id: number | null;
    semester: string;
    status: string;
    attendance_type: string;
};

type ClassOption = Pick<ClassRoom, 'id' | 'name' | 'school_year_id'> & {
    school_year?: Pick<SchoolYear, 'id' | 'name'>;
};

type StudentOption = { id: number; label: string };

defineOptions({
    layout: AppSidebarLayout,
});

const props = defineProps<{
    records: PaginatedData<AdminAttendanceMonitoringRecord>;
    filters: MonitoringFilters;
    stats: {
        total: number;
        approved: number;
        rejected: number;
        approval_rate: number;
    };
    school_timezone: string;
    classes: ClassOption[];
    school_years: Pick<SchoolYear, 'id' | 'name'>[];
    students_for_report: StudentOption[];
}>();

const applying = ref(false);
const reportStudentId = ref<string>('');

const local = reactive({
    school_year_id: '',
    class_id: '',
    semester: 'all',
    status: 'all',
    attendance_type: 'all',
});

function syncFromServer(f: MonitoringFilters): void {
    local.school_year_id =
        f.school_year_id != null ? String(f.school_year_id) : '';
    local.class_id = f.class_id != null ? String(f.class_id) : '';
    local.semester = f.semester;
    local.status = f.status;
    local.attendance_type = f.attendance_type;
}

syncFromServer(props.filters);

watch(
    () => props.filters,
    (f) => {
        syncFromServer(f);
    },
    { deep: true },
);

function buildQuery(extra: Record<string, string | number> = {}): Record<string, string | number> {
    const q: Record<string, string | number> = { ...extra };

    if (local.school_year_id) {
        q.school_year_id = Number(local.school_year_id);
    }

    if (local.class_id) {
        q.class_id = Number(local.class_id);
    }

    if (local.semester !== 'all') {
        q.semester = local.semester;
    }

    if (local.status !== 'all') {
        q.status = local.status;
    }

    if (local.attendance_type !== 'all') {
        q.attendance_type = local.attendance_type;
    }

    return q;
}

function applyFilters(): void {
    applying.value = true;
    router.get(attendanceRecordsIndex().url, buildQuery(), {
        preserveState: true,
        replace: true,
        onFinish: () => {
            applying.value = false;
        },
    });
}

function resetFilters(): void {
    local.school_year_id = '';
    local.class_id = '';
    local.semester = 'all';
    local.status = 'all';
    local.attendance_type = 'all';
    reportStudentId.value = '';
    applying.value = true;
    router.get(
        attendanceRecordsIndex().url,
        {},
        {
            replace: true,
            onFinish: () => {
                applying.value = false;
            },
        },
    );
}

function exportHref(extra: Record<string, string | number> = {}): string {
    return attendanceExportCsv.url({ query: buildQuery(extra) });
}

function printHref(extra: Record<string, string | number> = {}): string {
    return attendancePrintReport.url({ query: buildQuery(extra) });
}

const canExportClass = computed(
    () => Boolean(local.class_id) && Boolean(local.school_year_id),
);

const canExportSchoolYear = computed(() => Boolean(local.school_year_id));

const canExportStudent = computed(
    () =>
        Boolean(reportStudentId.value) &&
        (Boolean(local.class_id) || Boolean(local.school_year_id)),
);

function exportStudentHref(): string {
    return exportHref({
        report: 'student',
        student_profile_id: Number(reportStudentId.value),
    });
}

function printStudentHref(): string {
    return printHref({
        report: 'student',
        student_profile_id: Number(reportStudentId.value),
    });
}

function formatRow(iso?: string | null): { date: string; time: string } {
    if (!iso) {
        return { date: '-', time: '-' };
    }

    try {
        const d = new Date(iso);

        if (Number.isNaN(d.getTime())) {
            return { date: '-', time: '-' };
        }

        const dateFmt = new Intl.DateTimeFormat('id-ID', {
            timeZone: props.school_timezone,
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });

        const timeFmt = new Intl.DateTimeFormat('id-ID', {
            timeZone: props.school_timezone,
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        });

        return {
            date: dateFmt.format(d),
            time: timeFmt.format(d).replace('.', ':'),
        };
    } catch {
        return { date: '-', time: '-' };
    }
}

function attendanceSiteName(record: AdminAttendanceMonitoringRecord): string {
    return record.site?.name ?? '-';
}

function noteLabel(record: AdminAttendanceMonitoringRecord): string {
    return record.reason_detail?.trim() || '-';
}

function statusBadgeClass(status: string): string {
    if (status === 'approved' || status === 'present') {
        return 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200';
    }

    if (status === 'late') {
        return 'bg-amber-100 text-amber-800 ring-1 ring-amber-200';
    }

    if (status === 'rejected') {
        return 'bg-rose-100 text-rose-700 ring-1 ring-rose-200';
    }

    if (status === 'cancelled') {
        return 'bg-slate-200 text-slate-700 ring-1 ring-slate-300';
    }

    return 'bg-slate-100 text-slate-700';
}

function formatAttendanceTime(value?: string | null): string {
    if (!value) {
return '-';
}

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
return value;
}

    const parts = new Intl.DateTimeFormat('en-GB', {
        timeZone: 'Asia/Jakarta',
        hour: '2-digit',
        minute: '2-digit',
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour12: false,
    }).formatToParts(date);

    const get = (type: string) => parts.find((part) => part.type === type)?.value ?? '';

    return `${get('hour')}:${get('minute')}, ${get('day')}-${get('month')}-${get('year')}`;
}
</script>

<template>
    <Head title="Riwayat absensi siswa" />

    <div class="mx-auto flex w-full max-w-[1600px] flex-col gap-6">
        <div
            class="flex flex-col justify-between gap-4 md:flex-row md:items-end"
        >
            <div class="space-y-1">
                <h1 class="font-h2 text-h2 text-on-surface">
                    Riwayat absensi siswa
                </h1>
                <p
                    class="font-body-md text-body-md max-w-3xl text-on-surface-variant"
                >
                    Menampilkan absensi per jadwal mapel, absensi harian (masuk /
                    pulang), dan status manual (izin / sakit / dispensasi).
                    Rentang tanggal mengikuti tahun ajaran yang dipilih, atau
                    tahun ajaran aktif, atau 120 hari terakhir. Zona waktu:
                    {{ school_timezone }}.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="font-label-sm text-label-sm inline-flex items-center gap-2 rounded-lg border border-surface-container-highest bg-surface-container-lowest px-4 py-2 text-on-surface shadow-sm transition hover:bg-surface-container"
                    @click="resetFilters"
                >
                    <span class="material-symbols-outlined text-lg"
                        >refresh</span
                    >
                    Reset filter
                </button>
                <a
                    :href="printHref({ report: 'monitoring' })"
                    target="_blank"
                    rel="noopener"
                    class="font-label-sm text-label-sm inline-flex items-center gap-2 rounded-lg border border-surface-container-highest bg-surface-container-lowest px-4 py-2 text-on-surface shadow-sm transition hover:bg-surface-container"
                >
                    <span class="material-symbols-outlined text-lg"
                        >print</span
                    >
                    Cetak tabel
                </a>
                <a
                    :href="exportHref({ report: 'monitoring' })"
                    class="font-label-sm text-label-sm inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2 text-on-primary shadow-md transition hover:brightness-110 active:scale-[0.98]"
                >
                    <span class="material-symbols-outlined text-lg"
                        >download</span
                    >
                    Export CSV
                </a>
            </div>
        </div>

        <div
            class="grid grid-cols-1 gap-4 rounded-xl border border-surface-container-highest bg-surface-container-lowest p-4 shadow-sm md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5"
        >
            <div class="space-y-1">
                <label
                    class="font-label-sm text-label-sm text-on-surface-variant"
                    >Tahun ajaran</label
                >
                <select
                    v-model="local.school_year_id"
                    class="font-body-md text-body-md w-full rounded-lg border border-outline-variant bg-surface-container-lowest px-3 py-2 text-on-surface outline-none focus:ring-2 focus:ring-primary"
                >
                    <option value="">Semua (aktif / 120 hari)</option>
                    <option
                        v-for="y in school_years"
                        :key="y.id"
                        :value="String(y.id)"
                    >
                        {{ y.name }}
                    </option>
                </select>
            </div>
            <div class="space-y-1">
                <label
                    class="font-label-sm text-label-sm text-on-surface-variant"
                    >Kelas</label
                >
                <select
                    v-model="local.class_id"
                    class="font-body-md text-body-md w-full rounded-lg border border-outline-variant bg-surface-container-lowest px-3 py-2 text-on-surface outline-none focus:ring-2 focus:ring-primary"
                >
                    <option value="">Semua kelas</option>
                    <option
                        v-for="c in classes"
                        :key="c.id"
                        :value="String(c.id)"
                    >
                        {{ c.name }}
                        <template v-if="c.school_year"
                            >({{ c.school_year.name }})</template
                        >
                    </option>
                </select>
            </div>
            <div class="space-y-1">
                <label
                    class="font-label-sm text-label-sm text-on-surface-variant"
                    >Semester (jadwal mapel)</label
                >
                <select
                    v-model="local.semester"
                    class="font-body-md text-body-md w-full rounded-lg border border-outline-variant bg-surface-container-lowest px-3 py-2 text-on-surface outline-none focus:ring-2 focus:ring-primary"
                >
                    <option value="all">Semua</option>
                    <option value="1">Semester 1</option>
                    <option value="2">Semester 2</option>
                </select>
            </div>
            <div class="space-y-1">
                <label
                    class="font-label-sm text-label-sm text-on-surface-variant"
                    >Status</label
                >
                <select
                    v-model="local.status"
                    class="font-body-md text-body-md w-full rounded-lg border border-outline-variant bg-surface-container-lowest px-3 py-2 text-on-surface outline-none focus:ring-2 focus:ring-primary"
                >
                    <option value="all">Semua</option>
                    <option value="approved">Berhasil / hadir</option>
                    <option value="rejected">Ditolak (mapel)</option>
                </select>
            </div>
            <div class="space-y-1">
                <label
                    class="font-label-sm text-label-sm text-on-surface-variant"
                    >Tipe absensi</label
                >
                <select
                    v-model="local.attendance_type"
                    class="font-body-md text-body-md w-full rounded-lg border border-outline-variant bg-surface-container-lowest px-3 py-2 text-on-surface outline-none focus:ring-2 focus:ring-primary"
                >
                    <option value="all">Semua</option>
                    <option value="check_in">Check-in (mapel)</option>
                    <option value="check_out">Check-out (mapel)</option>
                    <option value="daily_check_in">Masuk harian</option>
                    <option value="daily_check_out">Pulang harian</option>
                    <option value="manual_excused">Izin (manual)</option>
                    <option value="manual_sick">Sakit (manual)</option>
                    <option value="manual_dispensation">Dispensasi (manual)</option>
                </select>
            </div>
            <div class="flex items-end xl:col-span-5">
                <button
                    type="button"
                    class="font-label-sm text-label-sm w-full rounded-lg bg-secondary-container py-2.5 text-on-secondary-container transition hover:brightness-95 disabled:opacity-50 sm:w-auto sm:min-w-[200px]"
                    :disabled="applying"
                    @click="applyFilters"
                >
                    Terapkan filter
                </button>
            </div>
        </div>

        <div
            class="rounded-xl border border-outline-variant/40 bg-surface-container-low/30 p-4"
        >
            <h2
                class="font-label-md text-label-md mb-3 text-on-surface-variant uppercase tracking-wide"
            >
                Laporan & cetak
            </h2>
            <div
                class="flex flex-col gap-4 lg:flex-row lg:flex-wrap lg:items-end"
            >
                <div
                    v-if="students_for_report.length"
                    class="min-w-[220px] flex-1 space-y-1"
                >
                    <label
                        class="font-label-sm text-label-sm text-on-surface-variant"
                        >Siswa (untuk laporan per siswa)</label
                    >
                    <select
                        v-model="reportStudentId"
                        class="font-body-md text-body-md w-full rounded-lg border border-outline-variant bg-surface-container-lowest px-3 py-2 text-on-surface outline-none focus:ring-2 focus:ring-primary"
                    >
                        <option value="">— pilih —</option>
                        <option
                            v-for="s in students_for_report"
                            :key="s.id"
                            :value="String(s.id)"
                        >
                            {{ s.label }}
                        </option>
                    </select>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a
                        v-if="canExportStudent"
                        :href="exportStudentHref()"
                        class="font-label-sm text-label-sm inline-flex items-center gap-1 rounded-lg border border-primary/40 bg-surface-container-lowest px-3 py-2 text-primary"
                    >
                        CSV per siswa
                    </a>
                    <a
                        v-if="canExportStudent"
                        :href="printStudentHref()"
                        target="_blank"
                        rel="noopener"
                        class="font-label-sm text-label-sm inline-flex items-center gap-1 rounded-lg border border-primary/40 bg-surface-container-lowest px-3 py-2 text-primary"
                    >
                        Cetak per siswa
                    </a>
                    <a
                        v-if="canExportClass"
                        :href="exportHref({ report: 'class' })"
                        class="font-label-sm text-label-sm inline-flex items-center gap-1 rounded-lg border border-outline-variant px-3 py-2 text-on-surface"
                    >
                        CSV per kelas
                    </a>
                    <a
                        v-if="canExportClass"
                        :href="printHref({ report: 'class' })"
                        target="_blank"
                        rel="noopener"
                        class="font-label-sm text-label-sm inline-flex items-center gap-1 rounded-lg border border-outline-variant px-3 py-2 text-on-surface"
                    >
                        Cetak per kelas
                    </a>
                    <a
                        v-if="canExportSchoolYear"
                        :href="exportHref({ report: 'school_year' })"
                        class="font-label-sm text-label-sm inline-flex items-center gap-1 rounded-lg border border-outline-variant px-3 py-2 text-on-surface"
                    >
                        CSV per angkatan (th. ajaran)
                    </a>
                    <a
                        v-if="canExportSchoolYear"
                        :href="printHref({ report: 'school_year' })"
                        target="_blank"
                        rel="noopener"
                        class="font-label-sm text-label-sm inline-flex items-center gap-1 rounded-lg border border-outline-variant px-3 py-2 text-on-surface"
                    >
                        Cetak per angkatan
                    </a>
                </div>
            </div>
            <p class="font-body-sm text-body-sm mt-2 text-on-surface-variant">
                Per kelas memerlukan tahun ajaran + kelas. Per siswa memerlukan
                pilihan siswa (muncul setelah tahun ajaran atau kelas dipilih).
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div
                class="flex items-center gap-4 rounded-xl bg-primary p-5 shadow-lg"
            >
                <div class="rounded-full bg-white/10 p-3">
                    <span
                        class="material-symbols-outlined text-3xl text-on-primary"
                        style="font-variation-settings: 'FILL' 1"
                    >
                        how_to_reg
                    </span>
                </div>
                <div>
                    <p
                        class="font-label-sm text-label-sm tracking-wide text-on-primary/80 uppercase"
                    >
                        Tingkat berhasil
                    </p>
                    <h3 class="text-2xl font-black text-on-primary">
                        {{ stats.approval_rate }}%
                    </h3>
                    <p
                        class="font-body-sm text-body-sm mt-1 text-on-primary/90"
                    >
                        Dari total {{ stats.total }} entri (filter aktif)
                    </p>
                </div>
            </div>
            <div
                class="flex items-center gap-4 rounded-xl border border-surface-container-highest bg-surface-container-lowest p-5 shadow-sm"
            >
                <div class="rounded-full bg-amber-50 p-3 dark:bg-amber-950/40">
                    <span
                        class="material-symbols-outlined text-3xl text-amber-600"
                        style="font-variation-settings: 'FILL' 1"
                    >
                        assignment_late
                    </span>
                </div>
                <div>
                    <p
                        class="font-label-sm text-label-sm tracking-wide text-on-surface-variant uppercase"
                    >
                        Ditolak (mapel)
                    </p>
                    <h3 class="text-2xl font-black text-on-surface">
                        {{ stats.rejected }}
                    </h3>
                </div>
            </div>
            <div
                class="flex items-center gap-4 rounded-xl border border-surface-container-highest bg-surface-container-lowest p-5 shadow-sm"
            >
                <div
                    class="rounded-full bg-emerald-50 p-3 dark:bg-emerald-950/40"
                >
                    <span
                        class="material-symbols-outlined text-3xl text-emerald-700"
                        style="font-variation-settings: 'FILL' 1"
                    >
                        fact_check
                    </span>
                </div>
                <div>
                    <p
                        class="font-label-sm text-label-sm tracking-wide text-on-surface-variant uppercase"
                    >
                        Total entri
                    </p>
                    <h3 class="text-2xl font-black text-on-surface">
                        {{ stats.total }}
                    </h3>
                    <p class="font-body-sm text-body-sm mt-1 text-primary">
                        Berhasil: {{ stats.approved }}
                    </p>
                </div>
            </div>
        </div>

        <div
            class="overflow-hidden rounded-xl border border-surface-container-highest bg-surface-container-lowest shadow-sm"
        >
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr
                            class="border-b border-outline-variant bg-surface-container"
                        >
                            <th
                                class="font-label-sm text-label-sm px-4 py-3 text-on-surface-variant"
                            >
                                Siswa
                            </th>
                            <th
                                class="font-label-sm text-label-sm px-4 py-3 text-on-surface-variant"
                            >
                                Sumber
                            </th>
                            <th
                                class="font-label-sm text-label-sm px-4 py-3 text-on-surface-variant"
                            >
                                Tanggal
                            </th>
                            <th
                                class="font-label-sm text-label-sm px-4 py-3 text-on-surface-variant"
                            >
                                Jam
                            </th>
                            <th
                                class="font-label-sm text-label-sm px-4 py-3 text-on-surface-variant"
                            >
                                Tipe
                            </th>
                            <th
                                class="font-label-sm text-label-sm px-4 py-3 text-on-surface-variant"
                            >
                                Kelas
                            </th>
                            <th
                                class="font-label-sm text-label-sm px-4 py-3 text-on-surface-variant"
                            >
                                Mapel
                            </th>
                            <th
                                class="font-label-sm text-label-sm px-4 py-3 text-on-surface-variant"
                            >
                                Th. ajaran
                            </th>
                            <th
                                class="font-label-sm text-label-sm px-4 py-3 text-on-surface-variant"
                            >
                                Sem.
                            </th>
                            <th
                                class="font-label-sm text-label-sm px-4 py-3 text-on-surface-variant"
                            >
                                Lokasi
                            </th>
                            <th
                                class="font-label-sm text-label-sm px-4 py-3 text-center text-on-surface-variant"
                            >
                                Status
                            </th>
                            <th
                                class="font-label-sm text-label-sm px-4 py-3 text-on-surface-variant"
                            >
                                Catatan
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/40">
                        <tr
                            v-for="(record, idx) in records.data"
                            :key="record.row_key"
                            :class="
                                idx % 2 === 1 ? 'bg-surface-container/40' : ''
                            "
                            class="transition-colors hover:bg-primary/5"
                        >
                            <td
                                class="px-4 py-3 text-sm whitespace-nowrap text-on-surface"
                            >
                                <template v-if="record.user?.id">
                                    <Link
                                        v-if="record.student_profile_id"
                                        :href="
                                            studentShow(
                                                record.student_profile_id,
                                            ).url
                                        "
                                        class="font-medium text-primary underline-offset-2 hover:underline"
                                    >
                                        {{ record.user.name ?? '—' }}
                                    </Link>
                                    <Link
                                        v-else
                                        :href="accountShow(record.user.id).url"
                                        class="font-medium text-primary underline-offset-2 hover:underline"
                                    >
                                        {{ record.user.name ?? '—' }}
                                    </Link>
                                    <div
                                        class="font-mono text-xs text-on-surface-variant"
                                    >
                                        {{ record.user.username ?? '' }}
                                    </div>
                                    <div
                                        v-if="record.nis"
                                        class="text-xs text-on-surface-variant"
                                    >
                                        NIS {{ record.nis }}
                                    </div>
                                </template>
                                <template v-else>—</template>
                            </td>
                            <td
                                class="max-w-[120px] px-4 py-3 text-xs text-on-surface-variant"
                            >
                                {{ record.feed_source_label }}
                            </td>
                            <td
                                class="px-4 py-3 text-sm whitespace-nowrap text-on-surface"
                            >
                                {{
                                    formatRow(record.attendance_time ?? null)
                                        .date
                                }}
                            </td>
                            <td
                                class="px-4 py-3 text-sm font-medium whitespace-nowrap text-primary"
                            >
                                {{
                                    formatRow(record.attendance_time ?? null)
                                        .time
                                }}
                            </td>
                            <td
                                class="max-w-[140px] px-4 py-3 text-xs text-on-surface"
                            >
                                {{ record.attendance_type_label }}
                            </td>
                            <td
                                class="max-w-[120px] truncate px-4 py-3 text-sm text-on-surface-variant"
                            >
                                {{ record.class?.name ?? '—' }}
                            </td>
                            <td
                                class="max-w-[120px] truncate px-4 py-3 text-sm text-on-surface-variant"
                            >
                                {{ record.subject?.name ?? '—' }}
                            </td>
                            <td
                                class="px-4 py-3 text-sm whitespace-nowrap text-on-surface-variant"
                            >
                                {{ record.school_year?.name ?? '—' }}
                            </td>
                            <td
                                class="px-4 py-3 text-sm whitespace-nowrap text-on-surface-variant"
                            >
                                {{ record.schedule_semester ?? '—' }}
                            </td>
                            <td
                                class="px-4 py-3 text-sm whitespace-nowrap text-on-surface-variant"
                            >
                                {{ attendanceSiteName(record) }}
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase"
                                    :class="statusBadgeClass(record.status)"
                                >
                                    {{ record.status }}
                                </span>
                            </td>
                            <td
                                class="max-w-xs px-4 py-3 text-sm text-on-surface-variant"
                            >
                                {{ noteLabel(record) }}
                            </td>
                        </tr>
                        <tr v-if="records.data.length === 0">
                            <td
                                colspan="12"
                                class="font-body-md text-body-md px-4 py-10 text-center text-on-surface-variant"
                            >
                                Tidak ada data absensi untuk filter ini.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <TablePagination
                v-if="records.data.length > 0"
                :from="records.from"
                :to="records.to"
                :total="records.total"
                :links="records.links"
                item-label="entri"
            />
        </div>
    </div>
</template>
