const EVENT_TYPE_LABELS: Record<string, string> = {
    national_holiday: 'Libur nasional',
    school_holiday: 'Libur sekolah',
    school_event: 'Kegiatan sekolah',
    exam: 'Ujian / assessment',
    special_date: 'Hari khusus',
};

const LONG_DATE = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

/** Parse `YYYY-MM-DD` sebagai tanggal lokal (hindari geser zona waktu). */
export function parseLocalDate(isoDate: string): Date {
    const [y, m, d] = isoDate.split('-').map((n) => Number.parseInt(n, 10));

    return new Date(y, (m ?? 1) - 1, d ?? 1);
}

export function formatIndonesianLongDate(isoDate: string): string {
    return LONG_DATE.format(parseLocalDate(isoDate));
}

/**
 * Rentang tanggal singkat dan natural untuk Indonesia.
 * Contoh: "15 Januari 2026" atau "15–20 Januari 2026" atau dua tanggal penuh jika beda bulan.
 */
export function formatAcademicDateRange(start: string, end: string): string {
    if (start === end) {
        return formatIndonesianLongDate(start);
    }

    const s = parseLocalDate(start);
    const e = parseLocalDate(end);

    if (
        s.getFullYear() === e.getFullYear() &&
        s.getMonth() === e.getMonth()
    ) {
        const monthYear = new Intl.DateTimeFormat('id-ID', {
            month: 'long',
            year: 'numeric',
        }).format(s);

        return `${s.getDate()}–${e.getDate()} ${monthYear}`;
    }

    return `${formatIndonesianLongDate(start)} – ${formatIndonesianLongDate(end)}`;
}

export function academicEventDurationDays(start: string, end: string): number {
    const s = parseLocalDate(start);
    const e = parseLocalDate(end);

    return Math.round((e.getTime() - s.getTime()) / 86400000) + 1;
}

export function academicEventDurationLabel(start: string, end: string): string {
    const n = academicEventDurationDays(start, end);

    return n === 1 ? '1 hari' : `${n} hari`;
}

export function academicEventTypeLabel(type: string): string {
    return EVENT_TYPE_LABELS[type] ?? type.replace(/_/g, ' ');
}

/** Kelas Tailwind untuk chip jenis event (token desain + sedikit warna semantik). */
export function academicEventTypeBadgeClass(type: string): string {
    const map: Record<string, string> = {
        national_holiday:
            'border border-outline-variant bg-error-container/35 text-on-error-container',
        school_holiday:
            'border border-outline-variant bg-tertiary-container/50 text-on-tertiary-container',
        school_event:
            'border border-outline-variant bg-primary-container/50 text-on-primary-container',
        exam: 'border border-outline-variant bg-secondary-container/60 text-on-secondary-container',
        special_date:
            'border border-outline-variant bg-surface-container-high text-on-surface-variant',
    };

    return (
        map[type] ??
        'border border-outline-variant bg-surface-container text-on-surface-variant'
    );
}
