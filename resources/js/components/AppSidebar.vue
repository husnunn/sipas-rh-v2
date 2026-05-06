<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { useMediaQuery } from '@vueuse/core';
import { computed, reactive, watch } from 'vue';
import { index as adminAcademicCalendarEvents } from '@/actions/App/Http/Controllers/Admin/AcademicCalendarEventController';
import { index as adminAccounts } from '@/actions/App/Http/Controllers/Admin/AccountController';
import { index as adminAttendanceDayOverrides } from '@/actions/App/Http/Controllers/Admin/AttendanceDayOverrideController';
import { index as adminAttendanceRecords } from '@/actions/App/Http/Controllers/Admin/AttendanceMonitoringController';
import { index as adminAttendanceSites } from '@/actions/App/Http/Controllers/Admin/AttendanceSiteController';
import { index as adminClasses } from '@/actions/App/Http/Controllers/Admin/ClassRoomController';
import { index as adminDashboard } from '@/actions/App/Http/Controllers/Admin/DashboardController';
import { index as adminSchedules } from '@/actions/App/Http/Controllers/Admin/ScheduleController';
import { index as adminStudents } from '@/actions/App/Http/Controllers/Admin/StudentController';
import { index as adminSubjects } from '@/actions/App/Http/Controllers/Admin/SubjectController';
import { index as adminTeachers } from '@/actions/App/Http/Controllers/Admin/TeacherController';
import Collapsible from '@/components/ui/collapsible/Collapsible.vue';
import CollapsibleContent from '@/components/ui/collapsible/CollapsibleContent.vue';
import CollapsibleTrigger from '@/components/ui/collapsible/CollapsibleTrigger.vue';
import { useAppSidebar } from '@/composables/useAppSidebar';
import { logout as logoutRoute } from '@/routes';
import { edit as appearanceEdit } from '@/routes/appearance';

type SidebarLink = {
    title: string;
    href: string;
    icon: string;
    active: boolean;
};

type SidebarGroup = {
    id: string;
    label: string;
    items: SidebarLink[];
};

const page = usePage();
const isLg = useMediaQuery('(min-width: 1024px)');
const { isCollapsed, isMobileOpen, toggleCollapsed, closeMobile } = useAppSidebar();

const showFullNav = computed(() => {
    return !isLg.value || !isCollapsed.value;
});

const navGroups = computed((): SidebarGroup[] => {
    return [
        {
            id: 'umum',
            label: 'Umum',
            items: [
                {
                    title: 'Beranda',
                    href: adminDashboard().url,
                    icon: 'dashboard',
                    active: page.url.startsWith(adminDashboard().url),
                },
            ],
        },
        {
            id: 'akademik',
            label: 'Akademik',
            items: [
                {
                    title: 'Siswa',
                    href: adminStudents().url,
                    icon: 'school',
                    active: page.url.startsWith(adminStudents().url),
                },
                {
                    title: 'Guru',
                    href: adminTeachers().url,
                    icon: 'group',
                    active: page.url.startsWith(adminTeachers().url),
                },
                {
                    title: 'Kelas',
                    href: adminClasses().url,
                    icon: 'domain',
                    active: page.url.startsWith(adminClasses().url),
                },
                {
                    title: 'Mata pelajaran',
                    href: adminSubjects().url,
                    icon: 'book',
                    active: page.url.startsWith(adminSubjects().url),
                },
                {
                    title: 'Jadwal',
                    href: adminSchedules().url,
                    icon: 'calendar_month',
                    active: page.url.startsWith(adminSchedules().url),
                },
            ],
        },
        {
            id: 'absensi',
            label: 'Absensi',
            items: [
                {
                    title: 'Aturan & lokasi',
                    href: adminAttendanceSites().url,
                    icon: 'my_location',
                    active: page.url.startsWith(adminAttendanceSites().url),
                },
                {
                    title: 'Riwayat absensi',
                    href: adminAttendanceRecords().url,
                    icon: 'history',
                    active: page.url.startsWith('/admin/attendance-records'),
                },
                {
                    title: 'Override hari',
                    href: adminAttendanceDayOverrides().url,
                    icon: 'rule',
                    active: page.url.startsWith(adminAttendanceDayOverrides().url),
                },
            ],
        },
        {
            id: 'sekolah',
            label: 'Kegiatan sekolah',
            items: [
                {
                    title: 'Kalender akademik',
                    href: adminAcademicCalendarEvents().url,
                    icon: 'event',
                    active: page.url.startsWith(adminAcademicCalendarEvents().url),
                },
            ],
        },
        {
            id: 'sistem',
            label: 'Sistem',
            items: [
                {
                    title: 'Akun',
                    href: adminAccounts().url,
                    icon: 'manage_accounts',
                    active: page.url.startsWith(adminAccounts().url),
                },
                {
                    title: 'Pengaturan tampilan',
                    href: appearanceEdit().url,
                    icon: 'settings',
                    active: page.url.startsWith('/settings'),
                },
            ],
        },
    ];
});

