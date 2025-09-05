<?php

declare(strict_types=1);

namespace SuperElf\ChatMessage;

use SportsHelpers\Identifiable;
use SuperElf\ChatMessage;
use SuperElf\Pool\User as PoolUser;

/**
 * @psalm-suppress ClassMustBeFinal
 */
class Unread extends Identifiable
{
    public function __construct(protected ChatMessage $chatMessage, protected PoolUser $poolUser)
    {
    }

    public function getChatMessage(): ChatMessage
    {
        return $this->chatMessage;
    }

    public function getPoolUser(): PoolUser
    {
        return $this->poolUser;
    }
}
