<?php

/*
 * This file is part of the AntiMattr MongoDB Migrations Library, a library by Matthew Fitzgerald.
 *
 * (c) 2014 Matthew Fitzgerald
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AntiMattr\MongoDB\Migrations;

/**
 * @author Matthew Fitzgerald <matthewfitz@gmail.com>
 */
class OutputWriter
{
    private $closure;

    public function __construct(\Closure $closure = null)
    {
        if (null === $closure) {
            $closure = function ($message) {
            };
        }
        $this->closure = $closure;
    }

    /**
     * @param string $message The message to write
     */
    public function write($message)
    {
        $closure = $this->closure;
        $closure($message);
    }
}
