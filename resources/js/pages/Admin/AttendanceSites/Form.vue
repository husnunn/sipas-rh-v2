<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { index, store, update } from '@/actions/App/Http/Controllers/Admin/AttendanceSiteController';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { AttendanceSite } from '@/types/models';
import 'leaflet/dist/leaflet.css';

defineOptions({ layout: AppSidebarLayout });

const props = defineProps<{
    mode: 'create' | 'edit';
    site: (AttendanceSite & { wifi_rules?: Array<{ ssid: string; bssid?: string | null; ip_subnet?: string | null; is_active?: boolean }> }) | null;
}>();

const form = useForm({
    name: props.site?.name ?? '',
    latitude: props.site?.latitude ?? '',
    longitude: props.site?.longitude ?? '',
    radius_m: props.site?.radius_m ?? 100,
    check_in_open_at: props.site?.check_in_open_at ?? '',
    check_in_on_time_until: props.site?.check_in_on_time_until ?? '',
    check_in_close_at: props.site?.check_in_close_at ?? '',
    check_out_open_at: props.site?.check_out_open_at ?? '',
    check_out_close_at: props.site?.check_out_close_at ?? '',
    is_active: props.site?.is_active ?? true,
    notes: props.site?.notes ?? '',
    wifi_rules: props.site?.wifi_rules?.map((rule) => ({
        ssid: rule.ssid ?? '',
        bssid: rule.bssid ?? '',
        ip_subnet: rule.ip_subnet ?? '',
        is_active: rule.is_active ?? true,
    })) ?? [{ ssid: '', bssid: '', ip_subnet: '', is_active: true }],
});

const addWifiRule = () => {
    form.wifi_rules.push({ ssid: '', bssid: '', ip_subnet: '', is_active: true });
};

const removeWifiRule = (idx: number) => {
    form.wifi_rules.splice(idx, 1);
};

const mapContainer = ref<HTMLDivElement | null>(null);
const mapReady = ref(false);

let leafletModule: typeof import('leaflet') | null = null;
let map: import('leaflet').Map | null = null;
let marker: import('leaflet').Marker | null = null;
let circle: import('leaflet').Circle | null = null;

const defaultLat = -6.2;
const defaultLng = 106.816666;

const getCurrentLatLng = (): [number, number] => {
    const lat = Number(form.latitude || defaultLat);
    const lng = Number(form.longitude || defaultLng);

    return [lat, lng];
};

const setFormCoordinates = (lat: number, lng: number) => {
    form.latitude = Number(lat.toFixed(7));
    form.longitude = Number(lng.toFixed(7));
};

const updateCircle = (lat: number, lng: number, radiusM: number) => {
    if (!leafletModule || !map) {
        return;
    }

    const validRadius = Math.max(1, Number(radiusM) || 100);

    if (!circle) {
        circle = leafletModule
            .circle([lat, lng], {
                radius: validRadius,
                color: '#ef4444',
                fillColor: '#ef4444',
                fillOpacity: 0.15,
                weight: 2,
            })
            .addTo(map);
    } else {
        circle.setLatLng([lat, lng]);
        circle.setRadius(validRadius);
    }
};

const updateMarker = (lat: number, lng: number) => {
    if (!leafletModule || !map) {
        return;
    }

    if (!marker) {
        marker = leafletModule.marker([lat, lng], { draggable: true }).addTo(map);
        marker.on('dragend', () => {
            const pos = marker?.getLatLng();

            if (pos) {
                setFormCoordinates(pos.lat, pos.lng);
            }
        });
    } else {
        marker.setLatLng([lat, lng]);
    }
};

