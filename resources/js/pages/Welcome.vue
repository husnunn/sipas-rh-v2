<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { index as adminDashboard } from '@/actions/App/Http/Controllers/Admin/DashboardController';
import { login } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const page = usePage();

const roles = computed((): string[] => (page.props.auth?.user as { roles?: string[] } | undefined)?.roles ?? []);

const isAdmin = computed(() => roles.value.includes('admin'));
const isAuthenticated = computed(() => !!page.props.auth?.user);
</script>

<template>
    <Head title="SMK Robithotul Hikmah Jombang - Mencetak Generasi Qur'ani dan Berprestasi" />

    <div class="min-h-screen flex flex-col bg-[#f8f9fa] text-[#191c1d] font-sans">
        <!-- TopNavBar -->
        <nav
            class="bg-[#f8f9fa] sticky top-0 z-50 border-b border-[#bfc9c3] shadow-sm"
        >
            <div class="flex justify-between items-center w-full px-6 h-20 max-w-7xl mx-auto">
                <div class="text-xl font-bold text-[#003527]">
                    SMK Robithotul Hikmah
                </div>
                <div class="hidden md:flex gap-4 text-sm">
                    <a
                        class="text-[#003527] border-b-2 border-[#003527] pb-1 font-bold transition-transform duration-150"
                        href="#home"
                    >Home</a>
                    <a
                        class="text-[#404944] hover:text-[#003527] transition-colors duration-200"
                        href="#sambutan"
                    >About</a>
                    <a
                        class="text-[#404944] hover:text-[#003527] transition-colors duration-200"
                        href="#jurusan"
                    >Academic</a>
                    <a
                        class="text-[#404944] hover:text-[#003527] transition-colors duration-200"
                        href="#fasilitas"
                    >Facilities</a>
                    <a
                        class="text-[#404944] hover:text-[#003527] transition-colors duration-200"
                        href="#statistik"
                    >Contact</a>
                </div>
                <div class="flex items-center gap-3">
                    <template v-if="isAuthenticated">
                        <Link
                            v-if="isAdmin"
                            :href="adminDashboard().url"
                            class="bg-[#003527] text-white px-6 py-2 rounded text-xs font-semibold tracking-wider shadow-sm hover:shadow-md transition-shadow duration-200"
                        >
                            Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="bg-[#003527] text-white px-6 py-2 rounded text-xs font-semibold tracking-wider shadow-sm hover:shadow-md transition-shadow duration-200"
                        >
                            Login
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="flex-grow">
            <!-- Hero Section -->
            <section
                id="home"
                class="relative h-[720px] md:h-[819px] flex items-center justify-center bg-white overflow-hidden"
            >
                <img
                    alt="School Campus"
                    class="absolute inset-0 w-full h-full object-cover opacity-20"
                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuCxEd1zfDG1Y7OS7cpOY5Gk7P3J5JbcpG8mkk_WewxBVXIjB_3EduClhXihh9xtY9pPZZ170Bx2sC-9yxunFOGbFGzIFZBtBgW0hDHm7d1ZXK-Yk_pcdp4L3SfaFR4hjNCabiCxxifLUTfatxkSpj01Ad0W94rHF3TE9nL_s37XK_fZZPq6ET2M-MXQAp7ah9bsLqRrjpCEfxalkkLSPcrwy9OWKrRV5ErsXyEf50zImYZiDYj7RJm6GJ-PqJ6j7D-5kIzUXpL1YRs"
                />
                <div class="absolute inset-0 bg-gradient-to-r from-[#064e3b]/90 to-[#003527]/80"></div>
                <div class="relative z-10 text-center px-6 max-w-4xl mx-auto">
                    <h1 class="text-3xl md:text-4xl font-bold text-white mb-4 leading-tight tracking-tight">
                        Mencetak Generasi Qur'ani dan Berprestasi
                    </h1>
                    <p class="text-base md:text-lg text-[#a7cfc0] mb-6">
                        Bergabunglah dengan SMK Robithotul Hikmah Jombang untuk pendidikan yang menyeimbangkan
                        kecerdasan intelektual dan spiritual dalam lingkungan modern yang Islami.
                    </p>
                    <div class="flex gap-4 justify-center flex-wrap">
                        <a
                            href="#jurusan"
                            class="bg-white text-[#003527] px-6 py-2 rounded text-xs font-semibold tracking-wider shadow-sm hover:shadow-md transition-shadow duration-200"
                        >
                            Enroll Now
                        </a>
                        <a
                            href="#sambutan"
                            class="bg-transparent border border-white text-white px-6 py-2 rounded text-xs font-semibold tracking-wider hover:bg-white/10 transition-colors duration-200 flex items-center gap-2"
                        >
                            <span class="material-symbols-outlined text-lg">visibility</span>
                            Take a Virtual Tour
                        </a>
                    </div>
                </div>
            </section>

            <!-- Principal's Welcome -->
            <section id="sambutan" class="py-12 md:py-16 px-6 bg-[#f8f9fa]">
                <div class="max-w-7xl mx-auto">
                    <div class="flex flex-col md:flex-row items-center gap-8 md:gap-12">
                        <div class="w-full md:w-1/3">
                            <div class="aspect-square bg-[#edeeef] rounded-2xl overflow-hidden relative shadow-md">
                                <img
                                    alt="MUHAMMAD UBAID AL FARUQ, S.M."
                                    class="w-full h-full object-cover"
                                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuAaPPSktrjCoGkYf12FgAvCocfN7O1hJXhWUG2MloOskd0-7Er4Huth_Gv49Hobx3En7z4A9wsvH9Bfw_mHEXfwGrfMGvymXCgNZpa8t0Zg90dYTUJWi3LRiobEWyj7FMoDJCJklsFJHGlkitRc_ScNoj5tmsECG9VY6XLOneQFz_htfZg8ra4bJUvRszQYv_uBjzAYm3WdXrJvd4Ua-41gEMqkMtFSgZIHUtxegW32ttZoGy0kfHeTniNzB0t74An9Ixg0LJ3vuDY"
                                />
                            </div>
                        </div>
                        <div class="w-full md:w-2/3">
                            <h2 class="text-2xl font-semibold text-[#003527] mb-2 tracking-tight">Sambutan Kepala Sekolah</h2>
                            <h3 class="text-xl font-semibold text-[#406659] mb-4">MUHAMMAD UBAID AL FARUQ, S.M.</h3>
                            <div class="text-base text-[#404944] space-y-4">
                                <p>Assalamu'alaikum Warahmatullahi Wabarakatuh.</p>
                                <p>
                                    Selamat datang di website resmi SMK Robithotul Hikmah Jombang. Kami berkomitmen untuk
                                    menyelenggarakan pendidikan vokasi yang berkualitas, mengintegrasikan keterampilan
                                    teknologi modern dengan nilai-nilai akhlakul karimah.
                                </p>
                                <p>
                                    Melalui jurusan unggulan kami, Teknik Grafika, kami menyiapkan lulusan yang kompeten,
                                    kreatif, dan siap bersaing di dunia industri kreatif maupun melanjutkan ke jenjang
                                    pendidikan yang lebih tinggi.
                                </p>
                                <p>Wassalamu'alaikum Warahmatullahi Wabarakatuh.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Teknik Grafika Section -->
            <section id="jurusan" class="py-12 md:py-16 px-6 bg-[#f8f9fa]">
                <div class="max-w-7xl mx-auto">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-semibold text-[#003527] mb-2 tracking-tight">Jurusan Unggulan Kami</h2>
                        <p class="text-sm text-[#404944]">
                            Satu program keahlian yang difokuskan untuk kualitas maksimal.
                        </p>
                    </div>
                    <div
                        class="bg-white rounded-2xl shadow-[0_4px_12px_rgba(0,0,0,0.05)] border border-[#bfc9c3] overflow-hidden flex flex-col md:flex-row"
                    >
                        <div class="w-full md:w-1/2 h-64 md:h-auto relative bg-[#edeeef]">
                            <img
                                alt="Teknik Grafika"
                                class="absolute inset-0 w-full h-full object-cover"
                                src="https://lh3.googleusercontent.com/aida-public/AB6AXuB9k1Fb6VshQ84aeGHNOESIAyWgWcNLsHPzKk9lzDyfsy__HtUL8fN41zb6ir34YV_1lQXL88sSSZSSTr-2-rj3piJ6YzL8Yhi6beUlfrj2XPwh1M7XU3xueGw7EFzXOOzPEgFyypqQ_OZDMak_NQzwSOcnLZyc2fHxEwOmeONasStSBa7B3W9AXNh3Giik-ODcBPSyU65QhRFqjS7F8Pn7XMFqJmnuOB1t2rI_3IsVpSpyvTPqvjVdpGjNhDVZhZGybasFmx0vHaE"
                            />
                        </div>
                        <div class="w-full md:w-1/2 p-6 md:p-8 flex flex-col justify-center">
                            <div
                                class="inline-block bg-[#064e3b] text-[#80bea6] px-3 py-1 rounded-full text-xs font-semibold tracking-wider mb-4 w-max"
                            >
                                Program Keahlian
                            </div>
                            <h3 class="text-3xl font-bold text-[#003527] mb-4 tracking-tight">Teknik Grafika</h3>
                            <p class="text-base text-[#404944] mb-6">
                                Program keahlian Teknik Grafika membekali siswa dengan kompetensi komprehensif di bidang
                                industri percetakan modern, desain grafis, dan media digital. Kurikulum kami dirancang
                                menyesuaikan dengan perkembangan teknologi industri kreatif terkini.
                            </p>
                            <ul class="space-y-3 text-sm text-[#191c1d]">
                                <li class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-[#406659] text-xl">check_circle</span>
                                    Desain Komunikasi Visual &amp; Multimedia
                                </li>
                                <li class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-[#406659] text-xl">check_circle</span>
                                    Teknik Cetak Digital &amp; Offset Modern
                                </li>
                                <li class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-[#406659] text-xl">check_circle</span>
                                    Sablon Digital &amp; Merchandising
                                </li>
                                <li class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-[#406659] text-xl">check_circle</span>
                                    Kewirausahaan Industri Kreatif
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Keunggulan Kami -->
            <section id="fasilitas" class="py-12 md:py-16 px-6 bg-[#f8f9fa]">
                <div class="max-w-7xl mx-auto">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-semibold text-[#003527] mb-2 tracking-tight">Fasilitas &amp; Keunggulan Kami</h2>
                        <p class="text-sm text-[#404944]">
                            Infrastruktur pendukung pembelajaran praktek yang mumpuni.
                        </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div
                            class="bg-white p-6 rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-[#bfc9c3] flex flex-col items-center text-center hover:-translate-y-1 transition-transform duration-300"
                        >
                            <div
                                class="w-16 h-16 bg-[#064e3b] rounded-full flex items-center justify-center mb-4"
                            >
                                <span class="material-symbols-outlined text-[#80bea6] text-3xl">computer</span>
                            </div>
                            <h3 class="text-xl font-semibold text-[#191c1d] mb-2">Laboratorium Komputer</h3>
                            <p class="text-sm text-[#404944]">
                                Spesifikasi tinggi untuk kebutuhan desain grafis, rendering, dan pemrosesan multimedia tingkat lanjut.
                            </p>
                        </div>
                        <div
                            class="bg-white p-6 rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-[#bfc9c3] flex flex-col items-center text-center hover:-translate-y-1 transition-transform duration-300"
                        >
                            <div
                                class="w-16 h-16 bg-[#064e3b] rounded-full flex items-center justify-center mb-4"
                            >
                                <span class="material-symbols-outlined text-[#80bea6] text-3xl">design_services</span>
                            </div>
                            <h3 class="text-xl font-semibold text-[#191c1d] mb-2">Studio Desain Grafik</h3>
                            <p class="text-sm text-[#404944]">
                                Ruang kreatif dengan peralatan drawing tablet dan perangkat lunak standar industri desain profesional.
                            </p>
                        </div>
                        <div
                            class="bg-white p-6 rounded-xl shadow-[0_4px_6px_rgba(0,0,0,0.05)] border border-[#bfc9c3] flex flex-col items-center text-center hover:-translate-y-1 transition-transform duration-300"
                        >
                            <div
                                class="w-16 h-16 bg-[#064e3b] rounded-full flex items-center justify-center mb-4"
                            >
                                <span class="material-symbols-outlined text-[#80bea6] text-3xl">print</span>
                            </div>
                            <h3 class="text-xl font-semibold text-[#191c1d] mb-2">Bengkel Produksi</h3>
                            <p class="text-sm text-[#404944]">
                                Fasilitas mesin cetak modern, printer large format, dan mesin finishing untuk praktek langsung produksi grafika.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Statistik Sekolah -->
            <section id="statistik" class="py-12 md:py-16 px-6 bg-[#003527] text-white">
                <div class="max-w-7xl mx-auto">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                        <div>
                            <div class="text-3xl md:text-4xl font-bold text-[#b0f0d6] mb-2">140+</div>
                            <div class="text-sm opacity-90">Siswa Aktif</div>
                        </div>
                        <div>
                            <div class="text-3xl md:text-4xl font-bold text-[#b0f0d6] mb-2">15+</div>
                            <div class="text-sm opacity-90">Tenaga Pendidik</div>
                        </div>
                        <div>
                            <div class="text-3xl md:text-4xl font-bold text-[#b0f0d6] mb-2">11</div>
                            <div class="text-sm opacity-90">Mitra Industri</div>
                        </div>
                        <div>
                            <div class="text-3xl md:text-4xl font-bold text-[#b0f0d6] mb-2">120+</div>
                            <div class="text-sm opacity-90">Alumni Terserap</div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="bg-[#003527]">
            <div class="w-full py-8 md:py-12 px-6 grid grid-cols-1 md:grid-cols-3 gap-6 max-w-7xl mx-auto">
                <div>
                    <div class="text-2xl font-bold text-white mb-2 tracking-tight">
                        SMK Robithotul Hikmah
                    </div>
                    <p class="text-sm text-white/80">
                        © 2024 SMK Robithotul Hikmah Jombang. All rights reserved.
                    </p>
                </div>
                <div class="flex flex-col gap-2">
                    <a
                        class="text-xs font-semibold tracking-wider text-[#e1e3e4] opacity-80 hover:text-[#a7cfc0] hover:opacity-100 transition-all duration-200"
                        href="#"
                    >Privacy Policy</a>
                    <a
                        class="text-xs font-semibold tracking-wider text-[#e1e3e4] opacity-80 hover:text-[#a7cfc0] hover:opacity-100 transition-all duration-200"
                        href="#"
                    >Terms of Service</a>
                </div>
                <div class="flex flex-col gap-2">
                    <a
                        class="text-xs font-semibold tracking-wider text-[#e1e3e4] opacity-80 hover:text-[#a7cfc0] hover:opacity-100 transition-all duration-200"
                        href="#"
                    >Campus Map</a>
                    <a
                        class="text-xs font-semibold tracking-wider text-[#e1e3e4] opacity-80 hover:text-[#a7cfc0] hover:opacity-100 transition-all duration-200"
                        href="#"
                    >Alumni Portal</a>
                </div>
            </div>
        </footer>
    </div>
</template>
