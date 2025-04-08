<?php
namespace App\Entity\GsProjet;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\GsProjet\UserRepository;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    private ?string $nom = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'string')]
    private ?string $role = null;

    #[ORM\Column(type: 'string', name: 'motDePasse')] 
    private ?string $motDePasse = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\OneToMany(mappedBy: 'createdBy', targetEntity: Project::class)]
    private Collection $projects;

    #[ORM\OneToMany(mappedBy: 'assignedTo', targetEntity: Mission::class)]
    private Collection $missions;

    public function __construct()
    {
        $this->projects = new ArrayCollection();
        $this->missions = new ArrayCollection();
    }

    // Getters/Setters...

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getMotDePasse(): ?string
    {
        return $this->motDePasse;
    }

    public function setMotDePasse(string $motDePasse): static
    {
        $this->motDePasse = $motDePasse;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;
        return $this;
    }

    // Interface UserInterface methods

    public function getRoles(): array
    {
        // Retourner un tableau avec les rôles
        return [$this->role ?? 'ROLE_USER'];
    }

    public function setRoles(array $roles): static
    {
        // Assigner le premier rôle à l'utilisateur (on suppose qu'un seul rôle est nécessaire)
        $this->role = $roles[0] ?? 'ROLE_USER';
        return $this;
    }

    public function getPassword(): string
    {
        return $this->motDePasse;
    }

    public function eraseCredentials() {}

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    // Relations

    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Project $project): static
    {
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
            $project->setCreatedBy($this);
        }
        return $this;
    }

    public function removeProject(Project $project): static
    {
        if ($this->projects->removeElement($project)) {
            if ($project->getCreatedBy() === $this) {
                $project->setCreatedBy(null);
            }
        }
        return $this;
    }

    public function getMissions(): Collection
    {
        return $this->missions;
    }

    public function addMission(Mission $mission): static
    {
        if (!$this->missions->contains($mission)) {
            $this->missions->add($mission);
            $mission->setAssignedTo($this);
        }
        return $this;
    }

    public function removeMission(Mission $mission): static
    {
        if ($this->missions->removeElement($mission)) {
            if ($mission->getAssignedTo() === $this) {
                $mission->setAssignedTo(null);
            }
        }
        return $this;
    }
}

