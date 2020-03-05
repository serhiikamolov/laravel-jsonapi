<?php

namespace serhiikamolov\Laravel\JsonApi\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use serhiikamolov\Laravel\JsonApi\Contracts\Response;

class QueryDebug
{
    public function handle($request, Closure $next)
    {
        /** @var Response $response */
        $response = $next($request);

        if ($request instanceof Response && config('app.debug')) {
            $query = DB::getQueryLog();
            return $response->debug([
                'query' => [
                    'total' =>  sizeof($query),
                    'queries' => $query
                ]
            ]);
        }

        return $response;
    }
}