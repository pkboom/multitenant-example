<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Redis;

class TenantJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $tenant;

    public function __construct($tenant)
    {
        $this->tenant = $tenant;
    }

    public function handle()
    {
        /*
         * Balancing job processing across different tenants
         * Option 1
         */
        Redis::throttle('key')
            ->allow(10)
            ->every(60)
            ->then(
                function () {
                    // Job logic...
                },
                function () {
                    return $this->release(10);
                }
            );
    }
}

/*
 * Balancing job processing across different tenants
 * Option 2
 * Each time a job is queued, we use a random queue.
 */
// php artisan queue:work --queue=one,two,three,four,five
// php artisan queue:work --queue=five,one,two,three,four
// php artisan queue:work --queue=four,five,one,two,three
// php artisan queue:work --queue=three,four,five,one,two
// php artisan queue:work --queue=two,three,four,five,one
TenantJob::dispatch($tenant)->onQueue(
    Arr::random(['one', 'two', 'three', 'four', 'five'])
);
// One tenant might dispatch a lot of jobs to all queues.
// With enough queues and workers, this might not be a problem.

/*
 * Balancing job processing across different tenants
 * Option 3
 * App\Providers\TenancyProvider
 */
// php artisan queue:work --name=tenancyWorker
// php artisan queue:work --name=tenancyWorker
// php artisan queue:work --name=tenancyWorker
// php artisan queue:work --name=tenancyWorker
// php artisan queue:work --name=tenancyWorker
TenantJob::dispatch($tenant)->onQueue($tenant);
// Each tenant has its own queue. Our pop strategy will pick 5 tenants at a time,
// and try to get its job.
