import type { VehicleModel } from './reference';

export type VehicleStatus = 'available' | 'maintenance' | 'out_of_service';

export type VehicleStatusOption = {
    value: VehicleStatus;
    label: string;
};

export type Vehicle = {
    id: number;
    /** Identifiant public utilisé dans les URL. */
    uid: string;
    name: string;
    vehicle_model_id: number | null;
    vehicle_model?: VehicleModel | null;
    seats: number;
    registration_number: string;
    year: number;
    has_air_conditioning: boolean;
    /** Litres aux 100 km — sérialisé en chaîne par le cast `decimal:2`. */
    average_consumption: string | null;
    status: VehicleStatus;
    image_path: string | null;
    image_url: string | null;
};
