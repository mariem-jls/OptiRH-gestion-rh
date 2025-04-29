<?php
// src/Entity/Interview.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\InterviewRepository;

#[ORM\Entity(repositoryClass: InterviewRepository::class)]
class Interview
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Demande::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Demande $demande = null;

    #[ORM\Column(type: "datetime")]
    private ?\DateTimeInterface $dateTime = null;

    #[ORM\Column(type: "string", length: 255)]
    private ?string $googleMeetLink = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDemande(): ?Demande
    {
        return $this->demande;
    }

    public function setDemande(?Demande $demande): self
    {
        $this->demande = $demande;
        return $this;
    }

    public function getDateTime(): ?\DateTimeInterface
    {
        return $this->dateTime;
    }

    public function setDateTime(\DateTimeInterface $dateTime): self
    {
        $this->dateTime = $dateTime;
        return $this;
    }

    public function getGoogleMeetLink(): ?string
    {
        return $this->googleMeetLink;
    }

    public function setGoogleMeetLink(string $googleMeetLink): self
    {
        $this->googleMeetLink = $googleMeetLink;
        return $this;
    }
}