<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\JournalAuditRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Journal d'audit : trace horodatée des événements sensibles (connexions,
 * refus d'accès, affectations, suppressions de compte). En lecture seule, et
 * réservé à l'administrateur. Répond à l'exigence EF09 et à la catégorie A09
 * de l'OWASP Top 10:2025.
 */
#[ORM\Entity(repositoryClass: JournalAuditRepository::class)]
#[ORM\Table(name: 'journal_audit')]
#[ORM\Index(name: 'idx_audit_action', columns: ['action'])]
#[ORM\Index(name: 'idx_audit_created', columns: ['created_at'])]
#[ApiResource(
    normalizationContext: ['groups' => ['audit:read']],
    operations: [
        new GetCollection(security: "is_granted('ROLE_ADMIN')"),
        new Get(security: "is_granted('ROLE_ADMIN')"),
    ],
    order: ['createdAt' => 'DESC'],
    paginationItemsPerPage: 50,
)]
class JournalAudit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['audit:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(['audit:read'])]
    private string $action;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['audit:read'])]
    private ?string $cible = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['audit:read'])]
    private ?string $auteurEmail = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['audit:read'])]
    private ?string $auteurRole = null;

    #[ORM\Column(length: 45, nullable: true)]
    #[Groups(['audit:read'])]
    private ?string $adresseIp = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['audit:read'])]
    private ?string $details = null;

    #[ORM\Column]
    #[Groups(['audit:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $action)
    {
        $this->action = $action;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getCible(): ?string
    {
        return $this->cible;
    }

    public function setCible(?string $cible): static
    {
        $this->cible = $cible;

        return $this;
    }

    public function getAuteurEmail(): ?string
    {
        return $this->auteurEmail;
    }

    public function setAuteurEmail(?string $auteurEmail): static
    {
        $this->auteurEmail = $auteurEmail;

        return $this;
    }

    public function getAuteurRole(): ?string
    {
        return $this->auteurRole;
    }

    public function setAuteurRole(?string $auteurRole): static
    {
        $this->auteurRole = $auteurRole;

        return $this;
    }

    public function getAdresseIp(): ?string
    {
        return $this->adresseIp;
    }

    public function setAdresseIp(?string $adresseIp): static
    {
        $this->adresseIp = $adresseIp;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): static
    {
        $this->details = $details;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
