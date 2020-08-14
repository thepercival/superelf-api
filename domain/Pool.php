<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 28-1-17
 * Time: 20:44
 */

namespace SuperElf;

use DateTimeImmutable;

class Pool
{
    /**
     * @var string | int
     */
    public $id;
    public string $name;

    const MIN_LENGTH_NAME = 3;
    const MAX_LENGTH_NAME = 60;

    public function __construct( string $name )
    {
        $this->name = $name;
    }

    public function getName():string {
        return $this->name;
    }

    public function setName(string $name ) {
        if (strlen($name) < static::MIN_LENGTH_NAME or strlen($name) > static::MAX_LENGTH_NAME) {
            throw new \InvalidArgumentException("de naam moet minimaal ".static::MIN_LENGTH_NAME." karakters bevatten en mag maximaal ".static::MAX_LENGTH_NAME." karakters bevatten", E_ERROR);
        }
        $this->name = $name;
    }
}
