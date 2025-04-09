<?php
// src/Entity/Reponse.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: "App\Repository\ReponseRepository")]
class Reponse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: "text")]
    #[Assert\NotBlank(message: "La réponse ne peut pas être vide")]
    #[Assert\Length(
        min: 5,
        minMessage: "La réponse doit contenir au moins {{ limit }} caractères",
        max: 5000,
        maxMessage: "La réponse ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $description = null;

    #[ORM\Column(type: "datetime")]
    private ?\DateTimeInterface $date;

    #[ORM\ManyToOne(targetEntity: Reclamation::class, inversedBy: "reponses")]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?Reclamation $reclamation = null;

    #[ORM\Column(type: "integer")]
    private int $rating = 0;

    public function __construct()
    {
        $this->date = new \DateTime();
    }

    // Getters et Setters
    public function getId(): ?int { return $this->id; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(string $description): self { $this->description = $description; return $this; }
    public function getDate(): ?\DateTimeInterface { return $this->date; }
    public function setDate(\DateTimeInterface $date): self { $this->date = $date; return $this; }
    public function getReclamation(): ?Reclamation { return $this->reclamation; }
    public function setReclamation(?Reclamation $reclamation): self { $this->reclamation = $reclamation; return $this; }
    public function getRating(): int { return $this->rating; }
    public function setRating(int $rating): self { $this->rating = $rating; return $this; }
}