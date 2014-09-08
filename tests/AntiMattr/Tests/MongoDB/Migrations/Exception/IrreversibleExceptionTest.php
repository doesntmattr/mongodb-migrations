<?php

namespace AntiMattr\Tests\MongoDB\Migrations\Exception;

use AntiMattr\TestCase\AntiMattrTestCase;

class IrreversibleExceptionTest extends AntiMattrTestCase
{
    private $exception;

    protected function setUp()
    {
        $this->exception = $this->buildMock('AntiMattr\MongoDB\Migrations\Exception\IrreversibleException');
    }

    public function testConstructor()
    {
        $this->assertInstanceOf('\AntiMattr\MongoDB\Migrations\Exception\AbstractMigrationsException', $this->exception);
    }
}
