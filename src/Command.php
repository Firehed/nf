<?php
declare(strict_types=1);

namespace Firehed\Nf;

use RuntimeException;
use Symfony\Component\Console\Command\Command as Base;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends Base
{
    private const ARG_FILE = 'file';
    private const OPT_INTERFACE = 'interface';
    private const OPT_TRAIT = 'trait';

    protected function configure(): void
    {
        $this->setName('nf')
            ->setDescription('Generate new PHP class file')
            ->addOption(
                self::OPT_TRAIT,
                't',
                InputOption::VALUE_NONE,
                'Generate a trait instead of a class'
            )
            ->addOption(
                self::OPT_INTERFACE,
                'i',
                InputOption::VALUE_NONE,
                'Generate an interface instead of a class'
            )
            ->addArgument(
                self::ARG_FILE,
                InputArgument::REQUIRED,
                'The file name'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Executed command');
        $isTrait = (bool)$input->getOption(self::OPT_TRAIT);
        $isInterface = (bool)$input->getOption(self::OPT_INTERFACE);

        if ($isTrait && $isInterface) {
            throw new RuntimeException(
                'Trait and interface mode cannot be used at the same time'
            );
        }
        return 4;
    }
}
