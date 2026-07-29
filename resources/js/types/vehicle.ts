export type VehicleStatus = 'available' | 'maintenance' | 'out_of_service';

export type VehicleStatusOption = {
    value: VehicleStatus;
    label: string;
};

export type Vehicle = {
    id: number;
    name: string;
    brand: string;
    model: string;
    seats: number;
    registration_number: string;
    year: number;
    has_air_conditioning: boolean;
    status: VehicleStatus;
    image_path: string | null;
    image_url: string | null;
};
