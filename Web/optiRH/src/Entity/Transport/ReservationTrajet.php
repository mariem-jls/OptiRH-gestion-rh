<?php

namespace App\Entity\Transport;

use App\Entity\User; // Correct namespace
use App\Entity\Transport\Vehicule;
use App\Entity\Transport\Trajet;
use App\Repository\Transport\ReservationTrajetRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationTrajetRepository::class)]
class ReservationTrajet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $disponibilite = null;

    #[ORM\ManyToOne(targetEntity: Vehicule::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Vehicule $vehicule = null;

    #[ORM\ManyToOne(targetEntity: Trajet::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Trajet $trajet = null;

    // Corrected the targetEntity and type declaration here
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null; // Changed from Users to User

    // Getters and setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDisponibilite(): ?string
    {
        return $this->disponibilite;
    }

    public function setDisponibilite(string $disponibilite): self
    {
        $this->disponibilite = $disponibilite;
        return $this;
    }

    public function getVehicule(): ?Vehicule
    {
        return $this->vehicule;
    }

    public function setVehicule(?Vehicule $vehicule): self
    {
        $this->vehicule = $vehicule;
        return $this;
    }

    public function getTrajet(): ?Trajet
    {
        return $this->trajet;
    }

    public function setTrajet(?Trajet $trajet): self
    {
        $this->trajet = $trajet;
        return $this;
    }

    // Corrected the return type here
    public function getUser(): ?User
    {
        return $this->user;
    }

    // Corrected the parameter type here
    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }
}