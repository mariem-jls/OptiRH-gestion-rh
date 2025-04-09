<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('status_icon', [$this, 'getStatusIcon']),
            new TwigFilter('status_color', [$this, 'getStatusColor']),
            new TwigFilter('status_badge', [$this, 'getStatusBadge']),
        ];
    }

    public function getStatusIcon(string $status): string
    {
        return match($status) {
            'To Do' => 'checkbox-blank-line',
            'In Progress' => 'settings-5-line',
            'Done' => 'checkbox-circle-line',
            'Terminé' => 'checkbox-circle-line',
            'En cours' => 'loader-4-line',
            default => 'question-line'
        };
    }

    public function getStatusColor(string $status): string
    {
        return match($status) {
            'To Do', 'À faire' => 'primary',
            'In Progress', 'En cours' => 'warning',
            'Done', 'Terminé' => 'success',
            default => 'secondary'
        };
    }

    public function getStatusBadge(string $status): string
    {
        return match($status) {
            'Terminé' => 'success',
            'En cours' => 'warning',
            'En attente' => 'info',
            'Annulé' => 'danger',
            default => 'secondary'
        };
    }
}