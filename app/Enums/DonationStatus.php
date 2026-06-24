<?php

namespace App\Enums;

enum DonationStatus: string
{
    case Pending  = 'pending';
    case Claimed  = 'claimed';
    case Rejected = 'rejected';
}
