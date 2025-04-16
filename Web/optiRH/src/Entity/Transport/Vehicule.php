<?php

namespace App\Entity\Transport;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: "App\Repository\Transport\VehiculeRepository")]
class Vehicule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    
    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'La disponibilité est obligatoire')]
    private $disponibilite;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le type de véhicule est obligatoire')]
    private $type;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank(message: 'Le nombre de places est obligatoire')]
    #[Assert\Positive(message: 'Le nombre de places doit être positif')]
    private $nbrplace;

    #[ORM\ManyToOne(targetEntity: Trajet::class, inversedBy: 'vehicules')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Trajet $trajet;

    #[ORM\OneToMany(mappedBy: 'vehicule', targetEntity: ReservationTrajet::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $reservations;

    #[ORM\Column(type: 'integer')]
    private $nbrReservation = 0;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
    }

    // Ajoutez ici TOUS les getters et setters pour chaque propriété
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

// Ajoutez ces méthodes à votre classe Vehicule

public function getType(): ?string
{
    return $this->type;
}

public function setType(string $type): self
{
    $this->type = $type;
    return $this;
}

public function getNbrplace(): ?int
{
    return $this->nbrplace;
}

public function setNbrplace(int $nbrplace): self
{
    $this->nbrplace = $nbrplace;
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

public function getNbrReservation(): ?int
{
    return $this->nbrReservation;
}

public function setNbrReservation(int $nbrReservation): self
{
    $this->nbrReservation = $nbrReservation;
    return $this;
}

/**
 * @return Collection|ReservationTrajet[]
 */
public function getReservations(): Collection
{
    return $this->reservations;
}

public function addReservation(ReservationTrajet $reservation): self
{
    if (!$this->reservations->contains($reservation)) {
        $this->reservations[] = $reservation;
        $reservation->setVehicule($this);
    }

    return $this;
}

public function removeReservation(ReservationTrajet $reservation): self
{
    if ($this->reservations->removeElement($reservation)) {
        // set the owning side to null (unless already changed)
        if ($reservation->getVehicule() === $this) {
            $reservation->setVehicule(null);
        }
    }

    return $this;
}}