import { onMounted, ref, watch } from 'vue';

const STORAGE_KEY = 'sipas-admin-sidebar-collapsed';

const isCollapsed = ref(false);
const isMobileOpen = ref(false);

let hydrated = false;

function hydrateFromStorage(): void {
    if (hydrated || typeof localStorage === 'undefined') {
        return;
    }

    hydrated = true;
    isCollapsed.value = localStorage.getItem(STORAGE_KEY) === '1';
}

watch(isCollapsed, (value) => {
    if (typeof localStorage !== 'undefined') {
        localStorage.setItem(STORAGE_KEY, value ? '1' : '0');
    }
});

export function useAppSidebar(): {
    isCollapsed: typeof isCollapsed;
    isMobileOpen: typeof isMobileOpen;
    toggleCollapsed: () => void;
    openMobile: () => void;
    closeMobile: () => void;
    toggleMobile: () => void;
} {
    onMounted(() => {
        hydrateFromStorage();
    });

    const toggleCollapsed = (): void => {
        hydrateFromStorage();
        isCollapsed.value = !isCollapsed.value;
    };

    const openMobile = (): void => {
        isMobileOpen.value = true;
    };

    const closeMobile = (): void => {
        isMobileOpen.value = false;
    };

    const toggleMobile = (): void => {
        isMobileOpen.value = !isMobileOpen.value;
    };

    return {
        isCollapsed,
        isMobileOpen,
        toggleCollapsed,
        openMobile,
        closeMobile,
        toggleMobile,
    };
}
