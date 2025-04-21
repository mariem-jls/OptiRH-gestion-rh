<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\DemandeMatchingRepository;

#[ORM\Entity(repositoryClass: DemandeMatchingRepository::class)]
#[ORM\Table(name: "demande_matching")]
class DemandeMatching
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Demande::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private Demande $demande;

    #[ORM\ManyToOne(targetEntity: Offre::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private Offre $offre;

    #[ORM\Column(type: "json", nullable: true)]
    private ?array $cvEmbedding = null;

    #[ORM\Column(type: "json", nullable: true)]
    private ?array $offreEmbedding = null;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $matchingScore = null;

    #[ORM\Column(type: "datetime")]
    private \DateTime $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDemande(): Demande
    {
        return $this->demande;
    }

    public function setDemande(Demande $demande): void
    {
        $this->demande = $demande;
    }

    public function getOffre(): Offre
    {
        return $this->offre;
    }

    public function setOffre(Offre $offre): void
    {
        $this->offre = $offre;
    }

    public function getCvEmbedding(): ?array
    {
        return $this->cvEmbedding;
    }

    public function setCvEmbedding(?array $cvEmbedding): void
    {
        $this->cvEmbedding = $cvEmbedding;
    }

    public function getOffreEmbedding(): ?array
    {
        return $this->offreEmbedding;
    }

    public function setOffreEmbedding(?array $offreEmbedding): void
    {
        $this->offreEmbedding = $offreEmbedding;
    }

    public function getMatchingScore(): ?float
    {
        return $this->matchingScore;
    }

    public function setMatchingScore(?float $matchingScore): void
    {
        $this->matchingScore = $matchingScore;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}