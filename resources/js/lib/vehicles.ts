type WithModel = {
    vehicle_model?: {
        name: string;
        brand?: { name: string } | null;
    } | null;
};

/** « Hyundai Starex », ou un tiret si le véhicule n'est rattaché à aucun modèle. */
export function vehicleIdentity(vehicle: WithModel): string {
    const model = vehicle.vehicle_model;

    if (!model) {
        return '—';
    }

    return [model.brand?.name, model.name].filter(Boolean).join(' ');
}
