<?php

declare(strict_types=1);

namespace App;

final class AuthToken
{
    protected string|int $userId;

    /**
     * @param array<string, string|int> $decoded
     */
    public function __construct(array $decoded)
    {
        $this->userId = $decoded['sub'];
    }

    public function getUserId(): int|string
    {
        return $this->userId;
    }
}
