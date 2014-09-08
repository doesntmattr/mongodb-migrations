<?php

namespace AntiMattr\Tests\MongoDB\Migrations\Exception;

use AntiMattr\TestCase\AntiMattrTestCase;

class NoMigrationsToExecuteExceptionTest extends AntiMattrTestCase
{
    private $exception;

    protected function setUp()
    {
        $this->exception = $this->buildMock('AntiMattr\MongoDB\Migrations\Exception\NoMigrationsToExecuteException');
    }

    public function testConstructor()
    {
        $this->assertInstanceOf('\AntiMattr\MongoDB\Migrations\Exception\AbstractMigrationsException', $this->exception);
    }
}
