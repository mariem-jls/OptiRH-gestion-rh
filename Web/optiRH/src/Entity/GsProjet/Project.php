<?php

namespace App\Entity\GsProjet;
use Symfony\Component\Validator\Constraints as Assert;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\GsProjet\ProjectRepository;
use App\Entity\User; // Ajout du bon namespace pour User

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\Table(name: 'projects')]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Le nom du projet est obligatoire")]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: "Le nom doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères"
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-Z0-9À-ÿ\s\-\_\.\,]+$/",
        message: "Caractères autorisés : lettres, chiffres, espaces, -_,."
    )]
    private ?string $nom = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\NotBlank(message: "La description est obligatoire")]
    #[Assert\Length(
        min: 20,
        max: 1000,
        minMessage: "La description doit contenir au moins {{ limit }} caractères",
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères"
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-Z0-9À-ÿ\s\-\_\.\,\!\?\'\"]+$/",
        message: "Caractères spéciaux non autorisés"
    )]
    private ?string $description = null;
    public const STATUS_ACTIVE = 'Actif';
    public const STATUS_INACTIVE = 'En Cour';
    public const STATUS_COMPLETED = 'Terminé';
    public const STATUS_DELAYED = 'En retard';
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Assert\NotBlank(message: "Le statut est obligatoire")]
    #[Assert\Choice(
        choices: [
            self::STATUS_ACTIVE,
            self::STATUS_INACTIVE,
            self::STATUS_COMPLETED,
            self::STATUS_DELAYED
        ],
        message: "Statut invalide"
    )]
    private ?string $status = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "created_by_id", referencedColumnName: "id", nullable: false)]
    private User $createdBy;

    #[ORM\OneToMany(mappedBy: 'project', targetEntity: Mission::class)]
    private Collection $missions;
   



    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->missions = new ArrayCollection();
    }

    // Getters/Setters...

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getMissions(): Collection
    {
        return $this->missions;
    }

    public function addMission(Mission $mission): static
    {
        if (!$this->missions->contains($mission)) {
            $this->missions->add($mission);
            $mission->setProject($this);
        }
        return $this;
    }

    public function removeMission(Mission $mission): static
    {
        if ($this->missions->removeElement($mission)) {
            if ($mission->getProject() === $this) {
                $mission->setProject(null);
            }
        }
        return $this;
    }

    public function getStatus(): string
    {
        if ($this->status === null) {
            $this->updateStatus();
        }
        return $this->status;
    }
    
    public function updateStatus(): void
    {
        $now = new \DateTime();
        $status = 'Actif';
    
        if ($this->missions->count() === 0) {
            $status = 'Inactif';
        } else {
            $allDone = true;
            $hasOverdue = false;
    
            foreach ($this->missions as $mission) {
                if ($mission->getStatus() !== 'Done') {
                    $allDone = false;
                }
                if ($mission->getDateTerminer() < $now && $mission->getStatus() !== 'Done') {
                    $hasOverdue = true;
                    break;
                }
            }
    
            if ($hasOverdue) {
                $status = 'En retard';
            } elseif ($allDone) {
                $status = 'Terminé';
            }
        }
    
        $this->status = $status;
    }
    public function setStatus(?string $status): static
    {
        $this->status = $status;
        return $this;
    }
    public function getProgress(): int
    {
        $missions = $this->getMissions();
        
        if ($missions->isEmpty()) {
            return 0;
        }

        $totalMissions = $missions->count();
        $completedMissions = $missions->filter(function(Mission $mission) {
            return $mission->getStatus() === 'Done';
        })->count();

        return (int) round(($completedMissions / $totalMissions) * 100);
    }
   

    public static function getStatusChoices(): array
    {
        return [
            'Actif' => self::STATUS_ACTIVE,
            'Inactif' => self::STATUS_INACTIVE,
            'Terminé' => self::STATUS_COMPLETED,
            'En retard' => self::STATUS_DELAYED,
        ];
    }
    
}
