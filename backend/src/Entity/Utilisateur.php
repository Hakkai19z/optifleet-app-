<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\UtilisateurRepository;
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
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Get(security: "is_granted('ROLE_ADMIN') or object == user"),
        new Put(security: "is_granted('ROLE_ADMIN') or object == user"),
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
    #[Groups(['utilisateur:read', 'utilisateur:write', 'affectation:read'])]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[Groups(['utilisateur:read', 'utilisateur:write', 'affectation:read'])]
    private ?string $prenom = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['utilisateur:read', 'utilisateur:write'])]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Groups(['utilisateur:write'])]
    private ?string $motDePasse = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: ['ADMIN', 'GESTIONNAIRE', 'CONDUCTEUR'])]
    #[Groups(['utilisateur:read', 'utilisateur:write'])]
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

    public function getId(): ?int { return $this->id; }
    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }
    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(string $prenom): static { $this->prenom = $prenom; return $this; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }
    public function getMotDePasse(): ?string { return $this->motDePasse; }
    public function setMotDePasse(string $motDePasse): static { $this->motDePasse = $motDePasse; return $this; }
    public function getRole(): string { return $this->role; }
    public function setRole(string $role): static { $this->role = $role; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }

    public function getRoles(): array
    {
        return ['ROLE_' . $this->role, 'ROLE_USER'];
    }

    public function getPassword(): string { return $this->motDePasse ?? ''; }
    public function getUserIdentifier(): string { return $this->email ?? ''; }
    public function eraseCredentials(): void {}

    public function getAffectations(): Collection { return $this->affectations; }
}
