export type RentalRate = {
    min_days: number;
    /** null = tranche ouverte, sans borne haute. */
    max_days: number | null;
    /** Sérialisé en chaîne par le cast `decimal:2`. */
    daily_rate: string;
};

export type RentalZone = {
    name: string;
    /** null = zone ouverte ; réservé à la dernière zone du découpage. */
    max_km: number | null;
    rates: RentalRate[];
};
