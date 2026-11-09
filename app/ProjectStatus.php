<?php

namespace App;

enum ProjectStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Closed = 'closed';

    public function label(): string
    {
        return __('homeowner.status.'.$this->value);
    }
}
