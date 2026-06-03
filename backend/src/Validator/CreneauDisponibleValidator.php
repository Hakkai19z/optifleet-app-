<?php

namespace App\Validator;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class CreneauDisponibleValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ReservationRepository $reservationRepository,
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof CreneauDisponible) {
            throw new UnexpectedValueException($constraint, CreneauDisponible::class);
        }

        if (!$value instanceof Reservation) {
            return;
        }

        // On ne contrôle pas un créneau incomplet ou une réservation annulée.
        if ($value->getVehicule() === null
            || $value->getDateDebut() === null
            || $value->getDateFin() === null
            || $value->isAnnulee()) {
            return;
        }

        $conflits = $this->reservationRepository->findChevauchements(
            $value->getVehicule(),
            $value->getDateDebut(),
            $value->getDateFin(),
            $value->getId(),
        );

        if (count($conflits) > 0) {
            $this->context->buildViolation($constraint->message)
                ->atPath('dateDebut')
                ->addViolation();
        }
    }
}
