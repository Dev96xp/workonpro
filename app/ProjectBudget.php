<?php

namespace App;

enum ProjectBudget: string
{
    case Under1k = 'under_1k';
    case From1kTo5k = '1k_5k';
    case From5kTo15k = '5k_15k';
    case Over15k = 'over_15k';
    case NotSure = 'not_sure';

    public function label(): string
    {
        return __('homeowner.budget.'.$this->value);
    }
}
