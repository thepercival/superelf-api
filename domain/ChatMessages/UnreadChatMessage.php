<?php

declare(strict_types=1);

namespace SuperElf\ChatMessages;

use SportsHelpers\Identifiable;
use SuperElf\ChatMessages;
use SuperElf\Pool\User as PoolUser;

/**
 * @psalm-suppress ClassMustBeFinal
 */
class UnreadChatMessage extends Identifiable
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
