# Process Chain

Chain `symfony/process` commands together.

## Getting started

## Prerequisites

* php: `>=8.1`
* Composer `^2.3`

### Installation

```shell
composer require jascha030/process-chain
```

## Usage

```php
<?php

use Jascha030\Process\Chain\ProcessChain;
use Symfony\Component\Console\Output\ConsoleOutput;

// Define the commands to run in the process chain
$commands = [
    'echo "Hello"',
    'echo "World"',
];

// Create a new instance of the ProcessChain class
$processChain = ProcessChain::create(
    $commands,
    new ConsoleOutput()
);

// Disable output for the processes in the chain
$processChain->disableOutput();

// Run the processes in the chain
$processChain->mustRun();

// Get the exit codes for the processes in the chain
$exitCodes = $processChain->getExitCodes();

// Output the exit codes for each command
foreach ($exitCodes as $command => $exitCode) {
    echo "{$command} exited with code {$exitCode}\n";
}
```

## License

This composer package is an open-sourced software licensed under
the [MIT License](https://github.com/jascha030/process-chain/blob/master/LICENSE.md)
