# Colonne `is_supported` sur `vehicle_models`

## Contexte

`vehicle_models` est peuplé par import (`VehicleReferenceSeeder`), qui crée des
modèles sans `vehicle_type_id` (non classés). Le superadmin classe ensuite
certains modèles avec un type. Le select "Modèle" du formulaire véhicule
(`VehicleForm.vue`) affiche aujourd'hui **tous** les modèles, classés ou non,
ce qui pollue la liste avec des modèles importés non curés.

## Objectif

N'afficher dans le select "Modèle" (ajout/modification de véhicule) que les
modèles à la fois :
- classés (`vehicle_type_id` non null), et
- marqués disponibles (`is_supported = true`).

`is_supported` est un champ **indépendant**, réglable manuellement par le
superadmin dans la configuration des modèles — pas dérivé automatiquement du
type. Un modèle peut donc avoir un type sans être `is_supported` (ex: en
cours de validation).

## Migration & modèle

- Nouvelle colonne `is_supported` (boolean, défaut `false`) sur
  `vehicle_models`.
- Backfill dans la même migration : `is_supported = true` là où
  `vehicle_type_id` n'est pas null, pour préserver le comportement actuel au
  déploiement (rien ne disparaît du select existant).
- `App\Models\VehicleModel` : ajout de `is_supported` au `Fillable`, cast
  boolean.

## Configuration des modèles (`resources/js/pages/configuration/VehicleModels.vue`)

- Case à cocher "Disponible" dans le formulaire de création et le dialogue
  d'édition.
- Colonne/badge "Disponible" / "Non disponible" dans le tableau.
- Valeur par défaut à la création : `true`.
- `StoreVehicleModelRequest` : règle `is_supported => ['boolean']` (héritée
  par `UpdateVehicleModelRequest`).

## Filtrage du select véhicule (`VehicleController::referenceData()`)

Filtrer les modèles renvoyés au formulaire véhicule sur :

```php
VehicleModel::query()
    ->whereNotNull('vehicle_type_id')
    ->where('is_supported', true)
    ->with('brand:id,name')
    ->orderBy('name')
    ->get(['id', 'brand_id', 'vehicle_type_id', 'name', 'is_supported']);
```

`VehicleForm.vue` ne change pas : il consomme déjà `vehicleModels` tel que
fourni par le backend.

## Cas limite — véhicule existant avec un modèle exclu

Si un véhicule a été créé avec un modèle qui, depuis, a perdu son type ou son
statut disponible, le formulaire d'édition doit quand même pouvoir l'afficher
(sinon le select paraît vide alors qu'une valeur est sélectionnée).
`referenceData()` doit donc inclure explicitement le modèle actuel du
véhicule en édition, même s'il ne remplit plus les deux critères. Concrètement,
`referenceData()` prend un `?VehicleModel $currentModel` optionnel, et la
requête devient une union (`orWhere('id', $currentModel->id)`) quand ce
paramètre est fourni.

## Hors périmètre

- Pas de filtre `is_supported` supplémentaire sur la page de configuration
  des modèles (liste déjà filtrable par marque/type/recherche).
- Pas de renforcement de la validation serveur sur `vehicle_model_id`
  (`exists:vehicle_models,id` reste inchangé) : un id de modèle non
  "supported" resterait techniquement acceptable si envoyé directement, mais
  ce n'est pas le problème posé ici (le select, seule voie d'usage normale,
  est filtré).
