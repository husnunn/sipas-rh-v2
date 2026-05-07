<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import { bulkDestroy, create, destroy, edit, index, show } from '@/actions/App/Http/Controllers/Admin/ScheduleController';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import TablePagination from '@/components/TablePagination.vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { firstVisitErrorMessage, isFlashErrorPage } from '@/lib/inertiaVisitHelpers';
import type {PaginatedData} from '@/types';
import type { ClassRoom, Schedule, SchoolYear, Subject, TeacherProfile } from '@/types/models';

defineOptions({
    layout: AppSidebarLayout,
});

const props = defineProps<{
    schedules: PaginatedData<Schedule>;
    activeSchoolYear: SchoolYear | null;
    days: Record<string, string>;
    filters: {
        search?: string | null;
        school_year_id?: number | null;
        class_id?: number | null;
        subject_id?: number | null;
        teacher_profile_id?: number | null;
        day_of_week?: number | null;
    };
    schoolYears: Pick<SchoolYear, 'id' | 'name' | 'is_active'>[];
    classes: Pick<ClassRoom, 'id' | 'name' | 'school_year_id'>[];
    subjects: Pick<Subject, 'id' | 'code' | 'name'>[];
    teachers: Pick<TeacherProfile, 'id' | 'full_name'>[];
}>();

type UiFilters = {
    search: string;
    school_year_id: string;
    class_id: string;
    subject_id: string;
    teacher_profile_id: string;
    day_of_week: string;
};

function fromServer(f: typeof props.filters): UiFilters {
    return {
        search: f.search?.trim() ?? '',
        school_year_id: f.school_year_id ? String(f.school_year_id) : '',
        class_id: f.class_id ? String(f.class_id) : '',
        subject_id: f.subject_id ? String(f.subject_id) : '',
        teacher_profile_id: f.teacher_profile_id ? String(f.teacher_profile_id) : '',
        day_of_week: f.day_of_week ? String(f.day_of_week) : '',
    };
}

const filterForm = ref<UiFilters>(fromServer(props.filters));

watch(
    () => props.filters,
    (f) => {
        filterForm.value = fromServer(f);
    },
    { deep: true },
);

function toQuery(f: UiFilters): Record<string, string> {
    const q: Record<string, string> = {};
    const s = f.search.trim();

    if (s !== '') {
        q.search = s;
    }

    if (f.school_year_id !== '') {
        q.school_year_id = f.school_year_id;
    }

    if (f.class_id !== '') {
        q.class_id = f.class_id;
    }

    if (f.subject_id !== '') {
        q.subject_id = f.subject_id;
    }

    if (f.teacher_profile_id !== '') {
        q.teacher_profile_id = f.teacher_profile_id;
    }

    if (f.day_of_week !== '') {
        q.day_of_week = f.day_of_week;
    }

    return q;
}

