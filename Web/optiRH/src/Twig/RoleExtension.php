<?php

namespace App\Twig;

use App\Enum\Role;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class RoleExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('role_label', [$this, 'getRoleLabel']),
        ];
    }

    public function getRoleLabel(string $role): string
    {
        $enum = Role::tryFrom($role);
        return $enum?->label() ?? $role;
    }
}
