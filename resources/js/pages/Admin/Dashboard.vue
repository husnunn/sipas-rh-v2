<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { index as scheduleIndex } from '@/actions/App/Http/Controllers/Admin/ScheduleController';
import type { Schedule } from '@/types/models';

defineProps<{
    stats: {
        totalStudents: number;
        totalTeachers: number;
        totalClasses: number;
        totalSubjects: number;
    };
    todaySchedules: Schedule[];
    days: Record<string, string>;
}>();

const currentDate = computed(() => {
    return new Date().toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric'
    });
});
</script>

<template>
        <Head>
        <title>Dashboard Overview</title>

        <meta
            head-key="description"
            name="description"
            content="Dashboard sekolah untuk melihat data siswa, guru, kelas, mata pelajaran, dan jadwal aktif hari ini."
        />

        <meta
            head-key="og:title"
            property="og:title"
            content="Dashboard Overview"
        />

        <meta
            head-key="og:description"
            property="og:description"
            content="Dashboard sekolah untuk melihat data siswa, guru, kelas, mata pelajaran, dan jadwal aktif hari ini."
        />

        <meta
            head-key="og:image"
            property="og:image"
            content="https://smkrobithotulhikmah.my.id/logo_new.png"
        />

        <meta
            head-key="og:url"
            property="og:url"
            content="https://smkrobithotulhikmah.my.id"
        />

        <meta
            head-key="og:type"
            property="og:type"
            content="website"
        />
    </Head>


    <div class="flex justify-between items-end mb-8">
        <div>
            <h2 class="font-h2 text-h2 text-on-surface mb-1">Dashboard Overview</h2>
            <p class="font-body-md text-body-md text-on-surface-variant">Real-time metrics for current academic term.</p>
        </div>
        <div class="text-sm font-medium text-on-surface-variant bg-surface-container py-1.5 px-3 rounded-lg border border-outline-variant/30 flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">today</span>
            <span>{{ currentDate }}</span>
        </div>
    </div>

    <!-- Summary Cards (Bento style grid) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-gutter mb-8">
        <!-- Card 1 -->
        <div class="bg-surface-container-lowest rounded-xl p-5 border border-outline-variant/20 shadow-[0_4px_6px_rgba(0,0,0,0.05)] hover:shadow-[0_8px_12px_rgba(0,0,0,0.08)] transition-shadow group">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-secondary-container text-on-secondary-container rounded-lg group-hover:bg-primary-container group-hover:text-on-primary transition-colors">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">groups</span>
                </div>
                <span class="flex items-center text-primary-container text-sm font-semibold bg-primary-fixed py-0.5 px-2 rounded-full">+2.4%</span>
            </div>
            <p class="font-label-sm text-label-sm text-outline uppercase tracking-wider mb-1">Total Students</p>
            <h3 class="font-h1 text-h1 text-on-surface">{{ stats?.totalStudents ?? 0 }}</h3>
        </div>
        <!-- Card 2 -->
        <div class="bg-surface-container-lowest rounded-xl p-5 border border-outline-variant/20 shadow-[0_4px_6px_rgba(0,0,0,0.05)] hover:shadow-[0_8px_12px_rgba(0,0,0,0.08)] transition-shadow group">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-secondary-container text-on-secondary-container rounded-lg group-hover:bg-primary-container group-hover:text-on-primary transition-colors">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">badge</span>
                </div>
                <span class="flex items-center text-outline text-sm font-semibold bg-surface-container py-0.5 px-2 rounded-full">0.0%</span>
            </div>
            <p class="font-label-sm text-label-sm text-outline uppercase tracking-wider mb-1">Total Teachers</p>
            <h3 class="font-h1 text-h1 text-on-surface">{{ stats?.totalTeachers ?? 0 }}</h3>
        </div>
        <!-- Card 3 -->
        <div class="bg-surface-container-lowest rounded-xl p-5 border border-outline-variant/20 shadow-[0_4px_6px_rgba(0,0,0,0.05)] hover:shadow-[0_8px_12px_rgba(0,0,0,0.08)] transition-shadow group">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-secondary-container text-on-secondary-container rounded-lg group-hover:bg-primary-container group-hover:text-on-primary transition-colors">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">meeting_room</span>
                </div>
            </div>
            <p class="font-label-sm text-label-sm text-outline uppercase tracking-wider mb-1">Total Classes</p>
            <h3 class="font-h1 text-h1 text-on-surface">{{ stats?.totalClasses ?? 0 }}</h3>
        </div>
        <!-- Card 4 -->
        <div class="bg-surface-container-lowest rounded-xl p-5 border border-outline-variant/20 shadow-[0_4px_6px_rgba(0,0,0,0.05)] hover:shadow-[0_8px_12px_rgba(0,0,0,0.08)] transition-shadow group">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-secondary-container text-on-secondary-container rounded-lg group-hover:bg-primary-container group-hover:text-on-primary transition-colors">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">menu_book</span>
                </div>
            </div>
            <p class="font-label-sm text-label-sm text-outline uppercase tracking-wider mb-1">Active Subjects</p>
            <h3 class="font-h1 text-h1 text-on-surface">{{ stats?.totalSubjects ?? 0 }}</h3>
        </div>
    </div>

    <!-- Lower Grid Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-gutter">
        <!-- Active Schedule Table (Takes up 2 columns) -->
        <div class="lg:col-span-2 bg-surface-container-lowest rounded-xl border border-outline-variant/20 shadow-[0_4px_6px_rgba(0,0,0,0.05)] overflow-hidden flex flex-col">
            <div class="p-5 border-b border-outline-variant/20 flex justify-between items-center bg-surface-bright">
                <h3 class="font-h3 text-h3 text-on-surface flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-primary-container animate-pulse"></span>
                    Active Schedule Today
                </h3>
                <Link :href="scheduleIndex().url" class="text-sm font-semibold text-primary hover:text-primary-container transition-colors flex items-center gap-1">
                    View Full <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </Link>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low font-table-header text-table-header text-outline uppercase tracking-wider">
                            <th class="py-3 px-5 border-b border-outline-variant/20">Time</th>
                            <th class="py-3 px-5 border-b border-outline-variant/20">Subject</th>
                            <th class="py-3 px-5 border-b border-outline-variant/20">Class</th>
                            <th class="py-3 px-5 border-b border-outline-variant/20">Teacher</th>
                            <th class="py-3 px-5 border-b border-outline-variant/20">Status</th>
                        </tr>
                    </thead>
                    <tbody class="font-body-md text-body-md text-on-surface">
                        <tr v-for="item in todaySchedules" :key="item.id" class="border-b border-outline-variant/10 hover:bg-surface-container-low/50 transition-colors">
                            <td class="py-3 px-5 whitespace-nowrap">{{ item.start_time }} - {{ item.end_time }}</td>
                            <td class="py-3 px-5 font-semibold">{{ item.subject?.name }}</td>
                            <td class="py-3 px-5 text-on-surface-variant">{{ item.class_room?.name }}</td>
                            <td class="py-3 px-5">{{ item.teacher_profile?.user?.name }}</td>
                            <td class="py-3 px-5">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary-fixed text-on-primary-fixed">
                                    {{ days[item.day_of_week] ?? item.day_of_week }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="todaySchedules.length === 0">
                            <td class="py-3 px-5 text-on-surface-variant" colspan="5">Tidak ada jadwal aktif hari ini.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Right Column: Quick Actions & Chart -->
        <div class="flex flex-col gap-gutter">
            <!-- Quick Actions -->
            <div class="bg-surface-container-lowest rounded-xl p-5 border border-outline-variant/20 shadow-[0_4px_6px_rgba(0,0,0,0.05)]">
                <h3 class="font-h3 text-h3 text-on-surface mb-4">Quick Actions</h3>
                <div class="grid grid-cols-2 gap-3">
                    <button class="flex flex-col items-center justify-center gap-2 p-4 rounded-lg bg-surface-container-low hover:bg-surface-container-highest transition-colors border border-outline-variant/30 hover:border-primary/30 group">
                        <span class="material-symbols-outlined text-primary group-hover:scale-110 transition-transform">person_add</span>
                        <span class="font-label-sm text-label-sm text-on-surface text-center">Add Student</span>
                    </button>
                    <button class="flex flex-col items-center justify-center gap-2 p-4 rounded-lg bg-surface-container-low hover:bg-surface-container-highest transition-colors border border-outline-variant/30 hover:border-primary/30 group">
                        <span class="material-symbols-outlined text-primary group-hover:scale-110 transition-transform">event_available</span>
                        <span class="font-label-sm text-label-sm text-on-surface text-center">Update Schedule</span>
                    </button>
                    <button class="flex flex-col items-center justify-center gap-2 p-4 rounded-lg bg-surface-container-low hover:bg-surface-container-highest transition-colors border border-outline-variant/30 hover:border-primary/30 group">
                        <span class="material-symbols-outlined text-primary group-hover:scale-110 transition-transform">mail</span>
                        <span class="font-label-sm text-label-sm text-on-surface text-center">Send Notice</span>
                    </button>
                    <button class="flex flex-col items-center justify-center gap-2 p-4 rounded-lg bg-surface-container-low hover:bg-surface-container-highest transition-colors border border-outline-variant/30 hover:border-primary/30 group">
                        <span class="material-symbols-outlined text-primary group-hover:scale-110 transition-transform">receipt_long</span>
                        <span class="font-label-sm text-label-sm text-on-surface text-center">Generate Report</span>
                    </button>
                </div>
            </div>
            
            <!-- Attendance Chart Card (Stylized) -->
            <div class="bg-surface-container-lowest rounded-xl p-5 border border-outline-variant/20 shadow-[0_4px_6px_rgba(0,0,0,0.05)] flex-1 flex flex-col">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="font-h3 text-h3 text-on-surface">Weekly Attendance</h3>
                    <button class="p-1 rounded hover:bg-surface-container transition-colors text-outline">
                        <span class="material-symbols-outlined">more_vert</span>
                    </button>
                </div>
                <!-- CSS Bar Chart Representation -->
                <div class="mt-auto h-32 flex items-end justify-between gap-2 pt-4 border-b border-outline-variant/20 pb-2">
                    <div class="w-full flex flex-col items-center gap-1 group">
                        <div class="w-full bg-primary-container/20 rounded-t-sm h-[85%] relative group-hover:bg-primary-container/40 transition-colors">
                            <div class="absolute bottom-0 w-full bg-primary-container rounded-t-sm h-full shadow-[0_-2px_4px_rgba(0,0,0,0.1)]"></div>
                        </div>
                        <span class="font-label-sm text-[10px] text-outline">Mon</span>
                    </div>
                    <div class="w-full flex flex-col items-center gap-1 group">
                        <div class="w-full bg-primary-container/20 rounded-t-sm h-[92%] relative group-hover:bg-primary-container/40 transition-colors">
                            <div class="absolute bottom-0 w-full bg-primary-container rounded-t-sm h-full shadow-[0_-2px_4px_rgba(0,0,0,0.1)]"></div>
                        </div>
                        <span class="font-label-sm text-[10px] text-outline">Tue</span>
                    </div>
                    <div class="w-full flex flex-col items-center gap-1 group">
                        <div class="w-full bg-primary-container/20 rounded-t-sm h-[78%] relative group-hover:bg-primary-container/40 transition-colors">
                            <div class="absolute bottom-0 w-full bg-tertiary-container rounded-t-sm h-full shadow-[0_-2px_4px_rgba(0,0,0,0.1)]"></div>
                        </div>
                        <span class="font-label-sm text-[10px] text-outline">Wed</span>
                    </div>
                </div>
                <div class="mt-4 flex justify-between items-center text-sm">
                    <span class="text-on-surface-variant font-medium">Average Rate</span>
                    <span class="text-primary-container font-bold text-lg">94.2%</span>
                </div>
            </div>
        </div>
    </div>
</template>
