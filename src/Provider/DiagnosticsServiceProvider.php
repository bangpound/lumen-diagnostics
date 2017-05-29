<?php

namespace ActiveCampaign\Diagnostics\Provider;

use ActiveCampaign\Diagnostics\Command\HealthCheckCommand;
use ActiveCampaign\Diagnostics\Command\ListChecksCommand;
use ActiveCampaign\Diagnostics\Controller\HealthCheckController;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use ZendDiagnostics\Runner\Runner;

class DiagnosticsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->configure('diagnostics');

        foreach ($this->app->make('config')->get('diagnostics.groups') as $name => $checks) {
            $this->app->bind('diagnostics.runner_'.$name, function (Container $app) use ($checks) {
                $runner = new Runner();

                $checks = array_map(function ($value) use ($app) {
                    return $app->make('diagnostics.check.'.$value);
                }, array_combine($checks, $checks));

                $runner->addChecks($checks);
                return $runner;
            }, true);
        }

        foreach ($this->app->make('config')->get('diagnostics.checks') as $name => $values) {
            $values = array_pad((array) $values, 2, []);
            $this->app->bind('diagnostics.check.'.$name, function (Container $app) use ($values) {
                return new $values[0](...array_values($values[1]));
            }, true);
            $this->app->tag('diagnostics.check.'.$name, 'diagnostics.check');
        }


        $this->app->get('/health', HealthCheckController::class.'@runAllChecksHttpStatusAction');
        $this->app->get('/health/{checkId}', HealthCheckController::class.'@runSingleCheckHttpStatusAction');

        $this->commands(HealthCheckCommand::class, ListChecksCommand::class);
    }
}
