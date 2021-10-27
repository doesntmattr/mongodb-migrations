<?php

namespace AntiMattr\Tests\MongoDB\Migrations\Exception;

use PHPUnit\Framework\TestCase;

class NoMigrationsToExecuteExceptionTest extends TestCase
{
    private $exception;

    protected function setUp(): void
    {
        $this->exception = $this->createMock('AntiMattr\MongoDB\Migrations\Exception\NoMigrationsToExecuteException');
    }

    public function testConstructor()
    {
        $this->assertInstanceOf('\AntiMattr\MongoDB\Migrations\Exception\AbstractMigrationsException', $this->exception);
    }
}
