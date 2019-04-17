<?php
declare(strict_types=1);

namespace Firehed\Nf;

use Symfony\Component\Console\Command\Command as Base;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends Base
{
    protected function configure()
    {
        $this->setName('nf');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return 3;
    }
}
