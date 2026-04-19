<?php

namespace App\Enum;

enum EnvironmentType: string
{
    case TEST = 'test';
    case PRODUCTION = 'production';
}
