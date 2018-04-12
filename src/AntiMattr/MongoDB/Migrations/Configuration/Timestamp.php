<?php

/*
 * This file is part of the AntiMattr MongoDB Migrations Library, a library by Matthew Fitzgerald.
 *
 * (c) 2014 Matthew Fitzgerald
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AntiMattr\MongoDB\Migrations\Configuration;

/**
 * This class is to normalise the potential values from the 't' version
 * attribute
 *
 * @author Douglas Reith <douglas@reith.com.au>
 */
class Timestamp
{
    private $t;

    /**
     * __construct
     *
     * @param mixed $t
     */
    public function __construct($t)
    {
        $this->t = $t;
    }

    /**
     * __invoke
     *
     * @param mixed $t
     * @return int
     */
    public function __invoke($t): int
    {
        return (new static($t))->getTimestamp();
    }

    /**
     * getTimestamp
     *
     * Normalise based on the different options for backward/forward
     * compatibility
     *
     * @return int Time in seconds since 1970
     */
    public function getTimestamp(): int
    {
        $supportedClasses = implode(
            ', ',
            [
                '\MongoTimestamp',
                '\MongoDate',
                '\MongoDB\BSON\Timestamp',
                '\MongoDB\BSON\UTCDateTime',
                '\DateTimeInterface',
            ]
        );

        if (!$this->t || !is_object($this->t)) {
            throw new \DomainException(
                'The timestamp to normalise must be one of ' . $supportedClasses . ' but it is not an object'
            );
        }

        switch(get_class($this->t)) {
            case 'MongoTimestamp':
                return (int) $this->t->__toString();

            case 'MongoDate':
                return $this->t->sec;

            case 'MongoDB\BSON\Timestamp':
                return $this->t->getTimestamp();

            case 'MongoDB\BSON\UTCDateTime':
                return (int) $this->t->toDateTime()->format('U');
        }

        if ($this->t instanceof \DateTimeInterface) {
            return $this->t->getTimestamp();
        }

        throw new \DomainException(
            'The normalised timestamp must be one of ' . $supportedClasses
        );
    }

    /**
     * __toString
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->getTimestamp();
    }
}
