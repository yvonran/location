export type RentalRate = {
    min_days: number;
    /** null = tranche ouverte, sans borne haute. */
    max_days: number | null;
    /** Sérialisé en chaîne par le cast `decimal:2`. */
    daily_rate: string;
    meal_included: boolean;
    /** null tant que le repas n'est pas facturé séparément. */
    meal_price: string | null;
};

export type RentalZone = {
    name: string;
    /** null = zone ouverte ; réservé à la dernière zone du découpage. */
    max_km: number | null;
    rates: RentalRate[];
};

/** Formes éditables : le montant reste nul tant que rien n'a été saisi. */
export type RentalRateInput = {
    min_days: number;
    max_days: number | null;
    daily_rate: number | null;
    meal_included: boolean;
    meal_price: number | null;
};

export type RentalZoneInput = {
    name: string;
    max_km: number | null;
    rates: RentalRateInput[];
};
