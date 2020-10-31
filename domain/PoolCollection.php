<?php

declare(strict_types=1);

namespace SuperElf;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class PoolCollection
{
    /**
     * @var string | int
     */
    protected $id;
    protected string $name;
    /**
     * @var ArrayCollection|Pool[]
     */
    protected $pools;

    const MIN_LENGTH_NAME = 3;
    const MAX_LENGTH_NAME = 20;

    public function __construct( string $name )
    {
        $this->setName( $name );
        $this->pools = new ArrayCollection();
    }

    /**
     * @return string | int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string | int $id
     */
    public function setId($id)
    {
        $this->id = $id;
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

    /**
     * @return ArrayCollection|Pool[]
     */
    public function getPools() {
        return $this->pools;
    }
}
