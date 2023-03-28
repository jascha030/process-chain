<?php

declare(strict_types=1);

namespace Jascha030\Process\Chain;

use Illuminate\Support\Collection;
use PhpOption\Option;
use RuntimeException;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Traversable;
use function collect;
use function getcwd;

class ProcessChain
{
    /**
     * @var Collection<Process|string>
     */
    private Collection $processes;

    private bool $hasRun = false;

    /**
     * @param null|array<mixed> $env
     */
    private function __construct(
        iterable $commands,
        private readonly OutputInterface $output = new ConsoleOutput(),
        ?string $cwd = null,
        ?array $env = null,
        mixed $input = null,
        int $timeout = 60
    ) {
        $this->processes = collect($commands)
            ->keyBy(static fn (string $command): string => $command)
            ->map(static fn (string $command): Process => Process::fromShellCommandline($command, $cwd ?? getcwd(), $env, $input, $timeout));
    }

    public function __clone()
    {
        $this->resetProcessData();
    }

    public function __sleep(): array
    {
        throw new \BadMethodCallException('Cannot serialize ' . __CLASS__);
    }

    public function __wakeup()
    {
        throw new \BadMethodCallException('Cannot unserialize ' . __CLASS__);
    }

    /**
     * @param null|array<mixed> $env
     */
    public static function create(
        array $commands,
        ?OutputInterface $output = new ConsoleOutput(),
        ?string $cwd = null,
        ?array $env = null,
        mixed $input = null,
        int $timeout = 60
    ): ProcessChain {
        return new static($commands, $output, $cwd, $env, $input, $timeout);
    }

    public function disableOutput(): ProcessChain
    {
        $this->processes->map(fn (Process $process): Process => $process->disableOutput());

        return $this;
    }

    /**
     * @return Collection<Process|string>
     */
    public function getProcesses(): Collection
    {
        return $this->processes;
    }

    public function mustRun(callable $callback = null, array $env = []): ProcessChain
    {
        $this->processes->map(function (Process $process, string $command) use ($callback, $env) {
            $process->mustRun($callback, $env);

            if ($this->output->isVerbose()) {
                $this->output->writeln("{$command} finished with status: {$process->getExitCode()}");
            }

            return $process;
        });

        $this->hasRun = true;

        return $this;
    }

    public function getExitCodes(): Collection
    {
        $this->hasRun()
            ->getOrThrow(new RuntimeException('Cannot get exit codes before running the process chain.'));

        return $this->processes
            ->collect()
            ->map(fn (Process $process): int => $process->getExitCode());
    }

    /**
     * @return Traversable<Process|string>
     */
    public function getIterator(): Traversable
    {
        foreach ($this->processes as $process) {
            $process->start();
            yield from $process;
        }
    }

    /**
     * @return Option<bool>
     */
    private function hasRun(): Option
    {
        return Option::fromValue($this->hasRun);
    }

    private function resetProcessData(): void
    {
        $this->processes->map(static fn (Process $process) => clone $process);
    }
}
