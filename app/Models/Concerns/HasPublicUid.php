<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Expose un identifiant opaque dans les URL.
 *
 * La clé primaire entière reste celle de la base — elle porte les relations —
 * mais elle n'apparaît plus dans les adresses : /simulations/1 laissait deviner
 * les enregistrements voisins et le volume total.
 *
 * La génération passe par `HasUniqueIds`, déclenché par l'insertion elle-même
 * et non par un événement de modèle : les seeders utilisent `WithoutModelEvents`
 * et laisseraient sinon des identifiants vides, donc des URL cassées.
 *
 * Un ULID est utilisé pour sa compacité en URL. Il encode l'instant de
 * création : si l'ordre de création doit lui aussi rester secret, remplacer
 * `Str::ulid()` par `Str::uuid()`.
 */
trait HasPublicUid
{
    /**
     * Eloquent porte déjà le mécanisme, mais désactivé par défaut : on
     * l'active à l'instanciation du modèle.
     */
    public function initializeHasPublicUid(): void
    {
        $this->usesUniqueIds = true;
    }

    /**
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uid'];
    }

    public function newUniqueId(): string
    {
        return (string) Str::ulid();
    }

    public function getRouteKeyName(): string
    {
        return 'uid';
    }
}
