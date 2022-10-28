<?php

namespace App\Commands\CompetitionConfig;

enum Action: string
{
    case Create = 'create';
    //case SetCreateAndJoinStart = 'set-createandjoin-start';
    case UpdateAssemblePeriod = 'update-assemble-period';
    case UpdateTransferPeriod = 'update-transfer-period';
    case Show = 'show';
    case Remove = 'remove';
}