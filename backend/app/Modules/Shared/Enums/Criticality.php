<?php

namespace App\Modules\Shared\Enums;

enum Criticality: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
}
