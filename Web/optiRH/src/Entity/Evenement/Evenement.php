<?php

namespace App\Entity\Evenement;

use App\Repository\Evenement\EvenementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: EvenementRepository::class)]
class Evenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_evenement', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: "Le titre doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $titre = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le lieu est obligatoire.')]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: "Le lieu doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le lieu ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $lieu = null;
    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "La description est obligatoire")]

    private ?string $description = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Le prix est obligatoire")]
    #[Assert\PositiveOrZero(message: "Le prix doit être positif ou zéro")]
    private ?float $prix = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: "La date de début est obligatoire")]
    #[Assert\GreaterThanOrEqual(
        "today",
        message: "La date de début ne peut pas être dans le passé"
    )]
    private ?\DateTimeInterface $date_debut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: "La date de fin est obligatoire")]

    private ?\DateTimeInterface $date_fin = null;
    #[Assert\Callback]
        public static function validateDates(Evenement $evenement, ExecutionContextInterface $context): void
        {
            // Vérifier si la date de fin est après la date de début
            if ($evenement->getDateDebut() && $evenement->getDateFin()) {
                if ($evenement->getDateFin() < $evenement->getDateDebut()) {
                    $context->buildViolation("La date de fin doit être après la date de début.")
                        ->atPath('date_fin')
                        ->addViolation();
                }
            }
        }

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'image est obligatoire")]

    private ?string $image = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Assert\NotBlank(message: "L'heure est obligatoire")]

    private ?\DateTimeInterface $heure = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "La longitude est obligatoire")]
    #[Assert\Range(min: -180, max: 180, notInRangeMessage: "La longitude doit être comprise entre -180 et 180")] 
    private ?float $longitude = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "La latitude est obligatoire")]
    #[Assert\Range(min: -90, max: 90, notInRangeMessage: "La latitude doit être comprise entre -90 et 90")] 


    private ?float $latitude = null;

    #[ORM\Column(length: 20, nullable: true)]  
    private ?string $status = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le type est obligatoire")]

    private ?string $type = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La modalité est obligatoire")]

    private ?string $modalite = null;

    #[ORM\OneToMany(mappedBy: 'evenement', targetEntity: ReservationEvenement::class)]

    private Collection $ReservationEvenement;

    public function __construct()
    {
        $this->ReservationEvenement = new ArrayCollection();
    }

    

    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(?string $lieu): static
    {
        $this->lieu = $lieu;

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

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(?float $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->date_debut;
    }

    public function setDateDebut(\DateTimeInterface $date_debut): static
    {
        $this->date_debut = $date_debut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->date_fin;
    }

    public function setDateFin(\DateTimeInterface $date_fin): static
    {
        $this->date_fin = $date_fin;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getHeure(): ?\DateTimeInterface
    {
        return $this->heure;
    }

    public function setHeure(?\DateTimeInterface $heure): static
    {
        $this->heure = $heure;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status ?? 'INCONNU'; 
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getModalite(): ?string
    {
        return $this->modalite;
    }

    public function setModalite(string $modalite): static
    {
        $this->modalite = $modalite;

        return $this;
    }

    // Dans ton entité Evenement
public function updateStatus(): void
{
    $currentDate = new \DateTime(); // Heure actuelle

    if ($currentDate >= $this->date_debut && $currentDate <= $this->date_fin) {
        $this->status = 'EN_COURS';
    } elseif ($currentDate < $this->date_debut) {
        $this->status = 'A_VENIR';
    } elseif ($currentDate > $this->date_fin) {
        $this->status = 'TERMINE';
    }
}


    /**
     * @return Collection<int, ReservationEvenement>
     */
    public function getReservationEvenements(): Collection
    {
        return $this->ReservationEvenement;
    }

    public function addReservationEvenement(ReservationEvenement $reservationEvenement): static
    {
        if (!$this->ReservationEvenement->contains($reservationEvenement)) {
            $this->ReservationEvenement->add($reservationEvenement);
            $reservationEvenement->setEvenement($this);
        }

        return $this;
    }

    public function removeReservationEvenement(reservationEvenement $reservationEvenement): static
    {
        if ($this->ReservationEvenement->removeElement($reservationEvenement)) {
            // set the owning side to null (unless already changed)
            if ($reservationEvenement->getEvenement() === $this) {
                $reservationEvenement->setEvenement(null);
            }
        }

        return $this;
    }
}
