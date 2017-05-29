<?php

namespace ActiveCampaign\Diagnostics\Command;

use ActiveCampaign\Diagnostics\Helper\RunnerManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Console\Style\SymfonyStyle;
use ZendDiagnostics\Runner\Runner;

class ListChecksCommand extends Command
{
    /** @var RunnerManager */
    private $runnerManager;

    /** @var OutputStyle */
    private $style;

    protected function configure()
    {
        $this
            ->setName('monitor:list')
            ->setDescription('Lists Health Checks')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Lists Health Checks of all groups')
            ->addOption('group', 'g', InputOption::VALUE_REQUIRED, 'List checks for given group')
            ->addOption('groups', 'G', InputOption::VALUE_NONE, 'List all registered groups');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->style = new SymfonyStyle($input, $output);
        $this->runnerManager = app(RunnerManager::class);

        switch (true) {
        case $input->getOption('all'):
            $this->listAllChecks($output);
            break;
        case $input->getOption('groups'):
            $this->listGroups($output);
            break;
        default:
            $this->listChecks($input, $output);
            break;
        }
    }

    protected function listChecks(InputInterface $input, OutputInterface $output)
    {
        $group = $input->getOption('group');

        $runner = $this->runnerManager->getRunner($group);

        if (null === $runner) {
            $output->writeln('<error>No such group.</error>');

            return;
        }

        $this->doListChecks($output, $runner);
    }

    /**
     * @param OutputInterface $output
     */
    protected function listAllChecks(OutputInterface $output)
    {
        foreach ($this->runnerManager->getRunners() as $group => $runner) {
            $this->style->title($group);

            $this->doListChecks($output, $runner);
        }
    }

    /**
     * @param OutputInterface $output
     */
    protected function listGroups(OutputInterface $output)
    {
        foreach ($this->runnerManager->getGroups() as $group) {
            $output->writeln($group);
        }
    }

    /**
     * @param OutputInterface $output
     * @param Runner          $runner
     */
    private function doListChecks(OutputInterface $output, Runner $runner)
    {
        $checks = $runner->getChecks();

        if (0 === count($checks)) {
            $output->writeln('<error>No checks configured.</error>');
        }

        foreach ($runner->getChecks() as $alias => $check) {
            $output->writeln(sprintf('<info>%s</info> %s', $alias, $check->getLabel()));
        }
    }
}
