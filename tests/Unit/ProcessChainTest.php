<?php

/** @noinspection PhpMissingParentCallCommonInspection */

declare(strict_types=1);

namespace Jascha030\Process\Chain;

use BadMethodCallException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use function is_dir;
use function PHPUnit\Framework\assertDirectoryExists;
use function PHPUnit\Framework\assertFileExists;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertIsInt;
use function PHPUnit\Framework\assertIsIterable;
use function PHPUnit\Framework\assertIsString;

/**
 * @covers \Jascha030\Process\Chain\ProcessChain
 *
 * @internal
 */
final class ProcessChainTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $fs = new Filesystem();

        if (is_dir(__DIR__ . '/../../output')) {
            $fs->remove(__DIR__ . '/../../output');
        }
    }

    public static function tearDownAfterClass(): void
    {
        $fs = new Filesystem();

        if (is_dir(__DIR__ . '/../../output')) {
            $fs->remove(__DIR__ . '/../../output');
        }
    }

    public function testCreate(): void
    {
        $chain = $this->createChain();

        assertInstanceOf(ProcessChain::class, $chain);
    }

    public function testDisableOutput(): void
    {
        assertInstanceOf(ProcessChain::class, $this->createChain()->disableOutput());
    }

    /**
     * @depends testMustRun
     */
    public function testGetExitCodes(ProcessChain $chain): void
    {
        foreach ($chain->getExitCodes() as $code) {
            assertIsInt($code);
        }
    }

    public function testGetIterator(): void
    {
        assertIsIterable($this->createChain()->getIterator());

        foreach ($this->createChain()->getIterator() as $process) {
            assertIsString($process);
        }
    }

    public function testGetProcesses(): void
    {
        foreach ($this->createChain()->getProcesses() as $process) {
            assertInstanceOf(Process::class, $process);
        }
    }

    public function testMustRun(): ProcessChain
    {
        $chain = $this->createChain();
        $chain->mustRun();

        assertDirectoryExists(__DIR__ . '/../../output');
        assertDirectoryExists(__DIR__ . '/../../output/.git');
        assertFileExists(__DIR__ . '/../../output/test.php');

        return $chain;
    }

    public function testClone(): void
    {
        assertInstanceOf(ProcessChain::class, clone $this->createChain());
    }

    public function testSleep(): void
    {
        $this->expectException(BadMethodCallException::class);

        $this->createChain()->__sleep();
    }

    public function testWakeup(): void
    {
        $this->expectException(BadMethodCallException::class);

        $this->createChain()->__wakeup();
    }

    private function createChain(): ProcessChain
    {
        $output = new ConsoleOutput();
        $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);

        return ProcessChain::create(
            ['mkdir output', 'touch output/test.php', 'git -C output init'],
            $output,
            cwd: __DIR__ . '/../../'
        );
    }
}
