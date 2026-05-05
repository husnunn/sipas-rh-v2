import type { Page } from '@inertiajs/core';
import type { FlashToast } from '@/types/ui';

export function isFlashErrorPage(page: Page): boolean {
    const flash = page.props.flash as FlashToast | undefined;

    return flash?.type === 'error';
}

export function firstVisitErrorMessage(
    errors: Record<string, string | string[]>,
    fallback: string,
): string {
    for (const value of Object.values(errors)) {
        if (Array.isArray(value) && value.length > 0) {
            return value[0];
        }

        if (typeof value === 'string' && value.length > 0) {
            return value;
        }
    }

    return fallback;
}
