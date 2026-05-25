<?php

namespace App\Enums;

enum OnboardingStatus: string
{
    case Registered = 'registered';
    case KycSubmitted = 'kyc_submitted';
    case Active = 'active';
    case Rejected = 'rejected';
}
