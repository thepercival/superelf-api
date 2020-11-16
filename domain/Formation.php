<?php

namespace SuperElf;

use Doctrine\Common\Collections\ArrayCollection;
use SuperElf\Formation\Line;

class Formation
{
    /**
     * @var int
     */
    protected $id;
    /**
     * @var ArrayCollection|Line[]
     */
    protected $lines;

    public function __construct()
    {
        $this->lines = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id)
    {
        $this->id = $id;
    }

    public function getLines()
    {
        return $this->lines;
    }

    public function getName(): string {
        return implode( "-", $this->getLines()->map( function( Line $line ): int {
            return $line->getMaxNrOfPersons();
        })->toArray() );
    }

    public function getNrOfPersons(): int {
        $nrOfPersons = 0;
        foreach( $this->getLines() as $line ) {
            $nrOfPersons += $line->getPersons()->count();
            if( $line->getSubstitute() ) {
                $nrOfPersons++;
            }
        }
        return $nrOfPersons;
    }
}
