<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use App\Repository\PleinRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PleinRepository::class)]
#[ORM\Table(name: 'plein')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    normalizationContext: ['groups' => ['plein:read']],
    denormalizationContext: ['groups' => ['plein:write']],
    operations: [
        new GetCollection(security: "is_granted('ROLE_CONDUCTEUR')"),
        new Post(security: "is_granted('ROLE_CONDUCTEUR')"),
        new Get(security: "is_granted('ROLE_CONDUCTEUR')"),
        new Put(security: "is_granted('ROLE_GESTIONNAIRE')"),
        new Patch(security: "is_granted('ROLE_GESTIONNAIRE')"),
        new Delete(security: "is_granted('ROLE_GESTIONNAIRE')"),
    ]
)]
#[ApiFilter(SearchFilter::class, properties: ['vehicule' => 'exact', 'typeCarburant' => 'exact'])]
#[ApiFilter(OrderFilter::class, properties: ['date' => 'DESC', 'kilometrage' => 'DESC'])]
class Plein
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['plein:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank]
    #[Groups(['plein:read', 'plein:write'])]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive(message: 'Le volume doit être positif')]
    #[Groups(['plein:read', 'plein:write'])]
    private ?string $litres = null;

    #[ORM\Column(type: 'decimal', precision: 6, scale: 3)]
    #[Assert\NotBlank]
    #[Assert\Positive(message: 'Le prix au litre doit être positif')]
    #[Groups(['plein:read', 'plein:write'])]
    private ?string $prixLitre = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    #[Groups(['plein:read', 'plein:write'])]
    private ?int $kilometrage = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: ['essence', 'diesel', 'gpl', 'electrique'])]
    #[Groups(['plein:read', 'plein:write'])]
    private ?string $typeCarburant = 'diesel';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['plein:read', 'plein:write'])]
    private ?string $notes = null;

    #[ORM\ManyToOne(targetEntity: Vehicule::class, inversedBy: 'pleins')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['plein:read', 'plein:write'])]
    private ?Vehicule $vehicule = null;

    #[ORM\Column]
    #[Groups(['plein:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getDate(): ?\DateTimeInterface { return $this->date; }
    public function setDate(\DateTimeInterface $date): static { $this->date = $date; return $this; }
    public function getLitres(): ?string { return $this->litres; }
    public function setLitres(string $litres): static { $this->litres = $litres; return $this; }
    public function getPrixLitre(): ?string { return $this->prixLitre; }
    public function setPrixLitre(string $prixLitre): static { $this->prixLitre = $prixLitre; return $this; }
    public function getKilometrage(): ?int { return $this->kilometrage; }
    public function setKilometrage(int $kilometrage): static { $this->kilometrage = $kilometrage; return $this; }
    public function getTypeCarburant(): ?string { return $this->typeCarburant; }
    public function setTypeCarburant(string $typeCarburant): static { $this->typeCarburant = $typeCarburant; return $this; }
    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }
    public function getVehicule(): ?Vehicule { return $this->vehicule; }
    public function setVehicule(?Vehicule $vehicule): static { $this->vehicule = $vehicule; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }

    #[Groups(['plein:read'])]
    public function getMontant(): float
    {
        return round((float) $this->litres * (float) $this->prixLitre, 2);
    }
}
