# Étape 2 — Modèles Eloquent et relations

Date : 2026-07-22
Statut : validé par l'utilisateur, en attente de relecture finale avant plan d'implémentation.

## Contexte

Deuxième étape du plan de livraison en 9 étapes. S'appuie directement sur le schéma de base de données validé dans `docs/superpowers/specs/2026-07-22-schema-base-de-donnees-design.md` (toutes les tables déjà créées via migrations). Cette spec couvre uniquement les modèles Eloquent, leurs relations et leurs enums PHP — pas les factories (reportées à l'étape 3, seeders) ni la logique métier de calcul (réservée aux services de l'étape 4).

## Convention de code existante à suivre

Le modèle `User` existant utilise les attributs PHP 8 `#[Fillable([...])]` et `#[Hidden([...])]` (`Illuminate\Database\Eloquent\Attributes\Fillable`/`Hidden`) plutôt que les propriétés classiques `protected $fillable`. Tous les nouveaux modèles suivent cette même convention pour rester cohérents avec le code déjà en place.

## PHP Enums (`app/Enums/`)

Enums backés par des chaînes, valeurs identiques à celles des colonnes `enum` en base :

- **`VehicleStatus`** : `Available = 'available'`, `Maintenance = 'maintenance'`, `OutOfService = 'out_of_service'`
- **`QuoteStatus`** : `Draft = 'draft'`, `Sent = 'sent'`, `Accepted = 'accepted'`, `Rejected = 'rejected'`
- **`AmountMode`** : `Fixed = 'fixed'`, `Percentage = 'percentage'` — réutilisé pour `option_types.default_mode`, `quote_lines.discount_type`, `quote_line_options.mode` (même concept partout : DRY plutôt que trois enums identiques)
- **`ReservationLineStatus`** : `Confirmed = 'confirmed'`, `InProgress = 'in_progress'`, `Completed = 'completed'`, `Cancelled = 'cancelled'`

## Modèles (`app/Models/`)

Chaque modèle : relations Eloquent typées (types de retour `BelongsTo`/`HasMany`/`HasOne`), casts appropriés (`decimal:2` pour les montants, `date` pour les dates, enum ci-dessus pour les colonnes enum), `SoftDeletes` sur les modèles dont la table a `deleted_at` (`Customer`, `Vehicle`, `Quote`, `Reservation`), `HasFactory` inclus par défaut (convention `make:model`) même si la factory concrète n'existe pas encore.

| Modèle | Relations |
|---|---|
| `Customer` | `hasMany(Quote::class)` |
| `Vehicle` | `hasMany(Tariff::class)`, `hasMany(QuoteLine::class)`, `hasMany(ReservationLine::class)` |
| `Route` | `hasMany(QuoteLine::class)` |
| `ServiceType` | `hasMany(QuoteLine::class)` |
| `OptionType` | `hasMany(QuoteLineOption::class)` |
| `Tariff` | `belongsTo(Vehicle::class)` |
| `Quote` | `belongsTo(Customer::class)`, `belongsTo(User::class)`, `hasMany(QuoteLine::class)`, `hasOne(Reservation::class)` |
| `QuoteLine` | `belongsTo(Quote::class)`, `belongsTo(Vehicle::class)`, `belongsTo(Route::class)` (nullable), `belongsTo(ServiceType::class)`, `hasMany(QuoteLineOption::class)` |
| `QuoteLineOption` | `belongsTo(QuoteLine::class)`, `belongsTo(OptionType::class)` |
| `Reservation` | `belongsTo(Quote::class)`, `hasMany(ReservationLine::class)` |
| `ReservationLine` | `belongsTo(Reservation::class)`, `belongsTo(QuoteLine::class)`, `belongsTo(Vehicle::class)` |
| `User` (modification) | ajout de `hasMany(Quote::class)` |

### Décision de conception

`Quote::reservation()` est un `hasOne`, pas un `hasMany` : un devis accepté ne donne naissance qu'à une seule réservation (workflow du cahier des charges), jamais plusieurs.

## Hors périmètre de cette spec

- Factories Eloquent (étape 3, avec les seeders)
- Toute méthode de calcul métier sur les modèles (`calculateTotal()`, etc.) — réservé à `QuoteCalculationService` (étape 4)
- Contrôleurs, policies, form requests (étape 5)
- Scopes de requête avancés au-delà des relations de base

## Tests

Tests Feature (`tests/Feature/Models/`) vérifiant :
- Chaque relation résout correctement (ex : `$quote->customer` retourne bien le `Customer` attendu)
- Les casts enum fonctionnent dans les deux sens (assigner une valeur string valide, relire l'instance d'enum ; ex : `$vehicle->status` renvoie `VehicleStatus::Available` après création avec `status: 'available'`)
- Les casts `decimal`/`date` renvoient les types attendus
