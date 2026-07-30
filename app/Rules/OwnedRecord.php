<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

/**
 * Remplace `exists:table,id` pour tout ce qui est cloisonné.
 *
 * `exists` interroge la table en SQL brut et ignore donc la portée globale :
 * un compte pourrait référencer l'enregistrement d'un autre en devinant son
 * identifiant. En passant par le modèle, la portée s'applique — et le
 * superadmin conserve son accès complet.
 *
 * @template TModel of Model
 */
class OwnedRecord implements ValidationRule
{
    /**
     * @param  class-string<TModel>  $modelClass
     */
    public function __construct(private readonly string $modelClass) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! $this->modelClass::query()->whereKey($value)->exists()) {
            $fail('La ressource sélectionnée est introuvable.');
        }
    }
}
