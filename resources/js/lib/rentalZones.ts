import type { RentalZone, RentalZoneInput } from '@/types/rental';

/**
 * Convertit les zones renvoyées par le serveur en structure éditable : les
 * montants transitent en chaîne à cause du cast `decimal:2`.
 */
export function toZoneInputs(zones: RentalZone[]): RentalZoneInput[] {
    return zones.map((zone) => ({
        name: zone.name,
        max_km: zone.max_km,
        rates: zone.rates.map((rate) => ({
            min_days: rate.min_days,
            max_days: rate.max_days,
            daily_rate: Number(rate.daily_rate),
        })),
    }));
}
