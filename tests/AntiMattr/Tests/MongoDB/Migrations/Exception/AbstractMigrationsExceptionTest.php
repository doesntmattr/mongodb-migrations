<?php

namespace AntiMattr\Tests\MongoDB\Migrations\Exception;

use AntiMattr\MongoDB\Migrations\Exception\AbstractMigrationsException;
use AntiMattr\TestCase\AntiMattrTestCase;

class AbstractMigrationsExceptionTest extends AntiMattrTestCase
{
    private $exception;

    protected function setUp()
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
