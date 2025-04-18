<?php

namespace App\Enum;

enum Role: string
{
    case ROLE_USER = 'ROLE_USER';
    case ROLE_EMPLOYEE = 'ROLE_EMPLOYEE';
    case ROLE_ADMIN = 'ROLE_ADMIN';
    case ROLE_CANDIDATE = 'ROLE_CANDIDATE';
    case ROLE_MANAGER = 'ROLE_MANAGER';
    case ROLE_DQHS = 'ROLE_DQHS';

    public function label(): string
    {
        return match ($this) {
            self::ROLE_USER => 'Utilisateur',
            self::ROLE_EMPLOYEE => 'Employé',
            self::ROLE_ADMIN => 'Administrateur',
            self::ROLE_CANDIDATE => 'Candidat',
            self::ROLE_MANAGER => 'Chef de projet',
            self::ROLE_DQHS => 'DQHS',
        };
    }

    public static function choices(): array
    {
        $choices = [];
        foreach (self::cases() as $case) {
            $choices[$case->label()] = $case->value;
        }
        return $choices;
    }
}
