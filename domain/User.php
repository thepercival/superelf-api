<?php

declare(strict_types=1);

namespace SuperElf;

use DateTimeImmutable;
use Sports\Competition\CompetitionReferee;
use SportsHelpers\Identifiable;

/**
 * @psalm-suppress ClassMustBeFinal
 */
class User extends Identifiable
{
    private string $emailaddress;
    private string $name;
    private string|null $forgetpassword = null;
    private bool $validated;

    public const int MIN_LENGTH_EMAIL = CompetitionReferee::MIN_LENGTH_EMAIL;
    public const int MAX_LENGTH_EMAIL = CompetitionReferee::MAX_LENGTH_EMAIL;
    public const int MIN_LENGTH_PASSWORD = 3;
    public const int MAX_LENGTH_PASSWORD = 50;
    public const int MIN_LENGTH_NAME = 2;
    public const int MAX_LENGTH_NAME = 15;

    public function __construct(
        string $emailaddress,
        string $name,
        protected string $salt,
        protected string $password
    )
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
        if (strlen($emailaddress) < self::MIN_LENGTH_EMAIL or strlen($emailaddress) > self::MAX_LENGTH_EMAIL) {
            throw new \InvalidArgumentException(
                "het emailadres moet minimaal " . self::MIN_LENGTH_EMAIL . " karakters bevatten en mag maximaal " . self::MAX_LENGTH_EMAIL . " karakters bevatten",
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

//    public function setSalt(string $salt): void
//    {
//        $this->salt = $salt;
//    }

    public function getName(): string
    {
        return $this->name;
    }

    final public function setName(string $name): void
    {
        if (strlen($name) < self::MIN_LENGTH_NAME or strlen($name) > self::MAX_LENGTH_NAME) {
            throw new \InvalidArgumentException(
                "de naam moet minimaal " . self::MIN_LENGTH_NAME . " karakters bevatten en mag maximaal " . self::MAX_LENGTH_NAME . " karakters bevatten",
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
        $forgetPassword = rand(100000, 999999);
        $tomorrow = new DateTimeImmutable('tomorrow');
        $tomorrow = $tomorrow->add(new \DateInterval('P1D'));
        $this->setForgetpassword($forgetPassword . ":" . $tomorrow->format("Y-m-d"));
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
