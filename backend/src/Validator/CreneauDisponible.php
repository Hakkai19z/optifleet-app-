<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class CreneauDisponible extends Constraint
{
    public string $message = 'Ce véhicule est déjà réservé sur ce créneau.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
