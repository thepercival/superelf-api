<?php

namespace App\Commands\Person;

enum Action: string
{
    case Fetch = 'fetch';
    case CreateWithS11Players = 'createWithS11Players';
    case MakeTransfer = 'makeTransfer';
    case UpdateCurrentLine = 'updateCurrentLine';
    case Stop = 'stop';
//    case SetCreateAndJoinStart = 'set-createandjoin-start';
//    case SetAssemblePeriod = 'set-assemble-period';
//    case SetTransferPeriod = 'set-transfer-period';
}