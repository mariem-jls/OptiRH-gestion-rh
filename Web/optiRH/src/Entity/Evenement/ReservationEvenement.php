<?php

namespace App\Entity\Evenement;

use App\Entity\User;
use App\Repository\Evenement\ReservationEvenementRepository;
use Symfony\Component\Validator\Constraints as Assert;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationEvenementRepository::class)]
class ReservationEvenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_participation')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le prénom ne peut pas être vide.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le prénom ne doit pas dépasser 20 caractères."
    )]
    private ?string $first_name = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom ne peut pas être vide.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le nom ne doit pas dépasser 20 caractères."
    )]
    private ?string $last_name = null;

   

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'email ne peut pas être vide.")]
    #[Assert\Email(message: "L'email '{{ value }}' n'est pas valide.")]
    #[Assert\Regex(
        pattern: "/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/",
        message: "L'email doit être au format exemple@esprit.tn"
    )]
    #[Assert\Length(
        max: 255,
        maxMessage: "L'email ne doit pas dépasser 13 caractères."
    )]
    private ?string $email = null;


    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le téléphone ne peut pas être vide.")]
    #[Assert\Regex(
        pattern: "/^\+[0-9]{1,4}[0-9]{7,14}$/",
        message: "Le numéro doit commencer par + (ex: +21612345678)"
    )]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le numéro de téléphone ne doit pas dépasser 255 caractères."
    )]
    private ?string $telephone = null;

    #[ORM\Column(type: "datetime", options: ["default" => "CURRENT_TIMESTAMP"])]
    private ?\DateTimeInterface $date_reservation = null;


    public function __construct()
{
    $this->date_reservation = new \DateTime();
}


    #[ORM\ManyToOne(inversedBy: 'reservationEvenements')]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;
    #[ORM\ManyToOne(inversedBy: 'reservationEvenements')]
    #[ORM\JoinColumn(name: 'id_evenement', referencedColumnName: 'id_evenement', nullable: false, onDelete: 'CASCADE')]
    private ?Evenement $Evenement = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(?string $first_name): static
    {
        $this->first_name = $first_name;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(?string $last_name): static
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getDateReservation(): ?\DateTimeInterface
    {
        return $this->date_reservation;
    }

    public function setDateReservation(\DateTimeInterface $date_reservation): static
    {
        $this->date_reservation = $date_reservation;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
    
    public function setUser(?User $user): static
    {
        $this->user = $user;
    
        return $this;
    }
    public function getEvenement(): ?Evenement
    {
        return $this->Evenement;
    }

    public function setEvenement(?Evenement $Evenement): static
    {
        $this->Evenement = $Evenement;

        return $this;
    }
}
