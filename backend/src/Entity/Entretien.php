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
use App\Repository\EntretienRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EntretienRepository::class)]
#[ORM\Table(name: 'entretien')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    normalizationContext: ['groups' => ['entretien:read']],
    denormalizationContext: ['groups' => ['entretien:write']],
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
class Entretien
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['entretien:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: ['revision', 'vidange', 'CT', 'freins', 'pneus', 'autre'])]
    #[Groups(['entretien:read', 'entretien:write'])]
    private ?string $type = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank]
    #[Groups(['entretien:read', 'entretien:write'])]
    private ?\DateTimeInterface $dateRealise = null;

    #[ORM\Column(type: 'date', nullable: true)]
    #[Groups(['entretien:read', 'entretien:write'])]
    private ?\DateTimeInterface $dateProchaine = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\PositiveOrZero]
    #[Groups(['entretien:read', 'entretien:write'])]
    private ?int $kmProchaine = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero]
    #[Groups(['entretien:read', 'entretien:write'])]
    private ?string $cout = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['entretien:read', 'entretien:write'])]
    private ?string $notes = null;

    #[ORM\ManyToOne(targetEntity: Vehicule::class, inversedBy: 'entretiens')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['entretien:read', 'entretien:write'])]
    private ?Vehicule $vehicule = null;

    #[ORM\Column]
    #[Groups(['entretien:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }
    public function getDateRealise(): ?\DateTimeInterface { return $this->dateRealise; }
    public function setDateRealise(\DateTimeInterface $dateRealise): static { $this->dateRealise = $dateRealise; return $this; }
    public function getDateProchaine(): ?\DateTimeInterface { return $this->dateProchaine; }
    public function setDateProchaine(?\DateTimeInterface $dateProchaine): static { $this->dateProchaine = $dateProchaine; return $this; }
    public function getKmProchaine(): ?int { return $this->kmProchaine; }
    public function setKmProchaine(?int $kmProchaine): static { $this->kmProchaine = $kmProchaine; return $this; }
    public function getCout(): ?string { return $this->cout; }
    public function setCout(?string $cout): static { $this->cout = $cout; return $this; }
    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }
    public function getVehicule(): ?Vehicule { return $this->vehicule; }
    public function setVehicule(?Vehicule $vehicule): static { $this->vehicule = $vehicule; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }

    public function isEchu(): bool
    {
        $now = new \DateTime();
        if ($this->dateProchaine !== null && $this->dateProchaine < $now) {
            return true;
        }
        if ($this->kmProchaine !== null && $this->vehicule !== null
            && $this->kmProchaine < $this->vehicule->getKilometrage()) {
            return true;
        }
        return false;
    }
}
