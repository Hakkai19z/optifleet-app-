<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use App\Repository\ReservationRepository;
use App\Validator\CreneauDisponible;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Table(name: 'reservation')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    normalizationContext: ['groups' => ['reservation:read']],
    denormalizationContext: ['groups' => ['reservation:write']],
    operations: [
        new GetCollection(security: "is_granted('ROLE_CONDUCTEUR')"),
        new Post(security: "is_granted('ROLE_CONDUCTEUR')"),
        new Get(security: "is_granted('ROLE_CONDUCTEUR')"),
        new Patch(security: "is_granted('ROLE_CONDUCTEUR')"),
        new Delete(security: "is_granted('ROLE_GESTIONNAIRE')"),
    ]
)]
#[ApiFilter(SearchFilter::class, properties: ['vehicule' => 'exact', 'conducteur' => 'exact', 'statut' => 'exact'])]
#[CreneauDisponible]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['reservation:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotBlank]
    #[Groups(['reservation:read', 'reservation:write'])]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotBlank]
    #[Assert\GreaterThan(propertyPath: 'dateDebut', message: 'La fin doit être après le début')]
    #[Groups(['reservation:read', 'reservation:write'])]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: ['en_attente', 'confirmee', 'annulee', 'terminee'])]
    #[Groups(['reservation:read', 'reservation:write'])]
    private ?string $statut = 'en_attente';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['reservation:read', 'reservation:write'])]
    private ?string $motif = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['reservation:read', 'reservation:write'])]
    private ?Utilisateur $conducteur = null;

    #[ORM\ManyToOne(targetEntity: Vehicule::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['reservation:read', 'reservation:write'])]
    private ?Vehicule $vehicule = null;

    #[ORM\Column]
    #[Groups(['reservation:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getDateDebut(): ?\DateTimeInterface { return $this->dateDebut; }
    public function setDateDebut(\DateTimeInterface $dateDebut): static { $this->dateDebut = $dateDebut; return $this; }
    public function getDateFin(): ?\DateTimeInterface { return $this->dateFin; }
    public function setDateFin(\DateTimeInterface $dateFin): static { $this->dateFin = $dateFin; return $this; }
    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }
    public function getMotif(): ?string { return $this->motif; }
    public function setMotif(?string $motif): static { $this->motif = $motif; return $this; }
    public function getConducteur(): ?Utilisateur { return $this->conducteur; }
    public function setConducteur(?Utilisateur $conducteur): static { $this->conducteur = $conducteur; return $this; }
    public function getVehicule(): ?Vehicule { return $this->vehicule; }
    public function setVehicule(?Vehicule $vehicule): static { $this->vehicule = $vehicule; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }

    public function isAnnulee(): bool
    {
        return $this->statut === 'annulee';
    }
}
