<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case CareCoordinator = 'care_coordinator';
    case Clinician = 'clinician';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<string>
     */
    public static function staffValues(): array
    {
        return self::values();
    }

    public function isStaff(): bool
    {
        return true;
    }

    public function managesClinicians(): bool
    {
        return match ($this) {
            self::SuperAdmin, self::Admin, self::CareCoordinator => true,
            self::Clinician => false,
        };
    }

    public function label(): string
    {
        return str_replace('_', ' ', $this->value);
    }
}
