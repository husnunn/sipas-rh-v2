import {
    provinces,
    regencies,
    districts,
    villages,
    villageContext,
} from '@/actions/App/Http/Controllers/Admin/WilayahController';
import { useHttp } from '@inertiajs/vue3';
import { ref, watch, type Ref } from 'vue';

export type WilayahOption = { id: string; name: string };

type VillageContextResponse = {
    wilayah_province_id: string;
    wilayah_regency_id: string;
    wilayah_district_id: string;
    wilayah_village_id: string;
};

type AddressFields = {
    wilayah_village_id: string;
    province: string;
    city: string;
    district: string;
    village: string;
};

export function useWilayahCascade(form: AddressFields): {
    provinceOptions: Ref<WilayahOption[]>;
    regencyOptions: Ref<WilayahOption[]>;
    districtOptions: Ref<WilayahOption[]>;
    villageOptions: Ref<WilayahOption[]>;
    wilayahProvinceId: Ref<string>;
    wilayahRegencyId: Ref<string>;
    wilayahDistrictId: Ref<string>;
    wilayahVillageId: Ref<string>;
    wilayahLoading: Ref<boolean>;
    bootstrapWilayah: () => Promise<void>;
    onWilayahProvinceChange: () => Promise<void>;
    onWilayahRegencyChange: () => Promise<void>;
    onWilayahDistrictChange: () => Promise<void>;
} {
    const http = useHttp();

    const provinceOptions = ref<WilayahOption[]>([]);
    const regencyOptions = ref<WilayahOption[]>([]);
    const districtOptions = ref<WilayahOption[]>([]);
    const villageOptions = ref<WilayahOption[]>([]);

    const wilayahProvinceId = ref('');
    const wilayahRegencyId = ref('');
    const wilayahDistrictId = ref('');
    const wilayahVillageId = ref('');

    const wilayahLoading = ref(false);

    const syncNamesToForm = (): void => {
        const pv = provinceOptions.value.find((o) => o.id === wilayahProvinceId.value);
        const rg = regencyOptions.value.find((o) => o.id === wilayahRegencyId.value);
        const ds = districtOptions.value.find((o) => o.id === wilayahDistrictId.value);
        const vl = villageOptions.value.find((o) => o.id === wilayahVillageId.value);

        if (pv && rg && ds && vl) {
            form.province = pv.name;
            form.city = rg.name;
            form.district = ds.name;
            form.village = vl.name;
            form.wilayah_village_id = vl.id;

            return;
        }

        form.wilayah_village_id = '';
    };

    const clearLowerThanProvince = (): void => {
        wilayahRegencyId.value = '';
        wilayahDistrictId.value = '';
        wilayahVillageId.value = '';
        regencyOptions.value = [];
        districtOptions.value = [];
        villageOptions.value = [];
    };

    const clearLowerThanRegency = (): void => {
        wilayahDistrictId.value = '';
        wilayahVillageId.value = '';
        districtOptions.value = [];
        villageOptions.value = [];
    };

    const clearLowerThanDistrict = (): void => {
        wilayahVillageId.value = '';
        villageOptions.value = [];
    };

    async function loadProvinces(): Promise<void> {
        wilayahLoading.value = true;
        try {
            const rows = (await http.get(provinces.url())) as WilayahOption[];
            provinceOptions.value = Array.isArray(rows) ? rows : [];
        } finally {
            wilayahLoading.value = false;
        }
    }

    async function loadRegencies(provinceId: string): Promise<void> {
        wilayahLoading.value = true;
        try {
            const rows = (await http.get(regencies.url({ query: { province_id: provinceId } }))) as WilayahOption[];
            regencyOptions.value = Array.isArray(rows) ? rows : [];
        } finally {
            wilayahLoading.value = false;
        }
    }

    async function loadDistricts(regencyId: string): Promise<void> {
        wilayahLoading.value = true;
        try {
            const rows = (await http.get(districts.url({ query: { regency_id: regencyId } }))) as WilayahOption[];
            districtOptions.value = Array.isArray(rows) ? rows : [];
        } finally {
            wilayahLoading.value = false;
        }
    }

    async function loadVillages(districtId: string): Promise<void> {
        wilayahLoading.value = true;
        try {
            const rows = (await http.get(villages.url({ query: { district_id: districtId } }))) as WilayahOption[];
            villageOptions.value = Array.isArray(rows) ? rows : [];
        } finally {
            wilayahLoading.value = false;
        }
    }

    async function bootstrapWilayah(): Promise<void> {
        await loadProvinces();

        const vid = form.wilayah_village_id;
        if (typeof vid !== 'string' || vid.length !== 10) {
            return;
        }

        wilayahLoading.value = true;
        try {
            const ctx = (await http.get(
                villageContext.url({ query: { wilayah_village_id: vid } }),
            )) as VillageContextResponse;

            wilayahProvinceId.value = ctx.wilayah_province_id;
            await loadRegencies(ctx.wilayah_province_id);
            wilayahRegencyId.value = ctx.wilayah_regency_id;
            await loadDistricts(ctx.wilayah_regency_id);
            wilayahDistrictId.value = ctx.wilayah_district_id;
            await loadVillages(ctx.wilayah_district_id);
            wilayahVillageId.value = ctx.wilayah_village_id;
        } catch {
            wilayahProvinceId.value = '';
            wilayahRegencyId.value = '';
            wilayahDistrictId.value = '';
            wilayahVillageId.value = '';
            form.wilayah_village_id = '';
        } finally {
            wilayahLoading.value = false;
        }
    }

    async function onWilayahProvinceChange(): Promise<void> {
        clearLowerThanProvince();
        form.province = '';
        form.city = '';
        form.district = '';
        form.village = '';
        form.wilayah_village_id = '';

        if (wilayahProvinceId.value !== '') {
            await loadRegencies(wilayahProvinceId.value);
        }
    }

    async function onWilayahRegencyChange(): Promise<void> {
        clearLowerThanRegency();
        form.city = '';
        form.district = '';
        form.village = '';
        form.wilayah_village_id = '';

        if (wilayahRegencyId.value !== '') {
            await loadDistricts(wilayahRegencyId.value);
        }
    }

    async function onWilayahDistrictChange(): Promise<void> {
        clearLowerThanDistrict();
        form.district = '';
        form.village = '';
        form.wilayah_village_id = '';

        if (wilayahDistrictId.value !== '') {
            await loadVillages(wilayahDistrictId.value);
        }
    }

    watch(wilayahVillageId, (id) => {
        if (id === '') {
            form.wilayah_village_id = '';
            form.village = '';

            return;
        }

        syncNamesToForm();
    });

    return {
        provinceOptions,
        regencyOptions,
        districtOptions,
        villageOptions,
        wilayahProvinceId,
        wilayahRegencyId,
        wilayahDistrictId,
        wilayahVillageId,
        wilayahLoading,
        bootstrapWilayah,
        onWilayahProvinceChange,
        onWilayahRegencyChange,
        onWilayahDistrictChange,
    };
}