const openByGroup = reactive<Record<string, boolean>>({});

watch(
    navGroups,
    (groups) => {
        for (const group of groups) {
            if (openByGroup[group.id] === undefined) {
                openByGroup[group.id] = group.items.some((item) => item.active);
            }
        }
    },
    { immediate: true, deep: true },
);

watch(
    () => page.url,
    () => {
        for (const group of navGroups.value) {
            if (group.items.some((item) => item.active)) {
                openByGroup[group.id] = true;
            }
        }
    },
);

function linkClasses(active: boolean, { nested = false }: { nested?: boolean } = {}): string[] {
    const base =
        'flex items-center gap-3 py-2.5 font-[\'Inter\'] text-sm font-medium tracking-wide transition-all duration-200 w-full text-left rounded-lg';
    const pad = nested ? 'pl-3 pr-3 ml-2' : 'px-3';

    if (active) {
        return [base, pad, 'bg-emerald-800/50 text-white shadow-sm'];
    }

    return [base, pad, 'text-emerald-100/80 hover:bg-emerald-900/55 hover:text-white'];
}

const logoutAction = logoutRoute();

const handleLogout = (): void => {
    router.post(logoutAction.url);
};
</script>

<template>
    <nav
        id="admin-sidebar-nav"
        aria-label="Menu admin"
        :class="[
            'fixed left-0 top-0 z-50 flex h-screen flex-col border-r border-emerald-900/50 bg-emerald-950 shadow-xl transition-[width,transform] duration-300 ease-out dark:bg-black',
            'w-[260px]',
            isMobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
            isCollapsed && isLg ? 'lg:w-[72px] lg:min-w-[72px]' : 'lg:w-[260px]',
        ]"
    >
        <button
            type="button"
            class="absolute right-2 top-4 flex h-9 w-9 items-center justify-center rounded-lg text-emerald-200/90 hover:bg-emerald-900/60 hover:text-white lg:hidden"
            aria-label="Tutup menu"
            @click="closeMobile"
        >
            <span class="material-symbols-outlined text-[22px]">close</span>
        </button>

        <!-- Brand -->
        <div
            :class="[
                'relative shrink-0 border-b border-emerald-900/45 pb-4 pt-5',
                showFullNav ? 'px-4' : 'px-2',
                isCollapsed && isLg ? 'flex flex-col items-center gap-3' : '',
            ]"
        >
            <div
                class="flex items-center gap-3"
                :class="isCollapsed && isLg ? 'flex-col' : 'pr-10 lg:pr-0'"
            >
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-500/20"
                >
                    <img alt="Robithotul Hikmah Logo" class="h-8 w-8 rounded-full object-cover" src="../../../public/logo_new.png" />
                </div>
                <div v-if="showFullNav" class="min-w-0 flex-1 lg:block">
                    <h1 class="truncate text-lg font-black tracking-tight text-white">
                        Robithotul Hikmah
                    </h1>
                    <p
                        class="font-[\'Inter\'] text-sm font-medium tracking-wide text-emerald-500 dark:text-emerald-400"
                    >
                        Admin
                    </p>
                </div>
            </div>

            <button
                v-if="isLg"
                type="button"
                :class="[
                    'mt-3 flex w-full items-center justify-center gap-2 rounded-lg border border-emerald-800/60 bg-emerald-900/30 px-2 py-2 text-emerald-100/90 transition-colors hover:bg-emerald-900/50 hover:text-white',
                    isCollapsed && isLg ? 'mt-0' : '',
                ]"
                :aria-expanded="!isCollapsed"
                :aria-label="isCollapsed ? 'Perluas menu samping' : 'Ciutkan menu samping'"
                @click="toggleCollapsed"
            >
                <span class="material-symbols-outlined text-[22px]">
                    {{ isCollapsed ? 'chevron_right' : 'chevron_left' }}
                </span>
                <span v-if="showFullNav" class="font-[\'Inter\'] text-xs font-semibold">Ciutkan menu</span>
            </button>
        </div>

        <!-- Nav -->
        <div
            :class="[
                'min-h-0 flex-1 overflow-y-auto overflow-x-hidden py-3',
                showFullNav ? 'px-2' : 'px-1.5',
            ]"
        >
            <template v-for="group in navGroups" :key="group.id">
                <div v-if="showFullNav && group.items.length === 1" class="mb-2">
                    <Link
                        :href="group.items[0].href"
                        :class="linkClasses(group.items[0].active, { nested: false })"
                        @click="closeMobile"
                    >
                        <span
                            class="material-symbols-outlined shrink-0 text-[22px]"
                            :style="
                                group.items[0].active ? 'font-variation-settings: \'FILL\' 1;' : ''
                            "
                        >
                            {{ group.items[0].icon }}
                        </span>
                        <span class="truncate">{{ group.items[0].title }}</span>
                    </Link>
                </div>
                <div v-else-if="showFullNav" class="mb-1">
                    <Collapsible v-model:open="openByGroup[group.id]">
                        <CollapsibleTrigger
                            class="flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2 text-left font-[\'Inter\'] text-xs font-bold uppercase tracking-wider text-emerald-200/75 outline-none ring-emerald-500/40 hover:bg-emerald-900/45 hover:text-emerald-50 focus-visible:ring-2 data-[state=open]:bg-emerald-900/40 data-[state=open]:text-emerald-50"
                        >
                            {{ group.label }}
                            <span
                                class="material-symbols-outlined shrink-0 text-lg transition-transform duration-200"
                                :class="openByGroup[group.id] ? 'rotate-180' : ''"
                            >
                                expand_more
                            </span>
                        </CollapsibleTrigger>
                        <CollapsibleContent class="overflow-hidden pb-1 pt-0.5">
                            <Link
                                v-for="item in group.items"
                                :key="item.title + item.href"
                                :href="item.href"
                                :class="linkClasses(item.active, { nested: true })"
                                @click="closeMobile"
                            >
                                <span
                                    class="material-symbols-outlined shrink-0 text-[20px]"
                                    :style="item.active ? 'font-variation-settings: \'FILL\' 1;' : ''"
                                >
                                    {{ item.icon }}
                                </span>
                                <span class="truncate">{{ item.title }}</span>
                            </Link>
                        </CollapsibleContent>
                    </Collapsible>
                </div>

                <!-- Desktop collapsed: icon rail per group -->
                <div
                    v-else
                    class="mb-2 flex flex-col items-center gap-1 border-b border-emerald-900/40 pb-2 last:border-b-0 last:mb-0"
                >
                    <Link
                        v-for="item in group.items"
                        :key="item.title + item.href"
                        :href="item.href"
                        :title="item.title"
                        :class="[
                            'flex h-10 w-10 items-center justify-center rounded-lg transition-colors',
                            item.active
                                ? 'bg-emerald-800/55 text-white'
                                : 'text-emerald-200/80 hover:bg-emerald-900/55 hover:text-white',
                        ]"
                    >
                        <span
                            class="material-symbols-outlined text-[22px]"
                            :style="item.active ? 'font-variation-settings: \'FILL\' 1;' : ''"
                        >
                            {{ item.icon }}
                        </span>
                    </Link>
                </div>
            </template>
        </div>

        <!-- Logout -->
        <div
            :class="[
                'shrink-0 border-t border-emerald-900/50 bg-emerald-950 py-4 dark:bg-black',
                showFullNav ? 'px-3' : 'px-2',
            ]"
        >
            <button
                type="button"
                :class="[
                    'flex items-center justify-center gap-2 rounded-lg bg-emerald-800/30 px-3 py-2.5 font-[\'Inter\'] text-sm font-medium tracking-wide text-white transition-colors hover:bg-emerald-800/50',
                    isCollapsed && isLg ? 'w-full px-0' : 'w-full',
                ]"
                :title="isCollapsed && isLg ? 'Keluar' : undefined"
                @click="handleLogout"
            >
                <span class="material-symbols-outlined text-[20px]">logout</span>
                <span v-if="showFullNav">Keluar</span>
            </button>
        </div>
    </nav>
</template>
