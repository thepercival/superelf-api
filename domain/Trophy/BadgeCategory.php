<?php

declare(strict_types=1);

namespace SuperElf\Trophy;

enum BadgeCategory: string
{
    case Result = 'Result';
    case Goal = 'Goal';
    case Assist = 'Assist';
    case Sheet = 'Sheet';
    case Card = 'Card';
}
