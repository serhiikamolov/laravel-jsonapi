<?php

namespace JsonAPI\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use JsonAPI\Contracts\Response;

/**
 * Class QueryDebug
 * @package JsonAPI\Http\Middleware
 * @deprecated since v2.0.4
 */
class QueryDebug
{
    public function handle($request, Closure $next)
    {
        if (!config('app.debug', false)) {
            return $next($request);
        }

        DB::enableQueryLog();

        /** @var Response $response */
        $response = $next($request);

        if ($response instanceof Response) {
            $query = DB::getQueryLog();
            return $response->debug([
                'queries' => [
                    'total' =>  sizeof($query),
                    'list' => $query
                ]
            ]);
        }

        return $response;
    }
}