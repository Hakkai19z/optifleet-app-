<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Utilisateur;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Décore le processor Doctrine par défaut d'API Platform pour hacher le mot de
 * passe (bcrypt) avant persistance. Le mot de passe transite uniquement via la
 * propriété transitoire plainMotDePasse (groupe utilisateur:admin) ; il n'est
 * jamais stocké ni exposé en clair.
 *
 * @implements ProcessorInterface<Utilisateur, Utilisateur>
 */
final class UtilisateurPasswordProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<Utilisateur, Utilisateur> $persistProcessor
     */
    public function __construct(
        private readonly ProcessorInterface $persistProcessor,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof Utilisateur) {
            $plain = $data->getPlainMotDePasse();

            if (null !== $plain && '' !== $plain) {
                $data->setMotDePasse($this->hasher->hashPassword($data, $plain));
                $data->eraseCredentials();
            } elseif (null === $data->getMotDePasse()) {
                // Création sans mot de passe fourni : refus explicite plutôt
                // qu'une erreur d'intégrité en base.
                throw new UnprocessableEntityHttpException('Le mot de passe est requis.');
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
