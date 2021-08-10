<?php

namespace App\Http\Middleware;

use Closure;

class TenantSessions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @see https://youtu.be/cjWEZ5SKvIY?t=64
     */
    public function handle($request, Closure $next)
    {
        /**
         * If we change the session domain in cookie, then the session data
         * becomes available for the changed domain. Then the user suddenly
         * can act as a user belong to that domain. So we store tenant_id in session.
         * Another way of preventing this is each domain has a different session file.
         *
         * @see \App\Tenant
         */
        if (! $request->session()->has('tenant_id')) {
            $request->session()->put('tenant_id', app('tenant')->id);

            
            return $next($request);
        }

        if ($request->session()->get('tenant_id') !== app('tenant_id')->id) {
            abort(401);
        }
    }
}
