<?php

namespace App\Entity;

use App\Entity\Demande;
use App\Repository\OffreRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\DBAL\Types\Types;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: OffreRepository::class)]
class Offre
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['offres'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Assert\NotBlank(message: "Le poste ne peut pas être vide.")]
    #[Assert\Length(max: 100, maxMessage: "Le poste ne peut pas dépasser {{ limit }} caractères.")]
    #[Groups(['offres'])]
    private ?string $poste = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(message: "La description ne peut pas être vide.")]
    #[Assert\Length(min: 10, minMessage: "La description doit contenir au moins {{ limit }} caractères.")]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Choice(choices: ['Active', 'En Attente', 'Brouillon'], message: "Statut invalide.")]
    private ?string $statut = 'Brouillon'; // Par défaut : Brouillon

    #[ORM\Column(
        type: Types::DATETIME_MUTABLE,
        columnDefinition: "TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
    )]
    #[Assert\NotBlank(message: "La date de création est requise.")]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Choice(choices: ['Télétravail', 'Présentiel', 'Hybride'], message: "Mode de travail invalide.")]
    #[Groups(['offres'])]
    private ?string $modeTravail = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Choice(choices: ['CDD', 'CDI', 'Freelance', 'Stage'], message: "Type du contrat invalide.")]
    #[Groups(['offres'])]
    private ?string $typeContrat = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Assert\NotBlank(message: "La localisation ne peut pas être vide.")]
    #[Groups(['offres'])]
    private ?string $localisation = null;
    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Choice(choices: ['Junior', 'Senior', 'Débutant'], message: "Niveau d'expérience invalide.")]
    private ?string $niveauExperience = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Positive(message: "Le nombre de postes doit être positif.")]
    #[Groups(['offres'])]
    private ?int $nbPostes = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\GreaterThanOrEqual("today", message: "La date d'expiration doit être aujourd'hui ou dans le futur.")]
    private ?\DateTimeInterface $dateExpiration = null;
    #[ORM\OneToMany(mappedBy: "offre", targetEntity: Demande::class, orphanRemoval: true)]
    private Collection $demandes;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPoste(): ?string
    {
        return $this->poste;
    }

    public function setPoste(?string $poste): static
    {
        $this->poste = $poste;

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

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getModeTravail(): ?string
    {
        return $this->modeTravail;
    }

    public function setModeTravail(?string $modeTravail): static
    {
        $this->modeTravail = $modeTravail;

        return $this;
    }

    public function getTypeContrat(): ?string
    {
        return $this->typeContrat;
    }

    public function setTypeContrat(?string $typeContrat): static
    {
        $this->typeContrat = $typeContrat;

        return $this;
    }

    public function getLocalisation(): ?string
    {
        return $this->localisation;
    }

    public function setLocalisation(?string $localisation): static
    {
        $this->localisation = $localisation;

        return $this;
    }

    public function getNiveauExperience(): ?string
    {
        return $this->niveauExperience;
    }

    public function setNiveauExperience(?string $niveauExperience): static
    {
        $this->niveauExperience = $niveauExperience;

        return $this;
    }

    public function getNbPostes(): ?int
    {
        return $this->nbPostes;
    }

    public function setNbPostes(?int $nbPostes): static
    {
        $this->nbPostes = $nbPostes;

        return $this;
    }

    public function getDateExpiration(): ?\DateTimeInterface
    {
        return $this->dateExpiration;
    }

    public function setDateExpiration(?\DateTimeInterface $dateExpiration): static
    {
        $this->dateExpiration = $dateExpiration;

        return $this;
    }
    public function __construct()
    {
        $this->demandes = new ArrayCollection();
        $this->dateCreation = new \DateTime(); // Date de création par défaut : aujourd'hui
        $this->statut = 'Brouillon';
    }
    public function getDemandes(): Collection
    {
        return $this->demandes;
    }

    public function addDemande(Demande $demande): static
    {
        if (!$this->demandes->contains($demande)) {
            $this->demandes[] = $demande;
            $demande->setOffre($this);
        }
        return $this;
    }

    public function removeDemande(Demande $demande): static
    {
        if ($this->demandes->removeElement($demande)) {
            if ($demande->getOffre() === $this) {
                $demande->setOffre(null);
            }
        }
        return $this;
    }
    public function getTimeAgo(): string
    {
        $now = new \DateTime();
        $diff = $now->diff($this->dateCreation);

        if ($diff->y > 0) {
            return $diff->y . ' year(s) ago';
        } elseif ($diff->m > 0) {
            return $diff->m . ' month(s) ago';
        } elseif ($diff->d > 0) {
            return $diff->d . ' day(s) ago';
        } elseif ($diff->h > 0) {
            return $diff->h . ' hour(s) ago';
        } elseif ($diff->i > 0) {
            return $diff->i . ' minute(s) ago';
        } else {
            return 'Just now';
        }
    }
    public function __toString(): string
    {
        return $this->poste ?? 'Offre sans poste';
    }

}
