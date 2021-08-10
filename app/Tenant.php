<?php

namespace App;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Tenant extends Model
{
    protected $connection = 'landlord';

    protected $guarded = [];

    /**
     * If we change the session domain in cookie, then the session data
     * becomes available for the changed domain. Then the user suddenly
     * can act as a user belong to that domain. So we create a session file for
     * each domain.
     * Another way of preventing this is storing tenant_id.
     *
     * @see \App\Http\Middleware\TenantSessions
     */
    public function configure()
    {
        config([
            'database.connections.tenant.database' => $this->database,
            'session.files' => storage_path('framework/sessions/'.$this->id),
            'cache.prefex' => $this->id,
        ]);

        DB::purge('tenant');

        Cache::purge();

        return $this;
    }

    public function use()
    {
        app()->forgetInstance('tenant');

        app()->instance('tenant', $this);

        return $this;
    }
}
