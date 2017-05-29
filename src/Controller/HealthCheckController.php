<?php

namespace ActiveCampaign\Diagnostics\Controller;

use ActiveCampaign\Diagnostics\Helper\ArrayReporter;
use ActiveCampaign\Diagnostics\Helper\RunnerManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ZendDiagnostics\Runner\Runner;

class HealthCheckController
{
    protected $runnerManager;

    /**
     * @param RunnerManager $runnerManager
     */
    public function __construct(RunnerManager $runnerManager)
    {
        $this->runnerManager = $runnerManager;
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function runAllChecksHttpStatusAction(Request $request)
    {
        $report = $this->runTests($request);

        return new Response('', ($report->getGlobalStatus() === ArrayReporter::STATUS_OK ? 200 : 502));
    }

    /**
     * @param string  $checkId
     * @param Request $request
     *
     * @return Response
     */
    public function runSingleCheckHttpStatusAction($checkId, Request $request)
    {
        $report = $this->runTests($request, $checkId);

        return new Response('', ($report->getGlobalStatus() === ArrayReporter::STATUS_OK ? 200 : 502));
    }

    /**
     * @param Request     $request
     * @param string|null $checkId
     *
     * @return ArrayReporter
     */
    protected function runTests(Request $request, $checkId = null)
    {
        $reporter = new ArrayReporter();

        $runner = $this->getRunner($request);

        $runner->addReporter($reporter);
        $runner->run($checkId);

        return $reporter;
    }

    /**
     * @param Request $request
     *
     * @return Runner
     *
     * @throws \Exception
     */
    private function getRunner(Request $request)
    {
        $group = $request->query->get('group', $this->runnerManager->getDefaultGroup());

        $runner = $this->runnerManager->getRunner($group);

        if ($runner) {
            return $runner;
        }

        throw new \RuntimeException(sprintf('Unknown check group "%s"', $group));
    }
}
