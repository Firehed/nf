<?php
declare(strict_types=1);

namespace Firehed\Nf;

use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command as Base;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class CompileCommand extends Base
{
    protected function configure(): void
    {
        $this->setName('compile');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $levelMap = [
            LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::DEBUG => OutputInterface::VERBOSITY_VERBOSE,
        ];
        $logger = new ConsoleLogger($output, $levelMap);
        $compiler = new Compiler($logger);
        $compiler->compile(Command::class);
        return 0;
    }
}
