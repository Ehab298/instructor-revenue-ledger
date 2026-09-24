<?php

namespace App\Support;

/**
 * Single place that knows how stored integer amounts are displayed.
 * Amounts are stored in piastres; change this class if the storage
 * unit ever changes.
 */
final class Money
{
    public static function format(int $amount): string
    {
        return number_format($amount / 100, 2).' EGP';
    }
}
