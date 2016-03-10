<?php

namespace LisActiv\Bundle\DynamicSiteBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GeneratorCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('dynamicsite:dump')
            ->setDescription('Dump siteaccess configuration for multi site usage')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $generator = $this->getContainer()->get('dynamicsite.generator');
        $generator->dumpConfig();
        $output->writeln('Youpi');
    }
}
