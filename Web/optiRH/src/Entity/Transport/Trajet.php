<?php

namespace App\Entity\Transport;

use App\Repository\Transport\TrajetRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TrajetRepository::class)]
class Trajet
{
    public function __construct()
    {
        $this->vehicules = new ArrayCollection();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le type de trajet est obligatoire')]
    #[Assert\Choice(['Urbain', 'Interurbain'], message: 'Le type doit être soit Urbain soit Interurbain')]
    private $type;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'La station est obligatoire')]
    #[Assert\Length(
        max: 20,
        min:3,
        maxMessage: 'La station ne doit pas dépasser {{ limit }} caractères',
        minMessage: 'Lastation doit contenir au moins {{ limit }} caractères'

    )]
    private $station;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le point de départ est obligatoire')]
    #[Assert\Length(
        max: 10,
        min:3,
        maxMessage: 'Le point de départ ne doit pas dépasser {{ limit }} caractères',
        minMessage: 'Le point de départ doit contenir au moins {{ limit }} caractères'

    )]
    private $depart;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le point d\'arrivée est obligatoire')]
    #[Assert\Length(
        max: 10,
        min: 3,
        maxMessage: 'Le point d\'arrivée ne doit pas dépasser {{ limit }} caractères',
        minMessage: 'Le point d\'arrivée doit contenir au moins {{ limit }} caractères'

    )]
    private $arrive;

    #[ORM\Column(type: 'float')]
    #[Assert\NotBlank(message: 'La longitude de départ est obligatoire')]
    #[Assert\Type(
        type: 'float',
        message: 'La longitude doit être un nombre décimal'
    )]
    private $longitudeDepart;

    #[ORM\Column(type: 'float')]
    #[Assert\NotBlank(message: 'La latitude de départ est obligatoire')]
    #[Assert\Type(
        type: 'float',
        message: 'La latitude doit être un nombre décimal'
    )]
    private $latitudeDepart;

    #[ORM\Column(type: 'float')]
    #[Assert\NotBlank(message: 'La longitude d\'arrivée est obligatoire')]
    #[Assert\Type(
        type: 'float',
        message: 'La longitude doit être un nombre décimal'
    )]
    private $longitudeArrivee;

    #[ORM\Column(type: 'float')]
    #[Assert\NotBlank(message: 'La latitude d\'arrivée est obligatoire')]
    #[Assert\Type(
        type: 'float',
        message: 'La latitude doit être un nombre décimal'
    )]
    private $latitudeArrivee;

    #[ORM\OneToMany(mappedBy: 'trajet', targetEntity: Vehicule::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $vehicules;

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

    public function getStation(): ?string
    {
        return $this->station;
    }

    public function setStation(string $station): self
    {
        $this->station = $station;
        return $this;
    }

    public function getDepart(): ?string
    {
        return $this->depart;
    }

    public function setDepart(string $depart): self
    {
        $this->depart = $depart;
        return $this;
    }

    public function getArrive(): ?string
    {
        return $this->arrive;
    }

    public function setArrive(string $arrive): self
    {
        $this->arrive = $arrive;
        return $this;
    }

    public function getLongitudeDepart(): ?float
    {
        return $this->longitudeDepart;
    }

    public function setLongitudeDepart(?float $longitudeDepart): self
    {
        $this->longitudeDepart = $longitudeDepart;
        return $this;
    }

    public function getLatitudeDepart(): ?float
    {
        return $this->latitudeDepart;
    }

    public function setLatitudeDepart(?float $latitudeDepart): self
    {
        $this->latitudeDepart = $latitudeDepart;
        return $this;
    }

    public function getLongitudeArrivee(): ?float
    {
        return $this->longitudeArrivee;
    }

    public function setLongitudeArrivee(?float $longitudeArrivee): self
    {
        $this->longitudeArrivee = $longitudeArrivee;
        return $this;
    }

    public function getLatitudeArrivee(): ?float
    {
        return $this->latitudeArrivee;
    }

    public function setLatitudeArrivee(?float $latitudeArrivee): self
    {
        $this->latitudeArrivee = $latitudeArrivee;
        return $this;
    }

    public function getVehicules(): Collection
    {
        return $this->vehicules;
    }
    
    public function addVehicule(Vehicule $vehicule): self
    {
        if (!$this->vehicules->contains($vehicule)) {
            $this->vehicules[] = $vehicule;
            $vehicule->setTrajet($this);
        }
        return $this;
    }
    
    public function removeVehicule(Vehicule $vehicule): self
    {
        if ($this->vehicules->removeElement($vehicule)) {
            // set the owning side to null (unless already changed)
            if ($vehicule->getTrajet() === $this) {
                $vehicule->setTrajet(null);
            }
        }
        return $this;
    }
}