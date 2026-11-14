<?php

namespace App\Modules\Shared\Enums;

enum UserRole: string
{
    case User = 'user';
    case Admin = 'admin';
    case Government = 'government';
}
