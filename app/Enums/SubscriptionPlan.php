<?php

namespace App\Enums;

enum SubscriptionPlan: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Annual = 'annual';

    /**
     * Price in piastres (1 EGP = 100 piastres).
     */
    public function priceInPiastres(): int
    {
        return match ($this) {
            self::Monthly => 120_000,
            self::Quarterly => 600_000,
            self::Annual => 1_000_000,
        };
    }

    public function durationInMonths(): int
    {
        return match ($this) {
            self::Monthly => 1,
            self::Quarterly => 3,
            self::Annual => 12,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Quarterly => 'Quarterly',
            self::Annual => 'Annual',
        };
    }

    public function formattedPrice(): string
    {
        return number_format($this->priceInPiastres() / 100).' EGP';
    }
}
