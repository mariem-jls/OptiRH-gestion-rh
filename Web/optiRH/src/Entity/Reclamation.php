<?php
// src/Entity/Reclamation.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: "App\Repository\ReclamationRepository")]
class Reclamation
{
    public const STATUS_PENDING = 'En attente';
    public const STATUS_IN_PROGRESS = 'En cours';
    public const STATUS_RESOLVED = 'Résolue';

    // Types de réclamation
    public const TYPE_SALAIRE = 'Salaire';
    public const TYPE_REMUNERATION = 'Rémunération';
    public const TYPE_CONGES = 'Congés';
    public const TYPE_RELATIONS_PRO = 'Relations professionnelles';
    public const TYPE_CONDITIONS = 'Conditions de travail';
    public const TYPE_AUTRE = 'Autre';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: "text")]
    #[Assert\NotBlank(message: "La description ne peut pas être vide")]
    #[Assert\Length(
        min: 10,
        max: 5000,
        minMessage: "La description doit contenir au moins {{ limit }} caractères",
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    private ?string $status = self::STATUS_PENDING;

    #[ORM\Column(type: "datetime")]
    #[Assert\NotBlank(message: "La date est obligatoire")]
    private ?\DateTimeInterface $date;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le type de réclamation est obligatoire")]
    #[Assert\Choice(
        choices: [
            self::TYPE_SALAIRE,
            self::TYPE_REMUNERATION,
            self::TYPE_CONGES,
            self::TYPE_RELATIONS_PRO,
            self::TYPE_CONDITIONS,
            self::TYPE_AUTRE
        ],
        message: "Veuillez sélectionner un type de réclamation valide"
    )]
    private ?string $type = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "reclamations")]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?User $utilisateur = null;

    #[ORM\OneToMany(mappedBy: "reclamation", targetEntity: Reponse::class, cascade: ["persist", "remove"], orphanRemoval: true)]
    private Collection $reponses;

    public function __construct()
    {
        $this->date = new \DateTime();
        $this->reponses = new ArrayCollection();
    }

    // Getters et Setters
    public function getId(): ?int { return $this->id; }
    
    public function getDescription(): ?string
    {
        return $this->description;
    }
    
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }
    
    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $status): self { $this->status = $status; return $this; }
    
    public function getDate(): ?\DateTimeInterface { return $this->date; }
    public function setDate(\DateTimeInterface $date): self { $this->date = $date; return $this; }
    
    public function getUtilisateur(): ?User { return $this->utilisateur; }
    public function setUtilisateur(?User $utilisateur): self { $this->utilisateur = $utilisateur; return $this; }
    
    public function getReponses(): Collection { return $this->reponses; }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public static function getTypeChoices(): array
    {
        return [
            'Salaire' => self::TYPE_SALAIRE,
            'Rémunération' => self::TYPE_REMUNERATION,
            'Congés' => self::TYPE_CONGES,
            'Relations professionnelles' => self::TYPE_RELATIONS_PRO,
            'Conditions de travail' => self::TYPE_CONDITIONS,
            'Autre' => self::TYPE_AUTRE,
        ];
    }

    public function addReponse(Reponse $reponse): self
    {
        if (!$this->reponses->contains($reponse)) {
            $this->reponses[] = $reponse;
            $reponse->setReclamation($this);
        }
        return $this;
    }

    public function removeReponse(Reponse $reponse): self
    {
        if ($this->reponses->removeElement($reponse)) {
            if ($reponse->getReclamation() === $this) {
                $reponse->setReclamation(null);
            }
        }
        return $this;
    }

    public static function getStatusChoices(): array
    {
        return [
            'En attente' => self::STATUS_PENDING,
            'En cours' => self::STATUS_IN_PROGRESS,
            'Résolue' => self::STATUS_RESOLVED,
        ];
    }

    public static function getStatusColor(string $status): string
    {
        return match($status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_IN_PROGRESS => 'info',
            self::STATUS_RESOLVED => 'success',
            default => 'secondary'
        };
    }

    public static function getStatusIcon(string $status): string
    {
        $icons = [
            self::STATUS_PENDING => '⏳',
            self::STATUS_IN_PROGRESS => '🔄',
            self::STATUS_RESOLVED => '✅'
        ];

        return $icons[$status] ?? '';
    }
    
    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, $payload)
    {
        // Validation supplémentaire si nécessaire
        if (strpos($this->description, 'spam') !== false) {
            $context->buildViolation('La description contient des termes non autorisés.')
                ->atPath('description')
                ->addViolation();
        }
    }
    #[ORM\Column(type: "float", nullable: true)]
    private ?float $sentimentScore = null;

    #[ORM\Column(type: "string", length: 20, nullable: true)]
    private ?string $sentimentLabel = null;

    // ... getters et setters ...

    public function getSentimentScore(): ?float
    {
        return $this->sentimentScore;
    }

    public function setSentimentScore(?float $sentimentScore): self
    {
        $this->sentimentScore = $sentimentScore;
        return $this;
    }

    public function getSentimentLabel(): ?string
    {
        return $this->sentimentLabel;
    }

    public function setSentimentLabel(?string $sentimentLabel): self
    {
        $this->sentimentLabel = $sentimentLabel;
        return $this;
    }
}