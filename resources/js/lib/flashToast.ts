import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { FlashToast } from '@/types/ui';

const defaultHttpErrorMessage =
    'Terjadi kesalahan pada server. Silakan coba lagi atau muat ulang halaman.';

const defaultNetworkErrorMessage =
    'Koneksi terputus atau server tidak merespons. Periksa jaringan Anda lalu coba lagi.';

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
        } else if (data.type === 'info') {
            toast.info(data.message);
        } else if (data.type === 'warning') {
            toast.warning(data.message);
        } else {
            toast(data.message);
        }
    });

    router.on('httpException', () => {
        toast.error(defaultHttpErrorMessage);
    });

    router.on('networkError', () => {
        toast.error(defaultNetworkErrorMessage);
    });
}
