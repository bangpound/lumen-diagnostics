<?php

namespace ActiveCampaign\Diagnostics\Helper;

use Illuminate\Contracts\Container\Container;
use ZendDiagnostics\Runner\Runner;

class RunnerManager
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param null|string $group
     *
     * @return null|Runner
     */
    public function getRunner($group)
    {
        $runnerServiceId = $this->getRunnerServiceId($group);

        return $runnerServiceId ? $this->container->make($runnerServiceId) : null;
    }

    /**
     * @return array|Runner[] key/value $group/$runner
     */
    public function getRunners()
    {
        $runnerServiceIds = array_keys($this->container->make('config')->get('diagnostics.groups'));

        $runners = array();

        foreach ($runnerServiceIds as $serviceId) {
            $runners[$serviceId] = $this->container->make('diagnostics.runner_'. $serviceId);
        }

        return $runners;
    }

    /**
     * @return array|string[]
     */
    public function getGroups()
    {
        $runnerServiceIds = array_keys($this->container->make('config')->get('diagnostics.groups'));

        $groups = array();

        foreach ($runnerServiceIds as $serviceId) {
            $groups[] = $serviceId;
        }

        return $groups;
    }

    /**
     * @return string
     */
    public function getDefaultGroup()
    {
        return $this->container->make('config')->get('diagnostics.default_group');
    }

    /**
     * @param null|string $group
     *
     * @return null|string
     */
    private function getRunnerServiceId($group)
    {
        if (null === $group) {
            $group = $this->getDefaultGroup();
        }

        $runnerServiceId = 'diagnostics.runner_'.$group;

        return $this->container->bound($runnerServiceId) ? $runnerServiceId : Runner::class;
    }
}
