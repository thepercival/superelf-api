<?php

namespace App\Commands\PoolUser;

enum Action: string
{
    case Show = 'show';
    case CreateTransferFormation = 'createTransferFormation';
}