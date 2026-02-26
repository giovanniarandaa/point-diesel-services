<?php

namespace App\Enums;

enum EstimateStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Approved = 'approved';
    case Invoiced = 'invoiced';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::Approved => 'Approved',
            self::Invoiced => 'Invoiced',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Sent => 'default',
            self::Approved => 'success',
            self::Invoiced => 'outline',
        };
    }
}
