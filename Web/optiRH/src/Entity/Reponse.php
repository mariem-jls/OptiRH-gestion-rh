<?php
// src/Entity/Reponse.php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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
        max: 5000,
        minMessage: "La réponse doit contenir au moins {{ limit }} caractères",
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

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $commentaire = null;

    public function __construct()
    {
        $this->date = new \DateTime();
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
    public function getDate(): ?\DateTimeInterface { return $this->date; }
    public function setDate(\DateTimeInterface $date): self { $this->date = $date; return $this; }
    public function getReclamation(): ?Reclamation { return $this->reclamation; }
    public function setReclamation(?Reclamation $reclamation): self { $this->reclamation = $reclamation; return $this; }
    public function getRating(): int { return $this->rating; }

    public function setRating(int $rating): self
    {
        $this->rating = $rating;

        if ($rating >= 4 && $this->reclamation) {
            $this->reclamation->setStatus(Reclamation::STATUS_RESOLVED);
        }

        return $this;
    }
    
    public function getCommentaire(): ?string { return $this->commentaire; }
    public function setCommentaire(?string $commentaire): self { $this->commentaire = $commentaire; return $this; }
    
    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, $payload)
    {
        // Validation supplémentaire si nécessaire
        if (strpos($this->description, 'spam') !== false) {
            $context->buildViolation('La réponse contient des termes non autorisés.')
                ->atPath('description')
                ->addViolation();
        }
    }


}