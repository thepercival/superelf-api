<?php

declare(strict_types=1);

namespace SuperElf;

use DateTimeImmutable;
use Sports\Competition\Referee;
use SportsHelpers\Identifiable;

class User extends Identifiable
{
    private string $emailaddress;
    private string $name;
    private string|null $forgetpassword = null;
    private bool $validated;

    const MIN_LENGTH_EMAIL = Referee::MIN_LENGTH_EMAIL;
    const MAX_LENGTH_EMAIL = Referee::MAX_LENGTH_EMAIL;
    const MIN_LENGTH_PASSWORD = 3;
    const MAX_LENGTH_PASSWORD = 50;
    const MIN_LENGTH_NAME = 2;
    const MAX_LENGTH_NAME = 15;

    public function __construct(
        string $emailaddress, string $name, protected string $salt, protected string $password)
    {
        $this->validated = false;
        $this->setEmailaddress($emailaddress);
        $this->setName($name);
    }

    public function getEmailaddress(): string
    {
        return $this->emailaddress;
    }

    final public function setEmailaddress(string $emailaddress): void
    {
        if (strlen($emailaddress) < static::MIN_LENGTH_EMAIL or strlen($emailaddress) > static::MAX_LENGTH_EMAIL) {
            throw new \InvalidArgumentException(
                "het emailadres moet minimaal " . static::MIN_LENGTH_EMAIL . " karakters bevatten en mag maximaal " . static::MAX_LENGTH_EMAIL . " karakters bevatten",
                E_ERROR
            );
        }

        if (!filter_var($emailaddress, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("het emailadres " . $emailaddress . " is niet valide", E_ERROR);
        }
        $this->emailaddress = $emailaddress;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        if (strlen($password) === 0) {
            throw new \InvalidArgumentException("de wachtwoord-hash mag niet leeg zijn", E_ERROR);
        }
        $this->password = $password;
    }

    public function getSalt(): string
    {
        return $this->salt;
    }

    public function setSalt(string $salt): void
    {
        $this->salt = $salt;
    }

    public function getName(): string
    {
        return $this->name;
    }

    final public function setName(string $name): void
    {
        if (strlen($name) < static::MIN_LENGTH_NAME or strlen($name) > static::MAX_LENGTH_NAME) {
            throw new \InvalidArgumentException(
                "de naam moet minimaal " . static::MIN_LENGTH_NAME . " karakters bevatten en mag maximaal " . static::MAX_LENGTH_NAME . " karakters bevatten",
                E_ERROR
            );
        }

        if (!ctype_alnum($name)) {
            throw new \InvalidArgumentException("de naam mag alleen cijfers en letters bevatten", E_ERROR);
        }
        $this->name = $name;
    }

    public function getForgetpassword(): string|null
    {
        return $this->forgetpassword;
    }

    public function setForgetpassword(string|null $forgetpassword): void
    {
        $this->forgetpassword = $forgetpassword;
    }

    public function resetForgetpassword(): void
    {
        $forgetpassword = rand(100000, 999999);
        $tomorrow = date("Y-m-d", strtotime('tomorrow'));
        $tomorrow = new \DateTimeImmutable($tomorrow);
        $tomorrow = $tomorrow->modify("+1 days");
        $this->setForgetpassword($forgetpassword . ":" . $tomorrow->format("Y-m-d"));
    }

    public function getForgetpasswordToken(): string
    {
        $forgetpassword = $this->getForgetpassword();
        if ($forgetpassword === null || strlen($forgetpassword) === 0) {
            return '';
        }
        $arrForgetPassword = explode(":", $forgetpassword);
        return $arrForgetPassword[0];
    }

    /**
     * last 10 characters
     *
     * @throws \Exception
     */
    public function getForgetpasswordDeadline(): DateTimeImmutable|null
    {
        $forgetpassword = $this->getForgetpassword();
        if ($forgetpassword === null || strlen($forgetpassword) === 0) {
            return null;
        }
        $arrForgetPassword = explode(":", $forgetpassword);
        return new \DateTimeImmutable($arrForgetPassword[1]);
    }

    public function getValidated(): bool
    {
        return $this->validated;
    }

    public function setValidated(bool $validated): void
    {
        $this->validated = $validated;
    }
}
