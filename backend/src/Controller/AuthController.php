<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'limiter.login_limiter')]
        private readonly RateLimiterFactory $loginLimiter,
        #[Autowire(service: 'limiter.register_limiter')]
        private readonly RateLimiterFactory $registerLimiter,
    ) {
    }

    #[Route('/register', name: 'auth_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        JWTTokenManagerInterface $jwtManager,
        AuditLogger $audit,
    ): JsonResponse {
        $limiter = $this->registerLimiter->create($request->getClientIp());
        $limit = $limiter->consume(1);

        if (! $limit->isAccepted()) {
            $retryAfter = $limit->getRetryAfter()->getTimestamp() - time();

            return $this->json([
                'message' => 'Trop de tentatives. Réessayez dans ' . ceil($retryAfter / 60) . ' minute(s).',
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $data = json_decode($request->getContent(), true);
        $nom = trim($data['nom'] ?? '');
        $prenom = trim($data['prenom'] ?? '');
        $email = trim($data['email'] ?? '');
        $motDePasse = $data['motDePasse'] ?? '';

        if ('' === $nom || '' === $prenom || '' === $email || '' === $motDePasse) {
            return $this->json(['message' => 'Tous les champs sont requis'], Response::HTTP_BAD_REQUEST);
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['message' => 'Adresse e-mail invalide'], Response::HTTP_BAD_REQUEST);
        }

        if (strlen($motDePasse) < 8) {
            return $this->json(['message' => 'Le mot de passe doit contenir au moins 8 caractères'], Response::HTTP_BAD_REQUEST);
        }

        if (! preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $motDePasse)) {
            return $this->json(['message' => 'Le mot de passe doit contenir au moins une minuscule, une majuscule et un chiffre'], Response::HTTP_BAD_REQUEST);
        }

        // Message volontairement générique pour ne pas confirmer l'existence d'un
        // compte (limite l'énumération d'e-mails). L'énumération est en outre
        // freinée par le rate limiter. Une anti-énumération totale nécessiterait
        // un flux d'inscription avec vérification par e-mail.
        if ($em->getRepository(Utilisateur::class)->findOneBy(['email' => $email])) {
            return $this->json(['message' => 'Inscription impossible avec ces informations. Essayez de vous connecter.'], Response::HTTP_CONFLICT);
        }

        $user = new Utilisateur();
        $user->setNom($nom);
        $user->setPrenom($prenom);
        $user->setEmail($email);
        $user->setRole('CONDUCTEUR');
        $user->setMotDePasse($hasher->hashPassword($user, $motDePasse));

        $em->persist($user);
        $em->flush();

        $limiter->reset();

        $audit->enregistrer('inscription', $email, 'Compte conducteur créé', $email);

        $token = $jwtManager->create($user);

        return $this->json(['token' => $token], Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'auth_login', methods: ['POST'])]
    public function login(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        JWTTokenManagerInterface $jwtManager,
        AuditLogger $audit,
    ): JsonResponse {
        $limiter = $this->loginLimiter->create($request->getClientIp());
        $limit = $limiter->consume(1);

        if (! $limit->isAccepted()) {
            $retryAfter = $limit->getRetryAfter()->getTimestamp() - time();

            return $this->json([
                'message' => 'Trop de tentatives. Réessayez dans ' . ceil($retryAfter / 60) . ' minute(s).',
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';
        $motDePasse = $data['motDePasse'] ?? '';

        $user = $em->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);

        // Un compte anonymisé (RGPD) ne peut plus se connecter.
        if (! $user || null !== $user->getAnonymiseA() || ! $hasher->isPasswordValid($user, $motDePasse)) {
            $audit->enregistrer('login_echec', '' !== $email ? $email : null, 'Identifiants incorrects', '' !== $email ? $email : null);

            return $this->json(['message' => 'Identifiants incorrects'], Response::HTTP_UNAUTHORIZED);
        }

        $limiter->reset();

        $user->setDerniereConnexionA(new \DateTimeImmutable());
        $em->flush();

        $audit->enregistrer('login_reussi', null, null, $user->getUserIdentifier());

        $token = $jwtManager->create($user);

        return $this->json(['token' => $token]);
    }

    #[Route('/me', name: 'auth_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        return $this->json([
            'id' => $user->getId(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'email' => $user->getUserIdentifier(),
            'role' => $user->getRole(),
        ]);
    }

    #[Route('/delete-account', name: 'auth_delete_account', methods: ['DELETE'])]
    public function deleteAccount(EntityManagerInterface $em, UserPasswordHasherInterface $hasher, AuditLogger $audit): JsonResponse
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        $email = $user->getUserIdentifier();

        // Anonymisation plutôt que suppression physique : la ligne est conservée
        // (l'historique d'affectations la référence par clé étrangère), mais toutes
        // les données personnelles sont effacées et le compte devient inutilisable.
        $user->setNom('Utilisateur');
        $user->setPrenom('anonymisé');
        $user->setEmail('anonyme-' . $user->getId() . '@rgpd.invalid');
        $user->setMotDePasse($hasher->hashPassword($user, bin2hex(random_bytes(16))));
        $user->setAnonymiseA(new \DateTimeImmutable());

        $em->flush();

        $audit->enregistrer('compte_supprime', $email, 'Anonymisation RGPD du compte', $email);

        return $this->json(['message' => 'Compte anonymisé conformément au RGPD (droit à l\'effacement)'], Response::HTTP_OK);
    }
}