function applyFilters(patch: Partial<UiFilters> = {}): void {
    Object.assign(filterForm.value, patch);
    router.get(index().url, toQuery(filterForm.value), {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
}

let debounceTimer: ReturnType<typeof setTimeout> | undefined;
function queueSearch(): void {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }

    debounceTimer = setTimeout(() => applyFilters(), 350);
}

const selectedIds = ref<number[]>([]);

const allSelected = computed({
    get: () => props.schedules.data.length > 0 && selectedIds.value.length === props.schedules.data.length,
    set: (value) => {
        if (value) {
            selectedIds.value = props.schedules.data.map(item => item.id);
        } else {
            selectedIds.value = [];
        }
    }
});

const confirmBulkDeleteRef = ref<InstanceType<typeof ConfirmDialog> | null>(null);

const bulkDelete = async () => {
    const confirmed = await confirmBulkDeleteRef.value?.open();

    if (!confirmed) {
        return;
    }

    router.delete(bulkDestroy().url, {
        data: { ids: selectedIds.value },
        onSuccess: (page) => {
            if (isFlashErrorPage(page)) {
                return;
            }

            selectedIds.value = [];
        },
        onError: (errors) => {
            toast.error(
                firstVisitErrorMessage(
                    errors as Record<string, string | string[]>,
                    'Gagal menghapus jadwal. Periksa data lalu coba lagi.',
                ),
            );
        },
    });
};

const confirmDeleteRef = ref<InstanceType<typeof ConfirmDialog> | null>(null);

const handleDelete = async (schedule: Schedule) => {
    const confirmed = await confirmDeleteRef.value?.open();

    if (!confirmed) {
        return;
    }

    router.delete(destroy(schedule).url, {
        onError: (errors) => {
            toast.error(
                firstVisitErrorMessage(
                    errors as Record<string, string | string[]>,
                    'Gagal menghapus jadwal. Periksa data lalu coba lagi.',
                ),
            );
        },
    });
};

</script>

<template>
    <Head title="Manajemen Jadwal" />

    <div class="flex flex-col gap-stack-lg max-w-7xl mx-auto w-full">
        <!-- Page Header & Global Action -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
            <div>
                <h2 class="font-h2 text-h2 text-on-surface">Manajemen Jadwal</h2>
                <p class="font-body-md text-body-md text-on-surface-variant mt-1">Manage class schedules and teacher assignments for the semester.</p>
            </div>
            <Link
                :href="create().url"
                class="bg-primary hover:bg-primary-container text-on-primary font-label-sm text-label-sm px-5 py-2.5 rounded-lg flex items-center gap-2 shadow-[0_4px_6px_rgba(0,0,0,0.05)] transition-all">
                <span class="material-symbols-outlined text-[18px]">calendar_add_on</span>
                Add New Schedule
            </Link>
        </div>

        <!-- Enhanced Data Controls -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">
            <!-- Search Box -->
            <div class="lg:col-span-4 bg-surface-container-lowest p-2 rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest flex items-center relative">
                <span class="material-symbols-outlined absolute left-4 text-outline">search</span>
                <input
                    v-model="filterForm.search"
                    class="w-full pl-10 pr-4 py-2 bg-transparent border-none focus:ring-0 font-body-md text-body-md text-on-surface placeholder:text-outline-variant outline-none"
                    placeholder="Cari kelas, mapel, guru, ruangan..."
                    type="text"
                    @input="queueSearch"
                />
            </div>
            <!-- Filters -->
            <div class="lg:col-span-8 bg-surface-container-lowest p-2 rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest flex flex-wrap items-center gap-2">
                <div class="relative flex-1 min-w-[120px]">
                    <select
                        v-model="filterForm.school_year_id"
                        class="w-full appearance-none bg-surface-bright border border-outline-variant rounded-lg font-body-md text-body-md px-4 py-2 pr-10 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all"
                        @change="applyFilters()"
                    >
                        <option value="">Tahun ajaran (aktif)</option>
                        <option v-for="item in schoolYears" :key="item.id" :value="String(item.id)">
                            {{ item.name }} {{ item.is_active ? '(aktif)' : '' }}
                        </option>
                    </select>
                    <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline pointer-events-none">expand_more</span>
                </div>
                <div class="relative flex-1 min-w-[120px]">
                    <select
                        v-model="filterForm.day_of_week"
                        class="w-full appearance-none bg-surface-bright border border-outline-variant rounded-lg font-body-md text-body-md px-4 py-2 pr-10 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all"
                        @change="applyFilters()"
                    >
                        <option value="">Hari (semua)</option>
                        <option v-for="(label, value) in days" :key="value" :value="String(value)">{{ label }}</option>
                    </select>
                    <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline pointer-events-none">expand_more</span>
                </div>
                <div class="relative flex-1 min-w-[140px]">
                    <select
                        v-model="filterForm.class_id"
                        class="w-full appearance-none bg-surface-bright border border-outline-variant rounded-lg font-body-md text-body-md px-4 py-2 pr-10 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all"
                        @change="applyFilters()"
                    >
                        <option value="">Kelas (semua)</option>
                        <option v-for="item in classes" :key="item.id" :value="String(item.id)">{{ item.name }}</option>
                    </select>
                    <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline pointer-events-none">expand_more</span>
                </div>
                <div class="relative flex-1 min-w-[160px]">
                    <select
                        v-model="filterForm.subject_id"
                        class="w-full appearance-none bg-surface-bright border border-outline-variant rounded-lg font-body-md text-body-md px-4 py-2 pr-10 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all"
                        @change="applyFilters()"
                    >
                        <option value="">Mapel (semua)</option>
                        <option v-for="item in subjects" :key="item.id" :value="String(item.id)">{{ item.code }} - {{ item.name }}</option>
                    </select>
                    <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline pointer-events-none">expand_more</span>
                </div>
                <div class="relative flex-1 min-w-[160px]">
                    <select
                        v-model="filterForm.teacher_profile_id"
                        class="w-full appearance-none bg-surface-bright border border-outline-variant rounded-lg font-body-md text-body-md px-4 py-2 pr-10 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all"
                        @change="applyFilters()"
                    >
                        <option value="">Guru (semua)</option>
                        <option v-for="item in teachers" :key="item.id" :value="String(item.id)">{{ item.full_name }}</option>
                    </select>
                    <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline pointer-events-none">expand_more</span>
                </div>
            </div>
        </div>

        <!-- Data Table Container -->
        <div class="bg-surface-container-lowest rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest overflow-hidden flex flex-col">
            
            <!-- Bulk Actions Toolbar -->
            <div v-if="selectedIds.length > 0" class="bg-primary-container/20 px-6 py-3 border-b border-surface-container-highest flex items-center justify-between">
                <span class="font-medium text-on-surface text-sm">{{ selectedIds.length }} data terpilih</span>
                <div class="flex gap-2">
                    <button @click="bulkDelete" class="font-label-sm text-label-sm text-error bg-error-container hover:bg-error-container-hover px-4 py-2 rounded-lg transition-colors flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">delete</span>
                        Hapus Terpilih
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[1000px]">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-surface-container-highest">
                            <th class="py-3 px-6 w-12 text-center">
                                <input type="checkbox" v-model="allSelected" class="rounded border-outline-variant text-primary focus:ring-primary w-4 h-4 cursor-pointer" />
                            </th>
                            <th class="font-table-header text-table-header text-on-surface-variant py-3 px-6 uppercase tracking-wider w-32">Day</th>
                            <th class="font-table-header text-table-header text-on-surface-variant py-3 px-6 uppercase tracking-wider w-40">Time</th>
                            <th class="font-table-header text-table-header text-on-surface-variant py-3 px-6 uppercase tracking-wider w-32">Class</th>
                            <th class="font-table-header text-table-header text-on-surface-variant py-3 px-6 uppercase tracking-wider">Subject</th>
                            <th class="font-table-header text-table-header text-on-surface-variant py-3 px-6 uppercase tracking-wider">Teacher</th>
                            <th class="font-table-header text-table-header text-on-surface-variant py-3 px-6 uppercase tracking-wider text-right w-32">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container-highest">
                        <tr v-for="(schedule, index) in schedules.data" :key="schedule.id" :class="[index % 2 === 0 ? 'bg-surface-container-lowest' : 'bg-surface-container-low/40', selectedIds.includes(schedule.id) ? 'bg-primary-container/10' : '']" class="hover:bg-surface-bright transition-colors group">
                            <td class="py-4 px-6 text-center">
                                <input type="checkbox" :value="schedule.id" v-model="selectedIds" class="rounded border-outline-variant text-primary focus:ring-primary w-4 h-4 cursor-pointer" />
                            </td>
                            <td class="font-body-md text-body-md text-on-surface py-4 px-6 font-medium capitalize">{{ days[schedule.day_of_week] || schedule.day_of_week }}</td>
                            <td class="font-body-md text-body-md text-on-surface-variant py-4 px-6">{{ schedule.start_time?.substring(0,5) }} - {{ schedule.end_time?.substring(0,5) }}</td>
                            <td class="font-body-md text-body-md text-on-surface-variant py-4 px-6">{{ schedule.class_room?.name }}</td>
                            <td class="font-body-md text-body-md text-on-surface py-4 px-6">{{ schedule.subject?.name }}</td>
                            <td class="font-body-md text-body-md text-on-surface py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-6 h-6 rounded-full bg-secondary-fixed text-on-secondary-fixed flex items-center justify-center font-bold text-[10px] uppercase">
                                        {{ schedule.teacher_profile?.full_name?.substring(0,2) || 'TC' }}
                                    </div>
                                    <span class="font-medium text-sm">{{ schedule.teacher_profile?.full_name }}</span>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="flex items-center justify-end gap-1 opacity-70 group-hover:opacity-100 transition-opacity">
                                    <Link :href="show(schedule).url" class="p-1.5 text-outline hover:text-primary transition-colors rounded-md hover:bg-surface-container" title="Detail"><span class="material-symbols-outlined text-[20px]">visibility</span></Link>
                                    <Link :href="edit(schedule).url" class="p-1.5 text-outline hover:text-primary transition-colors rounded-md hover:bg-surface-container" title="Edit"><span class="material-symbols-outlined text-[20px]">edit</span></Link>
                                    <button class="p-1.5 text-outline hover:text-error transition-colors rounded-md hover:bg-surface-container" title="Delete" @click="handleDelete(schedule)"><span class="material-symbols-outlined text-[20px]">delete</span></button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="schedules.data.length === 0">
                            <td colspan="7" class="text-center py-8 text-on-surface-variant">No schedules found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <TablePagination
                :from="schedules.from"
                :to="schedules.to"
                :total="schedules.total"
                :links="schedules.links"
                item-label="jadwal"
            />
        </div>
    </div>
    <ConfirmDialog
        ref="confirmDeleteRef"
        title="Hapus Jadwal"
        message="Data jadwal ini akan dihapus secara permanen. Apakah Anda yakin?"
        confirm-text="Ya, Hapus"
        cancel-text="Batal"
        variant="danger"
    />
    <ConfirmDialog
        ref="confirmBulkDeleteRef"
        title="Hapus Massal Jadwal"
        :message="`${selectedIds.length} jadwal terpilih akan dihapus secara permanen. Apakah Anda yakin?`"
        confirm-text="Ya, Hapus Semua"
        cancel-text="Batal"
        variant="danger"
    />
</template>
