<?php

namespace App;

enum ProjectInterestStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';

    public function label(): string
    {
        return __('homeowner.interest_status.'.$this->value);
    }
}
