<?php

namespace App\Enums;

enum DonationSource: string
{
    case Online  = 'online';
    case Manual  = 'manual';
    case Gateway = 'gateway';   // disiapkan untuk payment gateway nanti
}
