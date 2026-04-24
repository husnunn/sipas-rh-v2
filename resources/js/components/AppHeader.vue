<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { index as adminAccounts } from '@/actions/App/Http/Controllers/Admin/AccountController';
import { index as adminDashboard } from '@/actions/App/Http/Controllers/Admin/DashboardController';
import { index as adminStudents } from '@/actions/App/Http/Controllers/Admin/StudentController';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
</script>

<template>
    <header class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-md sticky top-0 right-0 w-full z-40 border-b border-slate-200 dark:border-slate-800 shadow-sm flex justify-between items-center h-16 px-4 lg:px-8">
        <div class="flex items-center gap-6">
            <div class="text-emerald-900 dark:text-emerald-100 font-bold font-['Inter'] text-sm hidden sm:block">Academic Administration</div>
            <!-- Search -->
            <div class="relative w-64 hidden lg:block">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">search</span>
                <input
                    class="w-full bg-surface-container-low border border-outline-variant text-on-surface rounded-lg pl-9 pr-4 py-1.5 focus:ring-2 focus:ring-emerald-500 focus:outline-none font-['Inter'] text-sm transition-all focus:border-emerald-500 focus:bg-white placeholder:text-slate-500"
                    placeholder="Search system..." type="text" />
            </div>
        </div>

        <!-- Nav Links (Center) -->
        <div class="hidden md:flex items-center gap-1 h-full">
            <Link class="text-emerald-700 dark:text-emerald-400 border-b-2 border-emerald-600 pb-1 h-full flex items-center px-4 font-['Inter'] text-sm font-semibold"
                :href="adminDashboard().url">Academic</Link>
            <Link class="text-slate-500 dark:text-slate-400 hover:text-emerald-600 transition-colors h-full flex items-center px-4 font-['Inter'] text-sm font-semibold hover:bg-slate-50 dark:hover:bg-slate-800 rounded-md"
                :href="adminStudents().url">Personnel</Link>
            <Link class="text-slate-500 dark:text-slate-400 hover:text-emerald-600 transition-colors h-full flex items-center px-4 font-['Inter'] text-sm font-semibold hover:bg-slate-50 dark:hover:bg-slate-800 rounded-md"
                :href="adminAccounts().url">System</Link>
        </div>

        <!-- Trailing Actions -->
        <div class="flex items-center gap-4">
            <div class="hidden md:flex gap-2">
                <button class="bg-white border border-emerald-600 text-emerald-700 hover:bg-emerald-50 px-4 py-1.5 rounded-lg font-['Inter'] text-sm font-semibold transition-colors focus:ring-2 focus:ring-emerald-500 focus:outline-none">Export</button>
                <button class="bg-emerald-700 hover:bg-emerald-800 text-white px-4 py-1.5 rounded-lg font-['Inter'] text-sm font-semibold transition-colors focus:ring-2 focus:ring-emerald-500 focus:outline-none shadow-sm">Add New</button>
            </div>
            
            <div class="flex items-center gap-1 sm:border-l border-slate-200 sm:pl-4 sm:ml-2">
                <button class="p-2 text-slate-500 hover:text-emerald-600 hover:bg-slate-50 rounded-full transition-colors relative">
                    <span class="material-symbols-outlined">notifications</span>
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-error rounded-full"></span>
                </button>
                <button class="p-2 text-slate-500 hover:text-emerald-600 hover:bg-slate-50 rounded-full transition-colors hidden sm:block">
                    <span class="material-symbols-outlined">history</span>
                </button>
                
                <button class="p-1 rounded-full border-2 border-transparent hover:border-emerald-200 transition-all ml-1 sm:ml-2">
                    <img 
                        v-if="page.props.auth?.user?.avatar"
                        :src="page.props.auth.user.avatar"
                        class="w-8 h-8 rounded-full object-cover" 
                        alt="Profile" 
                    />
                    <!-- Fallback avatar matching reference design -->
                    <img
                        v-else
                        alt="Admin Profile" class="w-8 h-8 rounded-full object-cover"
                        src="https://lh3.googleusercontent.com/aida-public/AB6AXuDHxGrrhAq2Gw6f_Ikf4kQlhng49G1opD3t88IxvlZNxfn2bN2rUF7BtHXwnAFEWmvZjs8okHF5BJ36m13F7Eh1u1uxqsYCUvjCw-_hig_E6b58b5X50WydZ8bf-uaJyk0bmgkydLvAO8y_G3IIXwwWXetGpfMpZSmfUxbUGfAO-qyO0ysoVINySwmSdLotVh7lEHAaWP27vAxM-R5bvea_8sTV85QbGuNCZ6YaE3Bo8zfoNrqZe5vK77vVqsFnMs7t_koQbAnUbTM" 
                    />
                </button>
            </div>
        </div>
    </header>
</template>
