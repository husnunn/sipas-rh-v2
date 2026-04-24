<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { create, destroy, edit, show } from '@/actions/App/Http/Controllers/Admin/ClassRoomController';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type {PaginatedData} from '@/types';
import type { ClassRoom } from '@/types/models';

defineOptions({
    layout: AppSidebarLayout,
});

defineProps<{
    classes: PaginatedData<ClassRoom>;
}>();

</script>

<template>
    <Head title="Manajemen Kelas" />

    <div class="flex flex-col gap-stack-lg max-w-7xl mx-auto w-full">
        <!-- Page Header & Global Action -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
            <div>
                <h2 class="font-h2 text-h2 text-on-surface">Manajemen Kelas</h2>
                <p class="font-body-md text-body-md text-on-surface-variant mt-1">Manage and view all registered classes within the academy.</p>
            </div>
            <Link
                :href="create().url"
                class="bg-primary hover:bg-primary-container text-on-primary font-label-sm text-label-sm px-5 py-2.5 rounded-lg flex items-center gap-2 shadow-[0_4px_6px_rgba(0,0,0,0.05)] transition-all">
                <span class="material-symbols-outlined text-[18px]">add</span>
                Add New Class
            </Link>
        </div>

        <!-- Enhanced Data Controls -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">
            <!-- Search Box -->
            <div class="lg:col-span-5 bg-surface-container-lowest p-2 rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest flex items-center relative">
                <span class="material-symbols-outlined absolute left-4 text-outline">search</span>
                <input class="w-full pl-10 pr-4 py-2 bg-transparent border-none focus:ring-0 font-body-md text-body-md text-on-surface placeholder:text-outline-variant outline-none" placeholder="Search class by name..." type="text" />
            </div>
            <!-- Filters -->
            <div class="lg:col-span-7 bg-surface-container-lowest p-2 rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest flex flex-wrap items-center gap-2">
                <div class="relative flex-1 min-w-[140px]">
                    <select class="w-full appearance-none bg-surface-bright border border-outline-variant rounded-lg font-body-md text-body-md px-4 py-2 pr-10 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                        <option>Status (All)</option>
                        <option>Active</option>
                        <option>Inactive</option>
                    </select>
                    <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline pointer-events-none">expand_more</span>
                </div>
            </div>
        </div>

        <!-- Data Table Container -->
        <div class="bg-surface-container-lowest rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest overflow-hidden flex flex-col">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[800px]">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-surface-container-highest">
                            <th class="font-table-header text-table-header text-on-surface-variant py-3 px-6 uppercase tracking-wider">Class Name</th>
                            <th class="font-table-header text-table-header text-on-surface-variant py-3 px-6 uppercase tracking-wider w-32">Level</th>
                            <th class="font-table-header text-table-header text-on-surface-variant py-3 px-6 uppercase tracking-wider w-64">Homeroom Teacher</th>
                            <th class="font-table-header text-table-header text-on-surface-variant py-3 px-6 uppercase tracking-wider w-32">Status</th>
                            <th class="font-table-header text-table-header text-on-surface-variant py-3 px-6 uppercase tracking-wider text-right w-32">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container-highest">
                        <tr v-for="(cls, index) in classes.data" :key="cls.id" :class="index % 2 === 0 ? 'bg-surface-container-lowest' : 'bg-surface-container-low/40'" class="hover:bg-surface-bright transition-colors group">
                            <td class="font-body-md text-body-md text-on-surface py-4 px-6 font-medium">{{ cls.name }}</td>
                            <td class="font-body-md text-body-md text-on-surface-variant py-4 px-6">{{ cls.level }}</td>
                            <td class="font-body-md text-body-md text-on-surface-variant py-4 px-6">
                                {{ cls.homeroom_teacher?.user?.name || '-' }}
                            </td>
                            <td class="py-4 px-6">
                                <span v-if="cls.is_active" class="inline-flex items-center px-2.5 py-1 rounded-full font-label-sm text-label-sm bg-secondary-container text-on-secondary-container">Active</span>
                                <span v-else class="inline-flex items-center px-2.5 py-1 rounded-full font-label-sm text-label-sm bg-error-container text-on-error-container">Inactive</span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="flex items-center justify-end gap-1 opacity-70 group-hover:opacity-100 transition-opacity">
                                    <Link :href="show(cls).url" class="p-1.5 text-outline hover:text-primary transition-colors rounded-md hover:bg-surface-container" title="View"><span class="material-symbols-outlined text-[20px]">visibility</span></Link>
                                    <Link :href="edit(cls).url" class="p-1.5 text-outline hover:text-tertiary-container transition-colors rounded-md hover:bg-surface-container" title="Edit"><span class="material-symbols-outlined text-[20px]">edit</span></Link>
                                    <Link :href="destroy(cls).url" method="delete" as="button" class="p-1.5 text-outline hover:text-error transition-colors rounded-md hover:bg-surface-container" title="Delete"><span class="material-symbols-outlined text-[20px]">delete</span></Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="classes.data.length === 0">
                            <td colspan="5" class="text-center py-8 text-on-surface-variant">No classes found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="bg-surface-container-lowest px-6 py-4 border-t border-surface-container-highest flex items-center justify-between">
                <span class="font-body-md text-body-md text-on-surface-variant">Showing <span class="font-medium text-on-surface">{{ classes.from || 0 }}</span> to <span class="font-medium text-on-surface">{{ classes.to || 0 }}</span> of <span class="font-medium text-on-surface">{{ classes.total }}</span> classes</span>
                <div class="flex items-center gap-1">
                    <Link
                        v-for="(link, i) in classes.links"
                        :key="i"
                        :href="link.url || '#'"
                        :class="[
                            'w-8 h-8 flex items-center justify-center rounded transition-colors',
                            link.active ? 'bg-primary text-on-primary font-medium' : 'hover:bg-surface-container text-on-surface font-body-md text-body-md',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                    >
                        <span
                            v-html="link.label.replace('Previous', '<span class=\'material-symbols-outlined text-[20px]\'>chevron_left</span>').replace('Next', '<span class=\'material-symbols-outlined text-[20px]\'>chevron_right</span>')"
                        />
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
