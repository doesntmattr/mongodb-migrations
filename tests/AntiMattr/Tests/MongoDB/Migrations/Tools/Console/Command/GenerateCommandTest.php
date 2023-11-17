<?php

namespace AntiMattr\Tests\MongoDB\Migrations\Tools\Console\Command;

use AntiMattr\MongoDB\Migrations\Tools\Console\Command\GenerateCommand;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArgvInput;

/**
 * @author Ryan Catlin <ryan.catlin@gmail.com>
 */
class GenerateCommandTest extends TestCase
{
    private $command;
    private $output;
    private $config;

    protected function setUp(): void
    {
        $this->command = new GenerateCommandStub();
        $this->output = $this->createMock('Symfony\Component\Console\Output\OutputInterface');
        $this->config = $this->createMock('AntiMattr\MongoDB\Migrations\Configuration\Configuration');

        $this->command->setMigrationConfiguration($this->config);
    }

    public function testExecute()
    {
        $migrationsNamespace = 'migrations-namespace';
        $migrationsDirectory = 'Base/Migrations';
        $versionString = '1234567890';

        $this->command->setVersionString($versionString);

        $root = vfsStream::setup(
            'Base', // rootDir
            null,   // permissions
            [  // structure
                'Migrations' => [],
            ]
        );

        $input = new ArgvInput(
            [
                GenerateCommand::getDefaultName(),
            ]
        );

        // Expectations
        $this->config->expects($this->once())
            ->method('getMigrationsNamespace')
            ->will(
                $this->returnValue($migrationsNamespace)
            )
        ;
        $this->config->expects($this->once())
            ->method('getMigrationsDirectory')
            ->will(
                $this->returnValue(vfsStream::url($migrationsDirectory))
            )
        ;

        $this->command->run(
            $input,
            $this->output
        );

        // Assertions
        $filename = sprintf(
            '%s/Version%s.php',
            $migrationsDirectory,
            $versionString
        );
        $this->assertTrue($root->hasChild($filename));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExecuteWithInvalidMigrationDirectory()
    {
        $migrationsNamespace = 'migrations-namespace';
        $migrationsDirectory = 'missing-directory';

        $root = vfsStream::setup('Base');

        $input = new ArgvInput(
            [
                GenerateCommand::getDefaultName(),
            ]
        );

        // Expectations
        $this->config->expects($this->once())
            ->method('getMigrationsNamespace')
            ->will(
                $this->returnValue($migrationsNamespace)
            )
        ;
        $this->config->expects($this->once())
            ->method('getMigrationsDirectory')
            ->will(
                $this->returnValue(
                    sprintf('%s/%s',
                        vfsStream::url('Base'),
                        $migrationsDirectory
                    )
                )
            )
        ;

        // Run command, run.
        $this->command->run(
            $input,
            $this->output
        );
    }
}

class GenerateCommandStub extends GenerateCommand
{
    protected $version;

    public function getPrivateTemplate()
    {
        return self::$_template;
    }

    public function setVersionString($version)
    {
        $this->version = $version;
    }

    protected function getVersionString()
    {
        return $this->version;
    }
}
