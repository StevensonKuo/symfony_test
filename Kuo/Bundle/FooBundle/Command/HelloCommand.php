<?php

namespace App\Kuo\Bundle\FooBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class HelloCommand
 * @package App\Kuo\Bundle\FooBundle\Command
 */
class HelloCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('foo:hello')
            ->setDescription('I can say "Hello".');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @SuppressWarnings(PHPMD.UnusedFormalParameters)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Hello from Foo!');

        return 0;
    }
}
