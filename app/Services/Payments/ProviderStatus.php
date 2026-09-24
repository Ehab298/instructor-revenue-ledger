<?php

namespace App\Services\Payments;

enum ProviderStatus: string
{
    case Paid = 'paid';
    case Failed = 'failed';
}
