<?php

namespace App\Enums;

enum CompanyStatus: string
{
    case Pending = 'pending';
    case KycSubmitted = 'kyc_submitted';
    case Active = 'active';
    case Rejected = 'rejected';
}
