<?php

declare(strict_types=1);

namespace SuperElf\Auth;

use SuperElf\User;

class Item
{
    protected string $token;
    protected User $user;

    public function __construct(string $token, User $user)
    {
        $this->token = $token;
        $this->user = $user;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
