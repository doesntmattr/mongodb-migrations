<?php

namespace AntiMattr\Tests\MongoDB\Migrations\Exception;

use AntiMattr\MongoDB\Migrations\Exception\AbstractMigrationsException;
use PHPUnit\Framework\TestCase;

class AbstractMigrationsExceptionTest extends TestCase
{
    private $exception;

    protected function setUp(): void
    {
        $this->exception = new AbstractMigrationsExceptionStub();
    }

    public function testConstructor()
    {
        $this->assertInstanceOf('\RuntimeException', $this->exception);
    }
}

class AbstractMigrationsExceptionStub extends AbstractMigrationsException
{
}
