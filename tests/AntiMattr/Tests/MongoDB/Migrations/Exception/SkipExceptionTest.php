<?php

namespace AntiMattr\Tests\MongoDB\Migrations\Exception;

use AntiMattr\TestCase\AntiMattrTestCase;

class SkipExceptionTest extends AntiMattrTestCase
{
    private $exception;

    protected function setUp()
    {
        $this->exception = $this->buildMock('AntiMattr\MongoDB\Migrations\Exception\SkipException');
    }

    public function testConstructor()
    {
        $this->assertInstanceOf('\AntiMattr\MongoDB\Migrations\Exception\AbstractMigrationsException', $this->exception);
    }
}
