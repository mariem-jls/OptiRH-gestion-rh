<?php
namespace App\Entity;

use App\Entity\Offre;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: "demande")]
class Demande
{
    public const STATUT_EN_ATTENTE = 'EN_ATTENTE';
    public const STATUT_ACCEPTEE = 'ACCEPTEE';
    public const STATUT_REFUSEE = 'REFUSEE';
    public const SITUATION_ETUDIANT = 'Etudiant';
    public const SITUATION_EMPLOYE = 'Employé';
    public const SITUATION_AUTRE = 'Autre';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 20)]
    #[Assert\Choice(choices: [self::STATUT_EN_ATTENTE, self::STATUT_ACCEPTEE, self::STATUT_REFUSEE],message: "Le statut doit être En Attente, Acceptée ou Refusée.")]
    private string $statut = self::STATUT_EN_ATTENTE;

    #[ORM\Column(type: "datetime", options: ["default" => "CURRENT_TIMESTAMP"])]
    #[Assert\NotBlank(message: "La date est requise.")]
    private \DateTime $date;

    #[ORM\Column(type: "text", nullable: true)]
    #[Assert\Length(
        min: 10,
        minMessage: "La description doit contenir au moins {{ limit }} caractères."
    )]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Offre::class, inversedBy: "demandes")]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private Offre $offre;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $fichierPieceJointe = null;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank(message: "Le nom complet est requis.")]
    #[Assert\Length(max: 255, maxMessage: "Le nom complet ne peut pas dépasser {{ limit }} caractères.")]
    private string $nomComplet;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank(message: "L'email est requis.")]
    #[Assert\Email(message: "L'email doit être valide.")]
    #[Assert\Length(max: 255, maxMessage: "L'email ne peut pas dépasser {{ limit }} caractères.")]
    private string $email;

    #[ORM\Column(type: "string", length: 20)]
    #[Assert\NotBlank(message: "Le téléphone est requis.")]
    #[Assert\Length(max: 8, maxMessage: "Le téléphone doit avoir {{ limit }} caractères.")]
    #[Assert\Regex(
        pattern: "/^[0-9]{8}$/",
        message: "Le téléphone ne doit contenir que 8  chiffres."
    )]
    private string $telephone;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: "L'adresse ne peut pas dépasser {{ limit }} caractères.")]
    private ?string $adresse = null;

    #[ORM\Column(type: "date", nullable: true)]
    #[Assert\GreaterThanOrEqual("today", message: "La date de début disponible doit être aujourd'hui ou dans le futur.")]
    private ?\DateTimeInterface $dateDebutDisponible = null;

    #[ORM\Column(type: "string", length: 100, nullable: true)]
    #[Assert\Choice(
        choices: [self::SITUATION_ETUDIANT, self::SITUATION_EMPLOYE, self::SITUATION_AUTRE],
        message: "La situation actuelle doit être Etudiant, Employé ou Autre."
    )]
    private ?string $situationActuelle = null;

    public function __construct()
    {
        $this->date = new \DateTime();
        $this->statut = 'EN_ATTENTE';
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): void
    {
        $this->statut = $statut;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): void
    {
        $this->date = $date;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getOffre(): \App\Entity\Offre
    {
        return $this->offre;
    }

    public function setOffre(\App\Entity\Offre $offre): void
    {
        $this->offre = $offre;
    }

    public function getFichierPieceJointe(): ?string
    {
        return $this->fichierPieceJointe;
    }

    public function setFichierPieceJointe(?string $fichierPieceJointe): void
    {
        $this->fichierPieceJointe = $fichierPieceJointe;
    }

    public function getNomComplet(): string
    {
        return $this->nomComplet;
    }

    public function setNomComplet(string $nomComplet): void
    {
        $this->nomComplet = $nomComplet;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getTelephone(): string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): void
    {
        $this->telephone = $telephone;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): void
    {
        $this->adresse = $adresse;
    }

    public function getDateDebutDisponible(): ?\DateTimeInterface
    {
        return $this->dateDebutDisponible;
    }

    public function setDateDebutDisponible(?\DateTimeInterface $dateDebutDisponible): void
    {
        $this->dateDebutDisponible = $dateDebutDisponible;
    }

    public function getSituationActuelle(): ?string
    {
        return $this->situationActuelle;
    }

    public function setSituationActuelle(?string $situationActuelle): void
    {
        $this->situationActuelle = $situationActuelle;
    }

}
