<?php

namespace App;

enum AccountType: string
{
    case Business = 'business';
    case Personal = 'personal';

    public function label(): string
    {
        return __('account_type.'.$this->value);
    }
}
