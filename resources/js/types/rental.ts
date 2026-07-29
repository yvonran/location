export type RentalZone =
    'city' | 'suburb' | 'long_distance' | 'very_long_distance';

export type RentalZoneOption = {
    value: RentalZone;
    label: string;
};

/** Bornes hautes du trajet aller, en km. */
export type RentalConditionThresholds = {
    city_max_km: number;
    suburb_max_km: number;
    long_distance_max_km: number;
};

export type RentalRate = {
    id: number;
    zone: RentalZone;
    min_days: number;
    /** null = pas de borne haute, la tranche est ouverte. */
    max_days: number | null;
    /** Sérialisé en chaîne par le cast `decimal:2`. */
    daily_rate: string;
};
