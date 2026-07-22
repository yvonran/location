# Étape 1 — Schéma de base de données

Date : 2026-07-22
Statut : validé par l'utilisateur, en attente de relecture finale avant plan d'implémentation.

## Contexte

Application de gestion de location de véhicules pour une agence de transport touristique à Madagascar (objectif : générer un devis en moins de 30 secondes). Cette spec couvre uniquement l'étape 1 du plan de livraison en 9 étapes fourni par l'utilisateur : le schéma de base de données (MCD + plan de migrations Laravel). Les étapes suivantes (modèles Eloquent, seeders, services métier, contrôleurs, UI, tests, PDF, dashboard) font l'objet de specs/plans séparés.

**Convention de nommage** : tables, colonnes, modèles et énumérations en **anglais** (conventions Laravel standard). La conversation et cette documentation restent en français.

## Décisions de stack (validées)

- Le projet est un starter kit Laravel existant utilisant **Inertia.js + Vue 3 + TypeScript + Tailwind CSS** (Fortify, 2FA, passkeys déjà en place). On conserve cette stack plutôt que d'introduire Blade + Livewire.
- Laravel installé : **^13.17** (le cahier des charges mentionnait Laravel 12 ; on continue avec la version installée).
- `spatie/laravel-permission` et `barryvdh/laravel-dompdf` seront ajoutés (pas encore installés).

## Principe directeur

Aucune règle métier ne doit être codée en dur (`if`) dans le code applicatif pour : les tarifs, les coefficients de prestation, les rôles. Tout est piloté par des tables de configuration.

## Bloc A — Référentiel

**`customers`**
- id, name, phone, email (nullable), address (nullable), tax_id (nullable — NIF), timestamps, soft deletes

**`vehicles`**
- id, name, brand, model, seats, registration_number (unique), year, has_air_conditioning (bool), status (enum: `available`, `maintenance`, `out_of_service`), timestamps, soft deletes
- `status` est un champ **manuel**, utilisé uniquement pour sortir un véhicule du parc (maintenance, hors service). Il n'inclut pas de valeur "réservé" : l'occupation par réservation est **calculée dynamiquement** à partir de `reservation_lines` (voir Bloc D), jamais stockée sur le véhicule, pour éviter toute désynchronisation entre les deux sources de vérité.

**`routes`**
- id, name (ex: "RN2"), departure_city (défaut "Antananarivo" au niveau formulaire), arrival_city, distance_km (decimal), estimated_duration_minutes (nullable int), description (nullable text — détail des villes intermédiaires), timestamps
- Pas de table `cities` séparée : les trajets sont un référentiel géré par l'administration (pas saisi à la volée par les agents), le texte libre est suffisant
- Données de seed (étape 3) : les ~50 routes nationales fournies par l'utilisateur (RN1 à RN55). Certaines lignes de la liste source n'ont pas de distance connue (ex. RNT19, RN31a) — elles seront seedées avec une distance à compléter manuellement plus tard

**`service_types`**
- id, name, coefficient (decimal), description (nullable), active (bool), timestamps
- Remplace tout switch/if codé en dur sur le type de prestation (Location, Transfert, Circuit touristique, Mise à disposition, Aller simple, Aller-retour, etc.)

**`option_types`**
- id, name, default_mode (enum: `fixed`, `percentage`), default_value (decimal), active (bool), timestamps
- Catalogue configurable des options (chauffeur supplémentaire, carburant, péages, ferry, hébergement chauffeur, guide, assurance)

## Bloc B — Grille tarifaire

**`tariffs`**
- id, vehicle_id (FK), min_distance_km (int), max_distance_km (nullable int, null = pas de plafond), min_days (int), max_days (nullable int, null = pas de plafond), daily_rate (decimal), timestamps

Le tarif dépend **uniquement** du véhicule, de la distance et du nombre de jours — **pas** du type de prestation (voir Bloc C pour comment le type de prestation intervient).

Recherche du tarif :
```
WHERE vehicle_id = :vehicle_id
  AND :distance_km BETWEEN min_distance_km AND COALESCE(max_distance_km, infini)
  AND :number_of_days BETWEEN min_days AND COALESCE(max_days, infini)
```

Une contrainte applicative (validation, pas contrainte SQL) empêche les chevauchements de plages lors de la création/édition d'un tarif pour un même véhicule.

## Bloc C — Devis

