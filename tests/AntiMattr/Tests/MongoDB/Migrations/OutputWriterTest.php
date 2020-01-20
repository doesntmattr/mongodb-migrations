<?php

namespace AntiMattr\Tests\MongoDB\Migrations;

use AntiMattr\MongoDB\Migrations\OutputWriter;
use PHPUnit\Framework\TestCase;

class OutputWriterTest extends TestCase
{
    private $outputWriter;

    protected function setUp()
    {
        $this->output = $this->createMock('Symfony\Component\Console\Output\OutputInterface');
        $output = $this->output;
        $this->outputWriter = new OutputWriter(function ($message) use ($output) {
            return $output->writeln($message);
        });
    }

    public function testWrite()
    {
        $this->output->expects($this->once())
            ->method('writeln')
            ->with('foo');

        $this->outputWriter->write('foo');
    }
}
