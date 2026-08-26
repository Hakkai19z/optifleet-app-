<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\UtilisateurRepository;
use App\State\UtilisateurPasswordProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\Table(name: 'utilisateur')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    normalizationContext: ['groups' => ['utilisateur:read']],
    denormalizationContext: ['groups' => ['utilisateur:write']],
    operations: [
        new GetCollection(security: "is_granted('ROLE_GESTIONNAIRE')"),
        // Création : réservée à l'admin. Le rôle et le mot de passe (groupe
        // utilisateur:admin) ne sont modifiables que sur cette opération et le
        // Patch admin ; le mot de passe est haché par le processor dédié.
        new Post(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['utilisateur:write', 'utilisateur:admin']],
            processor: UtilisateurPasswordProcessor::class,
        ),
        new Get(security: "is_granted('ROLE_ADMIN') or object == user"),
        // Édition de profil (nom, prénom, email) : par l'admin ou l'utilisateur
        // lui-même. Le groupe utilisateur:write NE contient PAS role/motDePasse,
        // donc un utilisateur ne peut pas élever ses privilèges.
        new Put(security: "is_granted('ROLE_ADMIN') or object == user"),
        // Modification du rôle et/ou du mot de passe : admin uniquement.
        new Patch(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['utilisateur:write', 'utilisateur:admin']],
            processor: UtilisateurPasswordProcessor::class,
        ),
        new Delete(security: "is_granted('ROLE_ADMIN') or object == user"),
    ]
)]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['utilisateur:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[Groups(['utilisateur:read', 'utilisateur:write', 'affectation:read', 'reservation:read'])]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[Groups(['utilisateur:read', 'utilisateur:write', 'affectation:read', 'reservation:read'])]
    private ?string $prenom = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['utilisateur:read', 'utilisateur:write'])]
    private ?string $email = null;

    // Hash bcrypt stocké en base. Volontairement hors de tout groupe de
    // sérialisation : jamais exposé en lecture, jamais renseigné directement
    // en écriture (on passe par plainMotDePasse + processor de hachage).
    #[ORM\Column(length: 255)]
    private ?string $motDePasse = null;

    // Mot de passe en clair, non persisté. Fourni uniquement par une opération
    // admin (groupe utilisateur:admin) puis haché par UtilisateurPasswordProcessor.
    #[Assert\Length(min: 8, minMessage: 'Le mot de passe doit contenir au moins 8 caractères')]
    #[Assert\Regex(
        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
        message: 'Le mot de passe doit contenir au moins une minuscule, une majuscule et un chiffre'
    )]
    #[Groups(['utilisateur:admin'])]
    private ?string $plainMotDePasse = null;

    // Le rôle n'est modifiable que par un admin (groupe utilisateur:admin),
    // jamais via l'édition de profil (utilisateur:write) : pas d'auto-élévation.
    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: ['ADMIN', 'GESTIONNAIRE', 'CONDUCTEUR'])]
    #[Groups(['utilisateur:read', 'utilisateur:admin'])]
    private string $role = 'CONDUCTEUR';

    #[ORM\Column]
    #[Groups(['utilisateur:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'conducteur', targetEntity: Affectation::class)]
    private Collection $affectations;

    public function __construct()
    {
        $this->affectations = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getMotDePasse(): ?string
    {
        return $this->motDePasse;
    }

    public function setMotDePasse(string $motDePasse): static
    {
        $this->motDePasse = $motDePasse;

        return $this;
    }

    public function getPlainMotDePasse(): ?string
    {
        return $this->plainMotDePasse;
    }

    public function setPlainMotDePasse(?string $plainMotDePasse): static
    {
        $this->plainMotDePasse = $plainMotDePasse;

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getRoles(): array
    {
        return ['ROLE_' . $this->role, 'ROLE_USER'];
    }

    public function getPassword(): string
    {
        return $this->motDePasse ?? '';
    }

    public function getUserIdentifier(): string
    {
        return $this->email ?? '';
    }

    public function eraseCredentials(): void
    {
        $this->plainMotDePasse = null;
    }

    public function getAffectations(): Collection
    {
        return $this->affectations;
    }
}
