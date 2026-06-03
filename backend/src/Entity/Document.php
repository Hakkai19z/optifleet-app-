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
use ApiPlatform\Metadata\ApiFilter;
use App\Repository\DocumentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
#[ORM\Table(name: 'document')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    normalizationContext: ['groups' => ['document:read']],
    denormalizationContext: ['groups' => ['document:write']],
    operations: [
        new GetCollection(security: "is_granted('ROLE_CONDUCTEUR')"),
        new Post(security: "is_granted('ROLE_GESTIONNAIRE')"),
        new Get(security: "is_granted('ROLE_CONDUCTEUR')"),
        new Put(security: "is_granted('ROLE_GESTIONNAIRE')"),
        new Patch(security: "is_granted('ROLE_GESTIONNAIRE')"),
        new Delete(security: "is_granted('ROLE_GESTIONNAIRE')"),
    ]
)]
#[ApiFilter(SearchFilter::class, properties: ['vehicule' => 'exact', 'type' => 'exact'])]
class Document
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['document:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    #[Assert\Choice(choices: ['assurance', 'carte_grise', 'controle_technique', 'vignette', 'permis', 'autre'])]
    #[Groups(['document:read', 'document:write'])]
    private ?string $type = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['document:read', 'document:write'])]
    private ?string $numero = null;

    #[ORM\Column(type: 'date', nullable: true)]
    #[Groups(['document:read', 'document:write'])]
    private ?\DateTimeInterface $dateDelivrance = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank]
    #[Groups(['document:read', 'document:write'])]
    private ?\DateTimeInterface $dateExpiration = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['document:read', 'document:write'])]
    private ?string $notes = null;

    #[ORM\ManyToOne(targetEntity: Vehicule::class, inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['document:read', 'document:write'])]
    private ?Vehicule $vehicule = null;

    #[ORM\Column]
    #[Groups(['document:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }
    public function getNumero(): ?string { return $this->numero; }
    public function setNumero(?string $numero): static { $this->numero = $numero; return $this; }
    public function getDateDelivrance(): ?\DateTimeInterface { return $this->dateDelivrance; }
    public function setDateDelivrance(?\DateTimeInterface $dateDelivrance): static { $this->dateDelivrance = $dateDelivrance; return $this; }
    public function getDateExpiration(): ?\DateTimeInterface { return $this->dateExpiration; }
    public function setDateExpiration(\DateTimeInterface $dateExpiration): static { $this->dateExpiration = $dateExpiration; return $this; }
    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }
    public function getVehicule(): ?Vehicule { return $this->vehicule; }
    public function setVehicule(?Vehicule $vehicule): static { $this->vehicule = $vehicule; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }

    #[Groups(['document:read'])]
    public function getJoursAvantExpiration(): ?int
    {
        if ($this->dateExpiration === null) {
            return null;
        }
        $now = new \DateTime('today');
        $diff = $now->diff($this->dateExpiration);

        return (int) $diff->days * ($diff->invert === 1 ? -1 : 1);
    }

    #[Groups(['document:read'])]
    public function isExpire(): bool
    {
        return $this->dateExpiration !== null && $this->dateExpiration < new \DateTime('today');
    }
}
