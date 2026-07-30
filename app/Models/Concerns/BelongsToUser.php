<?php

namespace App\Models\Concerns;

use App\Models\User;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cloisonne un modèle par compte.
 *
 * Le filtre est posé en portée globale plutôt que dans chaque contrôleur : une
 * requête oubliée ne peut donc pas laisser fuir les données d'un autre compte,
 * et la résolution de route renvoie un 404 au lieu d'exposer l'existence de
 * l'enregistrement. Le superadmin, lui, voit tout.
 */
trait BelongsToUser
{
    public static function bootBelongsToUser(): void
    {
        static::addGlobalScope('owned', function (Builder $query) {
            $user = auth()->user();

            if (! $user instanceof User || $user->hasRole(Roles::SuperAdmin)) {
                return;
            }

            $query->where($query->getModel()->qualifyColumn('user_id'), $user->id);
        });

        // Passage par getAttribute/setAttribute : `auth()->id()` est typé
        // int|string|null et la colonne n'est pas déclarée sur le trait.
        static::creating(function (Model $model) {
            if ($model->getAttribute('user_id') === null) {
                $model->setAttribute('user_id', auth()->id());
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
