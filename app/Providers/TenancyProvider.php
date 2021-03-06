<?php

namespace App\Providers;

use App\Tenant;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class TenancyProvider extends ServiceProvider
{
    public function boot()
    {
        $this->configureRequests();

        $this->configureQueue();

        $this->configurePop();
    }

    public function configureRequests()
    {
        if (! $this->app->runningInConsole()) {
            $host = $this->app['request']->getHost();

            Tenant::whereDomain($host)->firstOrFail()->configure()->use();
        }
    }

    public function configureQueue()
    {
        $this->app['queue']->createPayloadUsing(function () {
            return $this->app['tenant'] ? ['tenant_id' => $this->app['tenant']->id] : [];
        });

        $this->app['events']->listen(JobProcessing::class, function ($event) {
            if (isset($event->job->payload()['tenant_id'])) {
                Tenant::find($event->job->payload()['tenant_id'])->configure()->use();
            }
        });
    }

    public function configurePop()
    {
        Queue::popUsing('tenancyWorker', function ($pop) {
            $tenants = Tenant::inRandomOrder()->limit(5)->pluck('id');

            return $tenants->map(fn ($tenant) => $pop($tenant))
                ->reject(fn ($jon) => is_null($jon))
                ->first();
        });
    }
}
