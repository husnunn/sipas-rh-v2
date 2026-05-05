<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { edit, index } from '@/actions/App/Http/Controllers/Admin/AttendanceSiteController';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { AttendanceSite } from '@/types/models';
import 'leaflet/dist/leaflet.css';

defineOptions({ layout: AppSidebarLayout });

const props = defineProps<{
    site: AttendanceSite & { wifi_rules?: Array<{ id: number; ssid: string; bssid?: string | null; ip_subnet?: string | null; is_active: boolean }> };
}>();

const mapContainer = ref<HTMLDivElement | null>(null);
let map: import('leaflet').Map | null = null;

const initMap = async () => {
    if (!mapContainer.value || map) {
        return;
    }

    const lat = Number(props.site.latitude);
    const lng = Number(props.site.longitude);
    const radius = Math.max(1, Number(props.site.radius_m) || 100);

    const L = await import('leaflet');

    map = L.map(mapContainer.value, { zoomControl: true }).setView([lat, lng], 17);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 20,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    L.marker([lat, lng]).addTo(map);

    L.circle([lat, lng], {
        radius,
        color: '#ef4444',
        fillColor: '#ef4444',
        fillOpacity: 0.15,
        weight: 2,
    }).addTo(map);
};

onMounted(async () => {
    await nextTick();
    await initMap();
});

onBeforeUnmount(() => {
    if (map) {
        map.remove();
        map = null;
    }
});
</script>

<template>
    <Head title="Detail Titik Absensi" />
    <div class="flex flex-col gap-stack-lg max-w-3xl mx-auto w-full">
        <!-- Header -->
        <div>
            <Link :href="index().url" class="inline-flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary transition-colors mb-3">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                Kembali ke daftar
            </Link>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="font-h2 text-h2 text-on-surface">{{ site.name }}</h1>
                    <p class="font-body-md text-body-md text-on-surface-variant mt-1">Detail informasi titik absensi.</p>
                </div>
                <Link
                    :href="edit(site).url"
                    class="px-4 py-2 rounded-lg border border-outline-variant text-on-surface hover:bg-surface-container transition-colors flex items-center gap-1.5 text-sm font-medium"
                >
                    <span class="material-symbols-outlined text-[18px]">edit</span>
                    Edit
                </Link>
            </div>
        </div>

        <!-- Info Card -->
        <div class="bg-surface-container-lowest rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest p-6 space-y-3">
            <h2 class="font-medium text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px] text-primary">edit_location</span>
                Lokasi & Jangkauan
            </h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-on-surface-variant">Koordinat</p>
                    <p class="text-sm font-mono font-medium text-on-surface">{{ site.latitude }}, {{ site.longitude }}</p>
                </div>
                <div>
                    <p class="text-xs text-on-surface-variant">Radius area absensi</p>
                    <p class="text-sm font-medium text-on-surface">{{ site.radius_m }} meter</p>
                </div>
                <div>
                    <p class="text-xs text-on-surface-variant">Status</p>
                    <span v-if="site.is_active" class="inline-flex items-center px-2.5 py-1 rounded-full font-label-sm text-label-sm bg-secondary-container text-on-secondary-container">Aktif</span>
                    <span v-else class="inline-flex items-center px-2.5 py-1 rounded-full font-label-sm text-label-sm bg-error-container text-on-error-container">Nonaktif</span>
                </div>
                <div v-if="site.notes">
                    <p class="text-xs text-on-surface-variant">Catatan</p>
                    <p class="text-sm text-on-surface">{{ site.notes }}</p>
                </div>
            </div>
        </div>

        <!-- Map Preview Card -->
        <div class="bg-surface-container-lowest rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest p-6 space-y-3">
            <div>
                <h2 class="font-medium text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px] text-primary">map</span>
                    Peta Lokasi Absensi
                </h2>
                <p class="text-xs text-on-surface-variant mt-1 flex items-center gap-1.5">
                    <span class="inline-block w-3 h-3 rounded-full bg-red-500/30 border border-red-500 shrink-0"></span>
                    Lingkaran merah menunjukkan jangkauan area absensi ({{ site.radius_m }} meter dari titik tengah).
                </p>
            </div>
            <div ref="mapContainer" class="h-72 w-full rounded-lg border border-outline-variant overflow-hidden" />
        </div>

        <!-- Policy Card -->
        <div class="bg-surface-container-lowest rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest p-6 space-y-3">
            <h2 class="font-medium text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px] text-primary">schedule</span>
                Kebijakan Jam Absensi
            </h2>
            <p class="text-xs text-on-surface-variant">Kolom kosong berarti memakai default konfigurasi sekolah.</p>
            <div class="grid grid-cols-3 gap-4 text-sm">
                <div>
                    <p class="text-xs text-on-surface-variant">Check-in dibuka</p>
                    <p class="font-medium text-on-surface">{{ site.check_in_open_at || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-on-surface-variant">Batas hadir tepat waktu</p>
                    <p class="font-medium text-on-surface">{{ site.check_in_on_time_until || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-on-surface-variant">Check-in ditutup</p>
                    <p class="font-medium text-on-surface">{{ site.check_in_close_at || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-on-surface-variant">Check-out dibuka</p>
                    <p class="font-medium text-on-surface">{{ site.check_out_open_at || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-on-surface-variant">Check-out ditutup</p>
                    <p class="font-medium text-on-surface">{{ site.check_out_close_at || '—' }}</p>
                </div>
            </div>
        </div>

        <!-- WiFi Card -->
        <div class="bg-surface-container-lowest rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest p-6 space-y-3">
            <h2 class="font-medium text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px] text-primary">wifi</span>
                Daftar Wi-Fi yang Diizinkan
            </h2>
            <p v-if="!site.wifi_rules?.length" class="text-sm text-on-surface-variant text-center py-4">Tidak ada aturan Wi-Fi terdaftar.</p>
            <div v-else class="space-y-2">
                <div
                    v-for="rule in site.wifi_rules"
                    :key="rule.id"
                    class="rounded-lg border border-surface-container-highest px-4 py-3 flex items-center gap-4 hover:bg-surface-container-low/40 transition-colors"
                >
                    <div class="w-8 h-8 rounded-full bg-primary-container/40 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[16px] text-primary">wifi</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-sm text-on-surface truncate">{{ rule.ssid }}</p>
                        <p class="text-xs text-on-surface-variant">
                            {{ rule.bssid || '—' }}
                            <span v-if="rule.ip_subnet"> • {{ rule.ip_subnet }}</span>
                        </p>
                    </div>
                    <span
                        class="text-xs px-2.5 py-1 rounded-full font-medium shrink-0"
                        :class="rule.is_active ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container text-on-surface-variant'"
                    >
                        {{ rule.is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