const initMap = async () => {
    if (!mapContainer.value || map) {
        return;
    }

    leafletModule = await import('leaflet');
    const [lat, lng] = getCurrentLatLng();

    map = leafletModule.map(mapContainer.value).setView([lat, lng], 18);

    leafletModule
        .tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 20,
            attribution: '&copy; OpenStreetMap contributors',
        })
        .addTo(map);

    updateMarker(lat, lng);
    updateCircle(lat, lng, Number(form.radius_m));

    map.on('click', (event: import('leaflet').LeafletMouseEvent) => {
        const { lat: clickLat, lng: clickLng } = event.latlng;
        setFormCoordinates(clickLat, clickLng);
        updateMarker(clickLat, clickLng);
        updateCircle(clickLat, clickLng, Number(form.radius_m));
    });

    mapReady.value = true;
};

const useCurrentBrowserLocation = () => {
    if (!navigator.geolocation) {
        return;
    }

    navigator.geolocation.getCurrentPosition((position) => {
        setFormCoordinates(position.coords.latitude, position.coords.longitude);
    });
};

// Watch latitude/longitude changes (from manual input or geolocation)
watch(
    () => [form.latitude, form.longitude],
    () => {
        if (!map || !mapReady.value) {
            return;
        }

        const [lat, lng] = getCurrentLatLng();
        updateMarker(lat, lng);
        updateCircle(lat, lng, Number(form.radius_m));
        map.setView([lat, lng], map.getZoom(), { animate: true });
    },
);

// Watch radius changes for real-time circle update
watch(
    () => form.radius_m,
    (newRadius) => {
        if (!map || !mapReady.value) {
            return;
        }

        const [lat, lng] = getCurrentLatLng();
        updateCircle(lat, lng, Number(newRadius));
    },
);

onMounted(async () => {
    await nextTick();
    await initMap();
});

onBeforeUnmount(() => {
    if (map) {
        map.remove();
        map = null;
        marker = null;
        circle = null;
    }
});

const submit = () => {
    // Ensure radius_m is an integer for backend validation
    form.transform((data) => ({
        ...data,
        radius_m: parseInt(String(data.radius_m), 10) || 100,
        latitude: Number(data.latitude),
        longitude: Number(data.longitude),
    }));

    if (props.mode === 'create') {
        form.post(store().url);

        return;
    }

    if (props.site) {
        form.put(update(props.site).url);
    }
};
</script>

