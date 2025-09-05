<?php

declare(strict_types=1);

namespace SuperElf\Auth;

final class Settings
{
    /**
     * @var string
     */
    protected $jwtSecret;
    /**
     * @var string
     */
    protected $jwtAlgorithm;
    /**
     * @var string
     */
    protected $activationSecret;

    public function __construct(
        string $jwtSecret,
        string $jwtAlgorithm,
        string $activationSecret
    ) {
        $this->jwtSecret = $jwtSecret;
        $this->jwtAlgorithm = $jwtAlgorithm;
        $this->activationSecret = $activationSecret;
    }

    public function getJwtSecret(): string
    {
        return $this->jwtSecret;
    }

    public function getJwtAlgorithm(): string
    {
        return $this->jwtAlgorithm;
    }

    public function getActivationSecret(): string
    {
        return $this->activationSecret;
    }
}
