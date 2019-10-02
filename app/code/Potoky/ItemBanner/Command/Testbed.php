<?php
namespace Potoky\ItemBanner\Command;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class Testbed extends Command
{
    protected function configure()
    {
        $this->setName('pt:test-if-works');
        $this->setDescription('Testing purposes');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('! CONGRATULATIONS !');
    }
}
