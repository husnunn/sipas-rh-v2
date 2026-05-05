<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

export type TablePaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

const props = defineProps<{
    from: number | null;
    to: number | null;
    total: number;
    links: TablePaginationLink[];
    /** Kata benda untuk ringkasan, contoh: "siswa", "akun", "entri" */
    itemLabel: string;
}>();

function stripLabel(raw: string): string {
    return raw
        .replace(/<[^>]*>/g, '')
        .replace(/&laquo;/gi, '«')
        .replace(/&raquo;/gi, '»')
        .replace(/&hellip;/gi, '…')
        .replace(/&nbsp;/gi, ' ')
        .replace(/\s+/g, ' ')
        .trim();
}

function isPrevLink(link: TablePaginationLink): boolean {
    if (link.label.toLowerCase().includes('previous')) {
        return true;
    }

    const s = stripLabel(link.label).toLowerCase();

    return s.includes('previous') || s.startsWith('«');
}

function isNextLink(link: TablePaginationLink): boolean {
    if (link.label.toLowerCase().includes('next')) {
        return true;
    }

    const s = stripLabel(link.label).toLowerCase();

    return s.includes('next') || s.endsWith('»');
}

function isEllipsisLink(link: TablePaginationLink): boolean {
    const s = stripLabel(link.label);

    return s === '...' || s === '…';
}

const prevLink = computed(() => props.links.find(isPrevLink));
const nextLink = computed(() => props.links.find(isNextLink));

const pageLinks = computed(() => props.links.filter((l) => !isPrevLink(l) && !isNextLink(l)));

const summaryText = computed(() => {
    const from = props.from ?? 0;
    const to = props.to ?? 0;

    return `Menampilkan ${from}–${to} dari ${props.total} ${props.itemLabel}`;
});

const btnBase =
    'inline-flex h-10 min-w-10 shrink-0 items-center justify-center rounded-lg border text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-surface-container-lowest';
const btnBorder = 'border-outline-variant';
const btnIdle = `${btnBase} ${btnBorder} text-on-surface-variant hover:bg-surface-container hover:text-on-surface`;
const btnActive = `${btnBase} border-primary bg-primary font-semibold text-on-primary shadow-sm`;
const btnDisabled = `${btnBase} ${btnBorder} cursor-not-allowed opacity-40`;
</script>

<template>
    <div
        class="flex flex-col gap-4 border-t border-surface-container-highest bg-surface-container-lowest px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6"
    >
        <p
            class="order-2 text-center font-body-md text-body-md text-on-surface-variant sm:order-1 sm:text-left"
        >
            {{ summaryText }}
        </p>

        <nav
            class="order-1 flex max-w-full flex-wrap items-center justify-center gap-2 sm:order-2 sm:justify-end"
            aria-label="Navigasi halaman"
        >
            <Link
                v-if="prevLink?.url"
                :href="prevLink.url"
                prefetch
                preserve-scroll
                preserve-state
                :class="btnIdle"
                aria-label="Halaman sebelumnya"
            >
                <span class="material-symbols-outlined text-[20px]">chevron_left</span>
            </Link>
            <span v-else :class="btnDisabled" aria-hidden="true">
                <span class="material-symbols-outlined text-[20px]">chevron_left</span>
            </span>

            <template v-for="(link, index) in pageLinks" :key="`page-${index}-${stripLabel(link.label)}`">
                <span
                    v-if="isEllipsisLink(link)"
                    class="flex h-10 min-w-10 items-center justify-center px-1 text-on-surface-variant"
                >
                    …
                </span>
                <Link
                    v-else-if="link.url"
                    :href="link.url"
                    prefetch
                    preserve-scroll
                    preserve-state
                    :class="link.active ? btnActive : btnIdle"
                >
                    {{ stripLabel(link.label) }}
                </Link>
                <span v-else :class="btnDisabled">
                    {{ stripLabel(link.label) }}
                </span>
            </template>

            <Link
                v-if="nextLink?.url"
                :href="nextLink.url"
                prefetch
                preserve-scroll
                preserve-state
                :class="btnIdle"
                aria-label="Halaman berikutnya"
            >
                <span class="material-symbols-outlined text-[20px]">chevron_right</span>
            </Link>
            <span v-else :class="btnDisabled" aria-hidden="true">
                <span class="material-symbols-outlined text-[20px]">chevron_right</span>
            </span>
        </nav>
    </div>
</template>
