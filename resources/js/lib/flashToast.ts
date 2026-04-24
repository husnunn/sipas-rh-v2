import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { FlashToast } from '@/types/ui';

export function initializeFlashToast(): void {
    router.on('success', (event) => {
        const pageProps = event.detail.page.props;
        const toastData = pageProps.toast as FlashToast | undefined;
        const flashData = pageProps.flash as FlashToast | undefined;
        
        const data = toastData || flashData;

        if (!data) {
            return;
        }

        if (data.type === 'success') {
            toast.success(data.message);
        } else if (data.type === 'error') {
            toast.error(data.message);
        } else {
            toast(data.message);
        }
    });
}
