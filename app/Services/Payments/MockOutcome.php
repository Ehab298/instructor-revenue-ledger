<?php

namespace App\Services\Payments;

/**
 * Behaviours the mock provider can simulate.
 */
enum MockOutcome: string
{
    case Success = 'success';
    case PermanentFailure = 'permanent_failure';

    case TimeoutAfterSuccess = 'timeout_after_success';

    case TimeoutAfterFailure = 'timeout_after_failure';
}
