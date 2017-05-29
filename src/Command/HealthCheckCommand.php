<?php

namespace ActiveCampaign\Diagnostics\Command;

use ActiveCampaign\Diagnostics\Helper\ConsoleReporter;
use ActiveCampaign\Diagnostics\Helper\RunnerManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Style\SymfonyStyle;

class HealthCheckCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('monitor:health')
            ->setDescription('Runs Health Checks')
            ->addArgument(
                'checkName',
                InputArgument::OPTIONAL,
                'The name of the service to be used to perform the health check.'
            )
            ->addOption('nagios', null, InputOption::VALUE_NONE, 'Suitable for using as a nagios NRPE command.')
            ->addOption(
                'group',
                'g',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Run Health Checks for given group'
            )
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Run Health Checks of all groups');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new SymfonyStyle($input, $output);
        $failureCount = 0;

        $groups = $input->getOption('group') ?: array(null);
        $allGroups = $input->getOption('all');
        $checkName = $input->getArgument('checkName');
        $nagios = $input->getOption('nagios');

        if ($nagios) {
            $output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
            $output->setDecorated(false);
        }

        $reporter = new ConsoleReporter($output);

        /** @var RunnerManager $runnerManager */
        $runnerManager = app(RunnerManager::class);

        if ($allGroups) {
            $groups = $runnerManager->getGroups();
        }

        foreach ($groups as $group) {
            if (count($groups) > 1 || $allGroups) {
                $style->title($group);
            }

            $runner = $runnerManager->getRunner($group);

            if (null === $runner) {
                $style->error('No such group.');

                return 1;
            }

            $runner->addReporter($reporter);

            if (0 === count($runner->getChecks())) {
                $style->error('No checks configured.');
            }

            $results = $runner->run($checkName);

            if ($nagios) {
                if ($results->getUnknownCount()) {
                    return 3;
                }

                if ($results->getFailureCount()) {
                    return 2;
                }

                if ($results->getWarningCount()) {
                    return 1;
                }
            }

            $failureCount += $results->getFailureCount();
        }

        return $failureCount > 0 ? 1 : 0;
    }
}
