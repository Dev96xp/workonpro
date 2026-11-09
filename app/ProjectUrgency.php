<?php

namespace App;

enum ProjectUrgency: string
{
    case Urgent = 'urgent';
    case ThisMonth = 'this_month';
    case Flexible = 'flexible';

    public function label(): string
    {
        return __('homeowner.urgency.'.$this->value);
    }
}
