<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ProjectExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('status_badge', [$this, 'getStatusBadge']),
            new TwigFilter('type_color', [$this, 'getTypeColor']),
        ];
    }

    public function getStatusBadge(string $status): string
    {
        return match($status) {
            'Completed' => 'success',
            'In Progress' => 'primary',
            'On Hold' => 'warning',
            default => 'secondary'
        };
    }

    public function getTypeColor(string $type): string
    {
        return match($type) {
            'Web Design' => 'success',
            'Android' => 'primary',
            'iOS' => 'info',
            default => 'dark'
        };
    }
}