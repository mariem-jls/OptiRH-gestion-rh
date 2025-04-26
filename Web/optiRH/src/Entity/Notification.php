<?php
namespace App\Entity;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\NotificationRepository;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notifications')]
#[ORM\HasLifecycleCallbacks]
class Notification
{
    
    public const TYPE_NEW_MISSION = 'new_mission';
    public const TYPE_LATE_MISSION = 'late_mission';
    public const TYPE_GENERAL = 'general';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $message;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'boolean')]
    private bool $isRead = false;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type = self::TYPE_GENERAL;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $context = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'notifications')]
    #[ORM\JoinColumn(nullable: True)] // Ceci garantit qu'une notification doit toujours avoir un destinataire
    private User $recipient ;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $routeName = null;
    
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $routeParams = null;

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function markAsRead(): self
    {
        $this->isRead = true;
        return $this;
    }

    public function markAsUnread(): self
    {
        $this->isRead = false;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        if (!in_array($type, [self::TYPE_LATE_MISSION, self::TYPE_NEW_MISSION, self::TYPE_GENERAL])) {
            throw new \InvalidArgumentException('Invalid notification type');
        }
        $this->type = $type;
        return $this;
    }

    public function getContext(): ?array
    {
        return $this->context;
    }

    public function setContext(?array $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function getRecipient(): User
    {
        return $this->recipient;
    }

    public function setRecipient(User $recipient): self
    {
        $this->recipient = $recipient;
        return $this;
    }

    public function getRouteName(): ?string
    {
        return $this->routeName;
    }

    public function setRouteName(?string $routeName): self
    {
        $this->routeName = $routeName;
        return $this;
    }

    public function getRouteParams(): ?array
    {
        return $this->routeParams;
    }

    public function setRouteParams(?array $routeParams): self
    {
        $this->routeParams = $routeParams;
        return $this;
    }

    // Helper methods

    public function getMissionId(): ?int
    {
        return $this->context['mission_id'] ?? null;
    }

    public function getProjectId(): ?int
    {
        return $this->context['project_id'] ?? null;
    }

    public function getDaysLate(): ?int
    {
        return $this->context['days_late'] ?? null;
    }
}