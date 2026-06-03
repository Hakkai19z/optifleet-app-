<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use App\Repository\VehiculeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VehiculeRepository::class)]
#[ORM\Table(name: 'vehicule')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    normalizationContext: ['groups' => ['vehicule:read']],
    denormalizationContext: ['groups' => ['vehicule:write']],
    operations: [
        new GetCollection(),
        new Post(security: "is_granted('ROLE_GESTIONNAIRE')"),
        new Get(),
        new Put(security: "is_granted('ROLE_GESTIONNAIRE')"),
        new Patch(security: "is_granted('ROLE_GESTIONNAIRE')"),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ]
)]
#[ApiFilter(SearchFilter::class, properties: ['statut' => 'exact', 'categorie' => 'exact'])]
class Vehicule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['vehicule:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 20, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^[A-Z]{2}-[0-9]{3}-[A-Z]{2}$/',
        message: "L'immatriculation doit suivre le format AA-000-AA"
    )]
    #[Groups(['vehicule:read', 'vehicule:write', 'affectation:read'])]
    private ?string $immatriculation = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['vehicule:read', 'vehicule:write', 'affectation:read'])]
    private ?string $marque = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['vehicule:read', 'vehicule:write', 'affectation:read'])]
    private ?string $modele = null;

    #[ORM\Column(type: 'smallint')]
    #[Assert\NotBlank]
    #[Assert\Range(min: 1900, max: 2030)]
    #[Groups(['vehicule:read', 'vehicule:write'])]
    private ?int $annee = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    #[Groups(['vehicule:read', 'vehicule:write'])]
    private int $kilometrage = 0;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    #[Groups(['vehicule:read', 'vehicule:write'])]
    private ?int $quotaKmAnnuel = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: ['disponible', 'en_mission', 'maintenance', 'inactif'])]
    #[Groups(['vehicule:read', 'vehicule:write'])]
    private string $statut = 'disponible';

    #[ORM\ManyToOne(targetEntity: Categorie::class, inversedBy: 'vehicules')]
    #[Groups(['vehicule:read', 'vehicule:write'])]
    private ?Categorie $categorie = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 8, nullable: true)]
    #[Groups(['vehicule:read', 'vehicule:write'])]
    private ?string $latitude = null;

    #[ORM\Column(type: 'decimal', precision: 11, scale: 8, nullable: true)]
    #[Groups(['vehicule:read', 'vehicule:write'])]
    private ?string $longitude = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['vehicule:read', 'vehicule:write'])]
    private ?string $adresse = null;

    #[ORM\Column]
    #[Groups(['vehicule:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['vehicule:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'vehicule', targetEntity: Affectation::class)]
    private Collection $affectations;

    #[ORM\OneToMany(mappedBy: 'vehicule', targetEntity: Entretien::class)]
    private Collection $entretiens;

    #[ORM\OneToMany(mappedBy: 'vehicule', targetEntity: Alerte::class)]
    private Collection $alertes;

    #[ORM\OneToMany(mappedBy: 'vehicule', targetEntity: Plein::class)]
    private Collection $pleins;

    #[ORM\OneToMany(mappedBy: 'vehicule', targetEntity: Reservation::class)]
    private Collection $reservations;

    #[ORM\OneToMany(mappedBy: 'vehicule', targetEntity: Document::class)]
    private Collection $documents;

    public function __construct()
    {
        $this->affectations = new ArrayCollection();
        $this->entretiens = new ArrayCollection();
        $this->alertes = new ArrayCollection();
        $this->pleins = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->documents = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getImmatriculation(): ?string { return $this->immatriculation; }
    public function setImmatriculation(string $immatriculation): static { $this->immatriculation = $immatriculation; return $this; }
    public function getMarque(): ?string { return $this->marque; }
    public function setMarque(string $marque): static { $this->marque = $marque; return $this; }
    public function getModele(): ?string { return $this->modele; }
    public function setModele(string $modele): static { $this->modele = $modele; return $this; }
    public function getAnnee(): ?int { return $this->annee; }
    public function setAnnee(int $annee): static { $this->annee = $annee; return $this; }
    public function getKilometrage(): int { return $this->kilometrage; }
    public function setKilometrage(int $kilometrage): static { $this->kilometrage = $kilometrage; return $this; }
    public function getQuotaKmAnnuel(): ?int { return $this->quotaKmAnnuel; }
    public function setQuotaKmAnnuel(?int $quotaKmAnnuel): static { $this->quotaKmAnnuel = $quotaKmAnnuel; return $this; }

    public function getPourcentageQuota(): ?float
    {
        if ($this->quotaKmAnnuel === null || $this->quotaKmAnnuel === 0) {
            return null;
        }
        return round(($this->kilometrage / $this->quotaKmAnnuel) * 100, 1);
    }

    public function isQuotaDepasse(): bool
    {
        return $this->quotaKmAnnuel !== null && $this->kilometrage >= $this->quotaKmAnnuel;
    }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }
    public function getCategorie(): ?Categorie { return $this->categorie; }
    public function setCategorie(?Categorie $categorie): static { $this->categorie = $categorie; return $this; }
    public function getLatitude(): ?string { return $this->latitude; }
    public function setLatitude(?string $latitude): static { $this->latitude = $latitude; return $this; }
    public function getLongitude(): ?string { return $this->longitude; }
    public function setLongitude(?string $longitude): static { $this->longitude = $longitude; return $this; }
    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(?string $adresse): static { $this->adresse = $adresse; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function getAffectations(): Collection { return $this->affectations; }
    public function getEntretiens(): Collection { return $this->entretiens; }
    public function getAlertes(): Collection { return $this->alertes; }
    public function getPleins(): Collection { return $this->pleins; }
    public function getReservations(): Collection { return $this->reservations; }
    public function getDocuments(): Collection { return $this->documents; }

    public function isDisponible(): bool
    {
        return $this->statut === 'disponible';
    }
}
