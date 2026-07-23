# Étape 3 — Factories et seeders

Date : 2026-07-23
Statut : validé par l'utilisateur, en attente de relecture finale avant plan d'implémentation.

## Contexte

Troisième étape du plan de livraison en 9 étapes. S'appuie sur les modèles Eloquent de l'étape 2 (`docs/superpowers/specs/2026-07-22-eloquent-models-design.md`). Couvre les factories Eloquent (explicitement reportées de l'étape 2) et les seeders de données d'exemple. Ne couvre pas la logique de calcul de devis (étape 4) ni les seeders de devis/réservations d'exemple, plus pertinents une fois `QuoteCalculationService` disponible.

## Factories (`database/factories/`)

Une factory par modèle métier (11 au total), avec des données Faker plausibles. Chaque factory est indépendamment utilisable dans les tests futurs (`Model::factory()->create()`), pas seulement par les seeders.

| Factory | Champs générés |
|---|---|
| `CustomerFactory` | `name`, `phone`, `email`, `address`, `tax_id` (nullable) |
| `VehicleFactory` | `name`, `brand`, `model`, `seats`, `registration_number` (unique), `year`, `has_air_conditioning` |
| `RouteFactory` | `name`, `departure_city`, `arrival_city`, `distance_km`, `estimated_duration_minutes` (nullable), `description` (nullable) |
| `ServiceTypeFactory` | `name`, `coefficient`, `description` (nullable), `active` |
| `OptionTypeFactory` | `name`, `default_mode`, `default_value`, `active` |
| `TariffFactory` | `vehicle_id` (via `Vehicle::factory()`), bornes distance/jours, `daily_rate` |
| `QuoteFactory` | `number` (unique séquentiel), `customer_id`, `user_id`, `quote_date`, `status`, montants à 0 |
| `QuoteLineFactory` | `quote_id`, `vehicle_id`, `route_id` (nullable), `service_type_id`, dates/jours/montants |
| `QuoteLineOptionFactory` | `quote_line_id`, `option_type_id`, `mode`, `value`, `amount` |
| `ReservationFactory` | `number` (unique séquentiel), `quote_id` |
| `ReservationLineFactory` | `reservation_id`, `quote_line_id`, `vehicle_id`, dates, `status` |

Les uniques (`registration_number`, `quotes.number`, `reservations.number`) sont générés par séquence Faker pour ne jamais entrer en collision sur des créations multiples.

## Seeders (`database/seeders/`)

### Rôles et utilisateurs

- Rôles Spatie `admin` et `agent` créés.
- Utilisateur `admin@agence.mg` (rôle `admin`), utilisateur `agent@agence.mg` (rôle `agent`).
- L'utilisateur "Test User" existant du `DatabaseSeeder` actuel n'est pas modifié.

### Clients

3 clients nommés (noms/adresses malgaches, NIF renseigné) + 10 clients générés via factory pour donner du volume au tableau de bord.

### Véhicules

4 véhicules représentatifs nommés (avec grille tarifaire complète, voir ci-dessous) + quelques véhicules générés via factory sans grille tarifaire (pour couvrir le cas "véhicule sans tarif encore configuré") :
- Starex 1 — Hyundai Starex, 8 places, climatisé
- Land Cruiser 1 — Toyota Land Cruiser (4x4), 7 places, climatisé
- Corolla 1 — Toyota Corolla (berline), 4 places, climatisé
- Coaster 1 — Toyota Coaster (minibus), 28 places, climatisé

### Trajets

38 trajets (RN1 à RN55, liste fournie par l'utilisateur), chacun avec `name`, `departure_city`, `arrival_city`, `distance_km`. Les 3 entrées sans distance connue (RNT19, RN31a, RN54) sont omises — à ajouter manuellement plus tard via l'administration une fois la distance connue.

### Types de prestation

| Nom | Coefficient |
|---|---|
| Location | 1.00 |
| Transfert | 2.00 |
| Circuit touristique | 1.50 |
| Mise à disposition | 1.20 |
| Aller simple | 1.00 |
| Aller-retour | 1.80 |

Valeurs de départ validées par l'utilisateur, modifiables ensuite via l'administration (étape 6) — aucune valeur codée en dur ailleurs dans l'application.

### Types d'option

| Nom | Mode | Valeur |
|---|---|---|
| Chauffeur supplémentaire | fixe | 50 000 Ar |
| Carburant | fixe | 100 000 Ar |
| Péages | fixe | 20 000 Ar |
| Ferry | fixe | 150 000 Ar |
| Hébergement chauffeur | fixe | 30 000 Ar |
| Guide | fixe | 80 000 Ar |
| Assurance | pourcentage | 5% |

Valeurs de départ validées par l'utilisateur, modifiables ensuite via l'administration.

### Grilles tarifaires

Pour Starex 1, la grille exacte du cahier des charges :
- 0–799 km : 1–5 jours → 250 000 Ar/jour, 6–10 jours → 220 000 Ar/jour, 11+ jours → 200 000 Ar/jour
- ≥800 km : 1–5 jours → 350 000 Ar/jour, 6–10 jours → 310 000 Ar/jour, 11+ jours → 250 000 Ar/jour

Pour Land Cruiser 1, Corolla 1, Coaster 1 : grilles à 6 paliers de structure identique, prix ajustés par type de véhicule (exemples pédagogiques, à corriger via l'administration si besoin).

## Hors périmètre de cette spec

- Seeders de devis/réservations d'exemple (plus pertinents une fois `QuoteCalculationService` disponible, étape 4)
- Toute logique de calcul (étape 4)
- Interface d'administration pour éditer ces données (étape 6)

## Tests

- Un test par groupe de factories vérifiant qu'elles produisent des enregistrements valides et que les créations multiples ne violent aucune contrainte unique.
- Un test de bout en bout exécutant les seeders et vérifiant les comptes de lignes attendus par table (rôles, utilisateurs, clients, véhicules, trajets, types de prestation, types d'option, tarifs).
