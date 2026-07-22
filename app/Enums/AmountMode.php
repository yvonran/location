<?php

namespace App\Enums;

enum AmountMode: string
{
    case Fixed = 'fixed';
    case Percentage = 'percentage';
}
