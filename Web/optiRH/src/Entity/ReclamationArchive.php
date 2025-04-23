<?php
// src/Entity/ReclamationArchive.php

namespace App\Entity;

use App\Repository\ReclamationArchiveRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReclamationArchiveRepository::class)]
class ReclamationArchive
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $type = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $utilisateurNom = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $deletedAt = null;

    #[ORM\Column(type: 'string', length: 50)]
    private ?string $status = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $sentimentScore = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $sentimentLabel = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getUtilisateurNom(): ?string
    {
        return $this->utilisateurNom;
    }

    public function setUtilisateurNom(string $utilisateurNom): self
    {
        $this->utilisateurNom = $utilisateurNom;
        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(\DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

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