<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Revoked = 'revoked';
}
