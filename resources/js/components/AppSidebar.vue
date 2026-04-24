<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { index as adminAccounts } from '@/actions/App/Http/Controllers/Admin/AccountController';
import { index as adminClasses } from '@/actions/App/Http/Controllers/Admin/ClassRoomController';
import { index as adminDashboard } from '@/actions/App/Http/Controllers/Admin/DashboardController';
import { index as adminSchedules } from '@/actions/App/Http/Controllers/Admin/ScheduleController';
import { index as adminStudents } from '@/actions/App/Http/Controllers/Admin/StudentController';
import { index as adminSubjects } from '@/actions/App/Http/Controllers/Admin/SubjectController';
import { index as adminTeachers } from '@/actions/App/Http/Controllers/Admin/TeacherController';
import { logout as logoutRoute } from '@/routes';
import { edit as appearanceEdit } from '@/routes/appearance';

const page = usePage();

const navItems = computed(() => [
    {
        title: 'Dashboard',
        href: adminDashboard().url,
        icon: 'dashboard',
        active: page.url.startsWith(adminDashboard().url),
    },
    {
        title: 'Students',
        href: adminStudents().url,
        icon: 'school',
        active: page.url.startsWith(adminStudents().url),
    },
    {
        title: 'Teachers',
        href: adminTeachers().url,
        icon: 'group',
        active: page.url.startsWith(adminTeachers().url),
    },
    {
        title: 'Classes',
        href: adminClasses().url,
        icon: 'domain',
        active: page.url.startsWith(adminClasses().url),
    },
    {
        title: 'Subjects',
        href: adminSubjects().url,
        icon: 'book',
        active: page.url.startsWith(adminSubjects().url),
    },
    {
        title: 'Schedules',
        href: adminSchedules().url,
        icon: 'calendar_month',
        active: page.url.startsWith(adminSchedules().url),
    },
    {
        title: 'Accounts',
        href: adminAccounts().url,
        icon: 'manage_accounts',
        active: page.url.startsWith(adminAccounts().url),
    },
    {
        title: 'Settings',
        href: appearanceEdit().url,
        icon: 'settings',
        active: page.url.startsWith('/settings'),
    },
]);

const handleLogout = () => {
    router.post(logoutAction.url);
};

const logoutAction = logoutRoute();
</script>

<template>
    <nav class="bg-emerald-950 dark:bg-black fixed left-0 top-0 h-screen w-0 lg:w-[260px] z-50 border-r border-emerald-900/50 shadow-xl flex flex-col pt-6 transition-all duration-300 overflow-hidden lg:overflow-visible">
        
        <!-- Header -->
        <div class="px-6 mb-8 w-[260px]">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center shrink-0">
                    <img alt="Robithotul Hikmah Logo" class="w-8 h-8 rounded-full object-cover"
                        src="https://lh3.googleusercontent.com/aida-public/AB6AXuBUuIlGqGOdlrvgN6e36EOiXpKfApLFGHVRyhk5UWo10_7r1ZVdhiC1yFTwX_8PiRD2Afz4Es5QuIvzfwesQqNIJpHf8Wbhc3wKMRUbQmjn3n40EVXyR0yKDTlp-EXtS5og9bLGfH-jSrmEdPOXEhwU8c2-HzAtVDFMK3YpMvJ_L9CQRzekdv19iz14ZqT-tGz7l-dmvCWz0fbh7XpuptbKb0AXpueoYZx_nfiUgtJ4Sc7dyskMAViMlItbugLrJumWJ9xm_igjFrs" />
                </div>
                <div>
                    <h1 class="text-lg font-black text-white tracking-tight">Robithotul Hikmah</h1>
                    <p class="text-emerald-500 dark:text-emerald-400 font-['Inter'] text-sm font-medium tracking-wide">
                        Admin Portal</p>
                </div>
            </div>
        </div>
        
        <!-- Navigation Links -->
        <div class="flex-1 overflow-y-auto space-y-1 w-[260px]">
            <Link 
                v-for="item in navItems" 
                :key="item.title"
                :href="item.href"
                :class="[
                    'flex items-center gap-3 px-6 py-3 transition-all duration-200 font-[\'Inter\'] text-sm font-medium tracking-wide w-full text-left',
                    item.active 
                        ? 'bg-emerald-800/40 text-white border-l-4 border-emerald-500 opacity-90 scale-[0.99]'
                        : 'text-emerald-100/70 hover:bg-emerald-900/50 hover:text-white border-l-4 border-transparent'
                ]"
            >
                <span class="material-symbols-outlined" :style="item.active ? 'font-variation-settings: \'FILL\' 1;' : ''">
                    {{ item.icon }}
                </span>
                {{ item.title }}
            </Link>
        </div>

        <!-- Footer / CTA -->
        <div class="mt-auto px-6 pt-6 pb-6 border-t border-emerald-900/50 space-y-2 w-[260px] bg-emerald-950 dark:bg-black">
            <a class="flex items-center gap-3 text-emerald-100/70 hover:bg-emerald-900/50 hover:text-white px-6 py-3 -mx-6 transition-all duration-200 font-['Inter'] text-sm font-medium tracking-wide"
                href="#">
                <span class="material-symbols-outlined">help</span>
                Help Center
            </a>
            <button
                @click="handleLogout"
                class="w-full flex items-center justify-center gap-2 bg-emerald-800/30 hover:bg-emerald-800/50 text-white px-4 py-2 rounded-lg transition-colors font-['Inter'] text-sm font-medium tracking-wide">
                <span class="material-symbols-outlined text-sm">logout</span>
                System Logout
            </button>
        </div>
    </nav>
</template>
