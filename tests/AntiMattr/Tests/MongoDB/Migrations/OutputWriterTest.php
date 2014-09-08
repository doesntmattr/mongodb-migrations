<?php

namespace AntiMattr\Tests\MongoDB\Migrations;

use AntiMattr\MongoDB\Migrations\OutputWriter;
use AntiMattr\TestCase\AntiMattrTestCase;

class OutputWriterTest extends AntiMattrTestCase
{
    private $outputWriter;

    protected function setUp()
    {
        $this->output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');
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
