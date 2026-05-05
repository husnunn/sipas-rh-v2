<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { index, resetPassword, toggleActive } from '@/actions/App/Http/Controllers/Admin/AccountController';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { User } from '@/types/models';

defineOptions({
    layout: AppSidebarLayout,
});

const props = defineProps<{
    user: User;
}>();

const showPassword = ref(false);

const copyPassword = () => {
    if (props.user.plain_password) {
        navigator.clipboard.writeText(props.user.plain_password);
    }
};

const resetUserPassword = () => {
    if (!window.confirm(`Reset password untuk ${props.user.name}?`)) {
        return;
    }

    router.post(resetPassword(props.user).url);
};

const toggleUserStatus = () => {
    if (!window.confirm(`Ubah status akun ${props.user.name}?`)) {
        return;
    }

    router.patch(toggleActive(props.user).url);
};

const formatDate = (dateStr?: string | null): string => {
    if (!dateStr) {
        return '-';
    }

    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const roleConfig: Record<string, { icon: string; bg: string; text: string }> = {
    admin: { icon: 'shield_person', bg: 'bg-error-container', text: 'text-on-error-container' },
    teacher: { icon: 'school', bg: 'bg-tertiary-container', text: 'text-on-tertiary-container' },
    student: { icon: 'person', bg: 'bg-primary-container', text: 'text-on-primary-container' },
};
</script>

<template>
    <Head :title="`Detail Akun — ${user.name}`" />

    <div class="flex flex-col gap-stack-lg max-w-5xl mx-auto w-full">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center gap-4">
                <Link
                    :href="index().url"
                    class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-container hover:bg-surface-container-high transition-colors"
                >
                    <span class="material-symbols-outlined text-[20px] text-on-surface-variant">arrow_back</span>
                </Link>
                <div>
                    <h2 class="font-h2 text-h2 text-on-surface">Detail Akun</h2>
                    <p class="font-body-md text-body-md text-on-surface-variant mt-0.5">{{ user.name }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button
                    @click="resetUserPassword"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-outline-variant text-on-surface hover:bg-surface-container transition-colors font-label-sm text-label-sm"
                >
                    <span class="material-symbols-outlined text-[18px]">key</span>
                    Reset Password
                </button>
                <button
                    @click="toggleUserStatus"
                    :class="user.is_active
                        ? 'border-error/30 text-error hover:bg-error-container'
                        : 'border-secondary/30 text-secondary hover:bg-secondary-container'"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border transition-colors font-label-sm text-label-sm"
                >
                    <span class="material-symbols-outlined text-[18px]">{{ user.is_active ? 'block' : 'check_circle' }}</span>
                    {{ user.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                </button>
            </div>
        </div>

        <!-- Account Info Card -->
        <div class="bg-surface-container-lowest rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest overflow-hidden">
            <div class="px-6 py-4 border-b border-surface-container-highest bg-surface-container-low/40">
                <h3 class="font-label-lg text-label-lg text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px] text-primary">account_circle</span>
                    Informasi Akun
                </h3>
            </div>
            <div class="p-6">
                <div class="flex flex-col sm:flex-row gap-6">
                    <!-- Avatar -->
                    <div class="flex flex-col items-center gap-3">
                        <div
                            :class="user.roles?.includes('admin') ? 'bg-error-container text-on-error-container' : user.roles?.includes('teacher') ? 'bg-tertiary-container text-on-tertiary-container' : 'bg-primary-container text-on-primary-container'"
                            class="w-20 h-20 rounded-2xl flex items-center justify-center font-bold text-2xl uppercase shadow-sm"
                        >
                            {{ user.name.substring(0, 2) }}
                        </div>
                        <span
                            v-if="user.is_active"
                            class="inline-flex items-center gap-1 px-3 py-1 rounded-full font-label-sm text-label-sm bg-secondary-container text-on-secondary-container"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-secondary inline-block"></span>
                            Active
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1 px-3 py-1 rounded-full font-label-sm text-label-sm bg-error-container text-on-error-container"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-error inline-block"></span>
                            Inactive
                        </span>
                    </div>
                    <!-- Details Grid -->
                    <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                        <div>
                            <dt class="font-label-sm text-label-sm text-on-surface-variant mb-1">Nama Lengkap</dt>
                            <dd class="font-body-md text-body-md text-on-surface">{{ user.name }}</dd>
                        </div>
                        <div>
                            <dt class="font-label-sm text-label-sm text-on-surface-variant mb-1">Username</dt>
                            <dd class="font-body-md text-body-md text-on-surface font-mono">@{{ user.username }}</dd>
                        </div>
                        <div>
                            <dt class="font-label-sm text-label-sm text-on-surface-variant mb-1">Email</dt>
                            <dd class="font-body-md text-body-md text-on-surface">{{ user.email }}</dd>
                        </div>
                        <div>
                            <dt class="font-label-sm text-label-sm text-on-surface-variant mb-1">Password</dt>
                            <dd class="flex items-center gap-2">
                                <span class="font-body-md text-body-md text-on-surface font-mono">
                                    {{ user.plain_password ? (showPassword ? user.plain_password : '••••••••') : '-' }}
                                </span>
                                <template v-if="user.plain_password">
                                    <button
                                        @click="showPassword = !showPassword"
                                        class="w-7 h-7 flex items-center justify-center rounded-md hover:bg-surface-container-high transition-colors"
                                        :title="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                                    >
                                        <span class="material-symbols-outlined text-[16px] text-on-surface-variant">{{ showPassword ? 'visibility_off' : 'visibility' }}</span>
                                    </button>
                                    <button
                                        @click="copyPassword"
                                        class="w-7 h-7 flex items-center justify-center rounded-md hover:bg-surface-container-high transition-colors"
                                        title="Salin password"
                                    >
                                        <span class="material-symbols-outlined text-[16px] text-on-surface-variant">content_copy</span>
                                    </button>
                                </template>
                            </dd>
                        </div>
                        <div>
                            <dt class="font-label-sm text-label-sm text-on-surface-variant mb-1">Email Verified</dt>
                            <dd class="font-body-md text-body-md text-on-surface">{{ formatDate(user.email_verified_at) }}</dd>
                        </div>
                        <div>
                            <dt class="font-label-sm text-label-sm text-on-surface-variant mb-1">Login Terakhir</dt>
                            <dd class="font-body-md text-body-md text-on-surface">{{ formatDate(user.last_login_at) }}</dd>
                        </div>
                        <div>
                            <dt class="font-label-sm text-label-sm text-on-surface-variant mb-1">Akun Dibuat</dt>
                            <dd class="font-body-md text-body-md text-on-surface">{{ formatDate(user.created_at) }}</dd>
                        </div>
                        <div class="md:col-span-2">
                            <dt class="font-label-sm text-label-sm text-on-surface-variant mb-2">Roles</dt>
                            <dd class="flex items-center gap-2 flex-wrap">
                                <span
                                    v-for="role in user.roles"
                                    :key="role"
                                    :class="[roleConfig[role]?.bg, roleConfig[role]?.text]"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-label-sm text-label-sm capitalize"
                                >
                                    <span class="material-symbols-outlined text-[16px]" style="font-variation-settings: 'FILL' 1;">{{ roleConfig[role]?.icon }}</span>
                                    {{ role }}
                                </span>
                            </dd>
                        </div>
                        <div v-if="user.must_change_password" class="md:col-span-2">
                            <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-warning-container text-on-warning-container font-label-sm text-label-sm">
                                <span class="material-symbols-outlined text-[18px]">warning</span>
                                User harus mengganti password pada login berikutnya.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Teacher Profile -->
        <div v-if="user.teacher_profile" class="bg-surface-container-lowest rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest overflow-hidden">
            <div class="px-6 py-4 border-b border-surface-container-highest bg-surface-container-low/40">
                <h3 class="font-label-lg text-label-lg text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px] text-tertiary">school</span>
                    Profil Guru
                </h3>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                <div>
                    <dt class="font-label-sm text-label-sm text-on-surface-variant mb-1">Nama Lengkap</dt>
                    <dd class="font-body-md text-body-md text-on-surface">{{ user.teacher_profile.full_name }}</dd>
                </div>
                <div>
                    <dt class="font-label-sm text-label-sm text-on-surface-variant mb-1">NIP</dt>
                    <dd class="font-body-md text-body-md text-on-surface">{{ user.teacher_profile.nip || '-' }}</dd>
                </div>
                <div>
                    <dt class="font-label-sm text-label-sm text-on-surface-variant mb-1">Jenis Kelamin</dt>
                    <dd class="font-body-md text-body-md text-on-surface capitalize">{{ user.teacher_profile.gender || '-' }}</dd>
                </div>
                <div>
                    <dt class="font-label-sm text-label-sm text-on-surface-variant mb-1">No. Telepon</dt>
                    <dd class="font-body-md text-body-md text-on-surface">{{ user.teacher_profile.phone || '-' }}</dd>
                </div>
                <div class="md:col-span-2">
                    <dt class="font-label-sm text-label-sm text-on-surface-variant mb-1">Alamat</dt>
                    <dd class="font-body-md text-body-md text-on-surface">{{ user.teacher_profile.address || '-' }}</dd>
                </div>
                <div v-if="user.teacher_profile.subjects?.length" class="md:col-span-2">
                    <dt class="font-label-sm text-label-sm text-on-surface-variant mb-2">Mata Pelajaran</dt>
                    <dd class="flex items-center gap-2 flex-wrap">
                        <span
                            v-for="subject in user.teacher_profile.subjects"
                            :key="subject.id"
                            class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-tertiary-container/50 text-on-tertiary-container font-label-sm text-label-sm border border-tertiary-container"
                        >
                            <span class="material-symbols-outlined text-[14px]">book</span>
                            {{ subject.name }}
                        </span>
                    </dd>
                </div>
            </div>
        </div>

        <!-- Student Profile -->
        <div v-if="user.student_profile" class="bg-surface-container-lowest rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest overflow-hidden">
            <div class="px-6 py-4 border-b border-surface-container-highest bg-surface-container-low/40">
                <h3 class="font-label-lg text-label-lg text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px] text-primary">person</span>
                    Profil Siswa
                </h3>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                <div>
                    <dt class="font-label-sm text-label-sm text-on-surface-variant mb-1">Nama Lengkap</dt>
                    <dd class="font-body-md text-body-md text-on-surface">{{ user.student_profile.full_name }}</dd>
                </div>
                <div>
                    <dt class="font-label-sm text-label-sm text-on-surface-variant mb-1">NIS</dt>
                    <dd class="font-body-md text-body-md text-on-surface">{{ user.student_profile.nis }}</dd>
                </div>
                <div>
                    <dt class="font-label-sm text-label-sm text-on-surface-variant mb-1">NISN</dt>
                    <dd class="font-body-md text-body-md text-on-surface">{{ user.student_profile.nisn || '-' }}</dd>
                </div>
                <div>
                    <dt class="font-label-sm text-label-sm text-on-surface-variant mb-1">Jenis Kelamin</dt>
                    <dd class="font-body-md text-body-md text-on-surface capitalize">{{ user.student_profile.gender || '-' }}</dd>
                </div>
                <div>
                    <dt class="font-label-sm text-label-sm text-on-surface-variant mb-1">Tempat Lahir</dt>
                    <dd class="font-body-md text-body-md text-on-surface">{{ user.student_profile.birth_place || '-' }}</dd>
                </div>
                <div>
                    <dt class="font-label-sm text-label-sm text-on-surface-variant mb-1">Tanggal Lahir</dt>
                    <dd class="font-body-md text-body-md text-on-surface">{{ formatDate(user.student_profile.birth_date) }}</dd>
                </div>
                <div>
                    <dt class="font-label-sm text-label-sm text-on-surface-variant mb-1">No. Telepon</dt>
                    <dd class="font-body-md text-body-md text-on-surface">{{ user.student_profile.phone || '-' }}</dd>
                </div>
                <div>
                    <dt class="font-label-sm text-label-sm text-on-surface-variant mb-1">Nama Orang Tua</dt>
                    <dd class="font-body-md text-body-md text-on-surface">{{ user.student_profile.parent_name || '-' }}</dd>
                </div>
                <div>
                    <dt class="font-label-sm text-label-sm text-on-surface-variant mb-1">No. Telepon Orang Tua</dt>
                    <dd class="font-body-md text-body-md text-on-surface">{{ user.student_profile.parent_phone || '-' }}</dd>
                </div>
                <div class="md:col-span-2">
                    <dt class="font-label-sm text-label-sm text-on-surface-variant mb-1">Alamat</dt>
                    <dd class="font-body-md text-body-md text-on-surface">{{ user.student_profile.address || '-' }}</dd>
                </div>
                <div v-if="user.student_profile.classes?.length" class="md:col-span-2">
                    <dt class="font-label-sm text-label-sm text-on-surface-variant mb-2">Kelas</dt>
                    <dd class="flex items-center gap-2 flex-wrap">
                        <span
                            v-for="klass in user.student_profile.classes"
                            :key="klass.id"
                            class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-primary-container/50 text-on-primary-container font-label-sm text-label-sm border border-primary-container"
                        >
                            <span class="material-symbols-outlined text-[14px]">meeting_room</span>
                            {{ klass.name }}
                        </span>
                    </dd>
                </div>
            </div>
        </div>

        <!-- Password Reset Audit Log -->
        <div v-if="user.password_reset_audits?.length" class="bg-surface-container-lowest rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest overflow-hidden">
            <div class="px-6 py-4 border-b border-surface-container-highest bg-surface-container-low/40">
                <h3 class="font-label-lg text-label-lg text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px] text-outline">history</span>
                    Riwayat Reset Password
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[600px]">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-surface-container-highest">
                            <th class="font-table-header text-table-header text-on-surface-variant py-3 px-6 uppercase tracking-wider">Tanggal</th>
                            <th class="font-table-header text-table-header text-on-surface-variant py-3 px-6 uppercase tracking-wider">Oleh Admin</th>
                            <th class="font-table-header text-table-header text-on-surface-variant py-3 px-6 uppercase tracking-wider">Alasan</th>
                            <th class="font-table-header text-table-header text-on-surface-variant py-3 px-6 uppercase tracking-wider">IP Address</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container-highest">
                        <tr
                            v-for="(audit, idx) in user.password_reset_audits"
                            :key="audit.id"
                            :class="idx % 2 === 0 ? 'bg-surface-container-lowest' : 'bg-surface-container-low/40'"
                        >
                            <td class="font-body-md text-body-md text-on-surface py-3 px-6">{{ formatDate(audit.created_at) }}</td>
                            <td class="font-body-md text-body-md text-on-surface py-3 px-6">{{ audit.reset_by_admin?.name || '-' }}</td>
                            <td class="font-body-md text-body-md text-on-surface-variant py-3 px-6">{{ audit.reason || '-' }}</td>
                            <td class="font-body-md text-body-md text-on-surface-variant py-3 px-6 font-mono text-sm">{{ audit.ip_address || '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
