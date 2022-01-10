<?php

namespace App\Commands\CompetitionConfig;

enum Action: string
{
    case Create = 'create';
    case SetCreateAndJoinStart = 'set-createandjoin-start';
    case SetAssemblePeriod = 'set-assemble-period';
    case SetTransferPeriod = 'set-transfer-period';
}