**`quotes`** (en-tête)
- id, number (unique, ex: `QUO-2026-0001`), customer_id (FK), user_id (agent créateur, FK), quote_date, status (enum: `draft`, `sent`, `accepted`, `rejected`), subtotal (caché, somme des `line_total`), total (caché, = subtotal ; prix TTC, pas de TVA — voir "TVA" ci-dessous), notes (nullable), timestamps, soft deletes

**`quote_lines`**
- id, quote_id (FK), vehicle_id (FK), route_id (FK **nullable**), service_type_id (FK), start_date, number_of_days, distance_km (figée : copiée du trajet si choisi, sinon saisie manuellement), daily_rate (figé, résultat de la recherche dans `tariffs` au moment de la création), service_coefficient (figé, copié de `service_types.coefficient`), discount_type (nullable: `fixed`/`percentage`), discount_value (nullable decimal), discount_amount (calculé), options_amount (calculé, somme des options de la ligne), line_total (calculé), position (int, position d'affichage), timestamps

Le trajet est **optionnel** : l'agent peut soit choisir un trajet existant (distance auto-remplie), soit saisir directement une distance à parcourir sans trajet formel ("aller" / distance libre).

Les champs `daily_rate`, `service_coefficient`, `distance_km` sont **figés** au moment de la création de la ligne (snapshot), pour que l'édition ultérieure d'un véhicule, d'un tarif ou d'un type de prestation ne modifie jamais un devis déjà émis.

**`quote_line_options`**
- id, quote_line_id (FK), option_type_id (FK), mode (`fixed`/`percentage`, copié de `option_types` ou surchargé), value (copiée ou surchargée), amount (calculé), timestamps

### Ordre de calcul par ligne (implémenté dans `QuoteCalculationService`, étape 4)

1. `distance_km` déterminée (trajet choisi, ou saisie manuelle)
2. Recherche dans `tariffs` (véhicule + distance + jours) → `daily_rate`
3. `service_amount = daily_rate × number_of_days × service_coefficient`
4. `options_amount` = somme des options de la ligne (montant fixe ajouté tel quel ; pourcentage calculé sur `service_amount`)
5. `discount_amount` calculé sur `(service_amount + options_amount)`
6. `line_total = service_amount + options_amount − discount_amount`

Le total du devis = somme des `line_total` de toutes ses lignes.

### TVA

**Retirée du périmètre.** Les prix sont TTC (toutes taxes comprises) directement dans `daily_rate` et les tarifs de la grille. Aucune colonne ni mécanisme de TVA n'est prévu en base pour l'instant (décision utilisateur du 2026-07-22, remplace la demande initiale du cahier des charges de "prévoir un système pour ajouter la TVA ultérieurement").

## Bloc D — Réservations & disponibilités

**`reservations`**
- id, number (unique, ex: `RES-2026-0001`), quote_id (FK — une réservation naît toujours d'un devis accepté), timestamps

**`reservation_lines`**
- id, reservation_id (FK), quote_line_id (FK), vehicle_id (FK, dénormalisé pour requêtes calendrier), start_date, end_date (calculée = start_date + number_of_days), status (enum: `confirmed`, `in_progress`, `completed`, `cancelled`), timestamps

Le calendrier de disponibilités interroge `reservation_lines` (véhicule occupé si une ligne active avec statut `confirmed`/`in_progress` chevauche la période demandée) combiné au champ `vehicles.status` pour le cas "maintenance" saisi manuellement. Aucune table calendrier séparée.

## Bloc E — Utilisateurs, rôles, administration

Installation de `spatie/laravel-permission` (migrations fournies par le package). Rôles seedés par défaut : `admin` (accès total : gestion utilisateurs, rôles, référentiels) et `agent` (création devis, réservations, clients). Le module Administration permet d'en créer/modifier d'autres librement — aucun rôle codé en dur dans l'application.

## Bloc F — Transverse

- **Montants** : `decimal(12,2)` sur toutes les colonnes monétaires.
- **Numérotation** devis/réservations : séquentielle par année, générée par le service métier correspondant (pas une colonne auto-increment exposée).
- **Soft deletes** sur les tables métier principales (`customers`, `vehicles`, `quotes`, `reservations`) pour préserver l'historique et les statistiques.

## Hors périmètre de cette spec

- Modèles Eloquent, relations, factories, seeders (étapes 2-3)
- Services métier de calcul (étape 4)
- Contrôleurs, policies, form requests (étape 5)
- UI Inertia/Vue (étape 6)
- Tests (étape 7)
- Génération PDF et personnalisation du modèle (étape 8)
- Tableau de bord (étape 9)
