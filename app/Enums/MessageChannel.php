<?php

namespace App\Enums;

enum MessageChannel: string
{
    case Client = 'client';

    public function label(): string
    {
        return 'Case chat';
    }
}
