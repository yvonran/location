<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Expose un identifiant opaque dans les URL.
 *
 * La clé primaire entière reste celle de la base — elle porte les relations —
 * mais elle n'apparaît plus dans les adresses : /simulations/1 laissait deviner
 * les enregistrements voisins et le volume total.
 *
 * Un ULID est utilisé pour sa compacité en URL. Il encode l'instant de
 * création : si l'ordre de création doit lui aussi rester secret, remplacer
 * `Str::ulid()` par `Str::uuid()`.
 */
trait HasPublicUid
{
    public static function bootHasPublicUid(): void
    {
        static::creating(function (Model $model) {
            if ($model->getAttribute('uid') === null) {
                $model->setAttribute('uid', (string) Str::ulid());
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uid';
    }
}
