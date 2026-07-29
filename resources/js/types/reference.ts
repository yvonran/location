export type Brand = {
    id: number;
    name: string;
    vehicle_models_count?: number;
};

export type VehicleType = {
    id: number;
    name: string;
    position?: number;
    vehicle_models_count?: number;
};

export type VehicleModel = {
    id: number;
    brand_id: number;
    /** null tant que le superadmin ne l'a pas classé. */
    vehicle_type_id: number | null;
    name: string;
    brand?: Brand;
    vehicle_type?: VehicleType | null;
};
