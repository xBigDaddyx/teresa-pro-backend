<?php

namespace App\Enums;

enum CartonValidationStatus: string
{
    case PENDING = 'PENDING';
    case PROCESS = 'PROCESS';
    case VALIDATED = 'VALIDATED';
}
