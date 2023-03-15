<?php

declare(strict_types=1);

namespace SuperElf\Achievement;

enum BadgeCategory: string
{
    case Result = 'Result';
    case Goal = 'FieldGoal';
    case Assist = 'Assist';
    case Sheet = 'Sheet';
    case Card = 'Card';
}
