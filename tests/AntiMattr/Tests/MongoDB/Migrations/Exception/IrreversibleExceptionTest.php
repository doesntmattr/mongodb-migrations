<?php

namespace AntiMattr\Tests\MongoDB\Migrations\Exception;

use PHPUnit\Framework\TestCase;

class IrreversibleExceptionTest extends TestCase
{
    private $exception;

    protected function setUp(): void
    {
        $this->exception = $this->createMock('AntiMattr\MongoDB\Migrations\Exception\IrreversibleException');
    }

    public function testConstructor()
    {
        $this->assertInstanceOf('\AntiMattr\MongoDB\Migrations\Exception\AbstractMigrationsException', $this->exception);
    }
}
