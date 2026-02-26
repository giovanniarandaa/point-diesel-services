<?php

namespace App\Actions\Estimate;

use App\Enums\EstimateStatus;
use App\Models\Estimate;

class ApproveEstimateAction
{
    public function execute(Estimate $estimate, string $ip): bool
    {
        if ($estimate->status !== EstimateStatus::Sent) {
            return false;
        }

        $estimate->markAsApproved($ip);

        return true;
    }
}