<template>
    <Head :title="mode === 'create' ? 'Tambah Titik Absensi' : 'Edit Titik Absensi'" />
    <div class="flex flex-col gap-stack-lg max-w-3xl mx-auto w-full">
        <!-- Header -->
        <div>
            <Link :href="index().url" class="inline-flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary transition-colors mb-3">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                Kembali ke daftar
            </Link>
            <h1 class="font-h2 text-h2 text-on-surface">{{ mode === 'create' ? 'Tambah Titik Absensi' : 'Edit Titik Absensi' }}</h1>
            <p class="font-body-md text-body-md text-on-surface-variant mt-1">
                {{ mode === 'create' ? 'Tentukan lokasi dan konfigurasi titik absensi baru.' : 'Perbarui informasi titik absensi yang sudah ada.' }}
            </p>
        </div>

        <form class="flex flex-col gap-stack-lg" @submit.prevent="submit">
            <!-- Basic Info Card -->
            <div class="bg-surface-container-lowest rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest p-6 space-y-4">
                <h2 class="font-medium text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px] text-primary">edit_location</span>
                    Informasi Dasar
                </h2>
                <div class="space-y-1">
                    <label class="text-sm font-medium text-on-surface">Nama titik absensi <span class="text-error">*</span></label>
                    <input
                        v-model="form.name"
                        type="text"
                        placeholder="Contoh: Gerbang Utama Sekolah"
                        class="w-full bg-surface-bright border border-outline-variant rounded-lg px-4 py-2.5 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all placeholder:text-outline-variant"
                    />
                    <p v-if="form.errors.name" class="text-sm text-error mt-1">{{ form.errors.name }}</p>
                </div>
            </div>

            <!-- Map Preview Card -->
            <div class="bg-surface-container-lowest rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-medium text-on-surface flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px] text-primary">map</span>
                            Lokasi di Peta
                        </h2>
                        <p class="text-xs text-on-surface-variant mt-1 flex items-center gap-1.5">
                            <span class="inline-block w-3 h-3 rounded-full bg-red-500/30 border border-red-500 shrink-0"></span>
                            Lingkaran merah menunjukkan jangkauan area absensi. Klik peta atau drag marker untuk memindahkan titik.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="px-3 py-1.5 text-sm rounded-lg border border-outline-variant text-on-surface hover:bg-surface-container transition-colors flex items-center gap-1.5 shrink-0"
                        @click="useCurrentBrowserLocation"
                    >
                        <span class="material-symbols-outlined text-[16px]">my_location</span>
                        Pakai lokasi browser
                    </button>
                </div>
                <div ref="mapContainer" class="h-80 w-full rounded-lg border border-outline-variant overflow-hidden" />

                <div class="grid grid-cols-3 gap-3">
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-on-surface">Latitude <span class="text-error">*</span></label>
                        <input
                            v-model="form.latitude"
                            type="number"
                            step="0.0000001"
                            placeholder="-6.2000000"
                            class="w-full bg-surface-bright border border-outline-variant rounded-lg px-4 py-2.5 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all placeholder:text-outline-variant font-mono text-sm"
                        />
                        <p v-if="form.errors.latitude" class="text-xs text-error">{{ form.errors.latitude }}</p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-on-surface">Longitude <span class="text-error">*</span></label>
                        <input
                            v-model="form.longitude"
                            type="number"
                            step="0.0000001"
                            placeholder="106.8166660"
                            class="w-full bg-surface-bright border border-outline-variant rounded-lg px-4 py-2.5 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all placeholder:text-outline-variant font-mono text-sm"
                        />
                        <p v-if="form.errors.longitude" class="text-xs text-error">{{ form.errors.longitude }}</p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-on-surface">Radius (meter) <span class="text-error">*</span></label>
                        <input
                            v-model="form.radius_m"
                            type="number"
                            min="1"
                            placeholder="100"
                            class="w-full bg-surface-bright border border-outline-variant rounded-lg px-4 py-2.5 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all placeholder:text-outline-variant"
                        />
                        <p class="text-xs text-on-surface-variant">Lingkaran merah di peta akan update otomatis.</p>
                        <p v-if="form.errors.radius_m" class="text-xs text-error">{{ form.errors.radius_m }}</p>
                    </div>
                </div>
            </div>

            <!-- Policy Card -->
            <div class="bg-surface-container-lowest rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest p-6 space-y-4">
                <div>
                    <h2 class="font-medium text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px] text-primary">schedule</span>
                        Kebijakan Jam Absensi
                    </h2>
                    <p class="text-xs text-on-surface-variant mt-1">Jika dikosongkan, sistem memakai default dari konfigurasi sekolah.</p>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div class="space-y-1">
                        <label class="text-xs font-medium text-on-surface">Check-in dibuka</label>
                        <input v-model="form.check_in_open_at" type="time" class="w-full bg-surface-bright border border-outline-variant rounded-lg px-4 py-2.5 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all" />
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-medium text-on-surface">Batas hadir tepat waktu</label>
                        <input v-model="form.check_in_on_time_until" type="time" class="w-full bg-surface-bright border border-outline-variant rounded-lg px-4 py-2.5 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all" />
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-medium text-on-surface">Check-in ditutup</label>
                        <input v-model="form.check_in_close_at" type="time" class="w-full bg-surface-bright border border-outline-variant rounded-lg px-4 py-2.5 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="text-xs font-medium text-on-surface">Check-out dibuka</label>
                        <input v-model="form.check_out_open_at" type="time" class="w-full bg-surface-bright border border-outline-variant rounded-lg px-4 py-2.5 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all" />
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-medium text-on-surface">Check-out ditutup</label>
                        <input v-model="form.check_out_close_at" type="time" class="w-full bg-surface-bright border border-outline-variant rounded-lg px-4 py-2.5 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all" />
                    </div>
                </div>
            </div>

            <!-- Notes + Status Card -->
            <div class="bg-surface-container-lowest rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest p-6 space-y-4">
                <h2 class="font-medium text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px] text-primary">description</span>
                    Informasi Tambahan
                </h2>
                <div class="space-y-1">
                    <label class="text-sm font-medium text-on-surface">Catatan</label>
                    <textarea
                        v-model="form.notes"
                        placeholder="Tambahan informasi titik absensi (opsional)"
                        rows="3"
                        class="w-full bg-surface-bright border border-outline-variant rounded-lg px-4 py-2.5 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all placeholder:text-outline-variant resize-none"
                    />
                </div>
                <label class="inline-flex items-center gap-2.5 cursor-pointer select-none">
                    <input v-model="form.is_active" type="checkbox" class="rounded border-outline-variant text-primary focus:ring-primary w-4 h-4" />
                    <span class="text-sm font-medium text-on-surface">Titik absensi aktif</span>
                </label>
            </div>

            <!-- WiFi Rules Card -->
            <div class="bg-surface-container-lowest rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-surface-container-highest p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="font-medium text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px] text-primary">wifi</span>
                        Wi-Fi yang Diizinkan
                    </h2>
                    <button
                        type="button"
                        class="px-3 py-1.5 text-sm rounded-lg border border-outline-variant text-on-surface hover:bg-surface-container transition-colors flex items-center gap-1.5"
                        @click="addWifiRule"
                    >
                        <span class="material-symbols-outlined text-[16px]">add</span>
                        Tambah Rule
                    </button>
                </div>
                <div v-for="(rule, idx) in form.wifi_rules" :key="idx" class="grid grid-cols-[1fr_1fr_1fr_auto] gap-3 items-end">
                    <div class="space-y-1">
                        <label class="text-xs font-medium text-on-surface">SSID</label>
                        <input v-model="rule.ssid" type="text" placeholder="Nama Wi-Fi" class="w-full bg-surface-bright border border-outline-variant rounded-lg px-3 py-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all placeholder:text-outline-variant text-sm" />
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-medium text-on-surface">BSSID</label>
                        <input v-model="rule.bssid" type="text" placeholder="AA:BB:CC:11:22:33" class="w-full bg-surface-bright border border-outline-variant rounded-lg px-3 py-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all placeholder:text-outline-variant font-mono text-sm" />
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-medium text-on-surface">Subnet (CIDR)</label>
                        <input v-model="rule.ip_subnet" type="text" placeholder="192.168.1.0/24" class="w-full bg-surface-bright border border-outline-variant rounded-lg px-3 py-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all placeholder:text-outline-variant font-mono text-sm" />
                    </div>
                    <button
                        type="button"
                        class="p-2 text-outline hover:text-error transition-colors rounded-lg hover:bg-surface-container"
                        title="Hapus rule"
                        @click="removeWifiRule(idx)"
                    >
                        <span class="material-symbols-outlined text-[20px]">delete</span>
                    </button>
                </div>
                <p v-if="form.wifi_rules.length === 0" class="text-sm text-on-surface-variant text-center py-4">Belum ada aturan Wi-Fi.</p>
            </div>

            <!-- Submit -->
            <div class="flex items-center gap-3">
                <button
                    :disabled="form.processing"
                    type="submit"
                    class="bg-primary hover:bg-primary-container text-on-primary font-label-sm text-label-sm px-6 py-2.5 rounded-lg flex items-center gap-2 shadow-[0_4px_6px_rgba(0,0,0,0.05)] transition-all disabled:opacity-50"
                >
                    <span v-if="form.processing" class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>
                    <span v-else class="material-symbols-outlined text-[18px]">save</span>
                    {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                </button>
                <Link :href="index().url" class="px-6 py-2.5 rounded-lg text-sm font-medium text-on-surface-variant bg-surface-container hover:bg-surface-container-high transition-colors">
                    Batal
                </Link>
            </div>
        </form>
    </div>
</template>
