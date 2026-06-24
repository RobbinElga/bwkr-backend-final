<?php

namespace App\Enums;

enum BroadcastStatus: string
{
    case Sent   = 'sent';
    case Failed = 'failed';
}
