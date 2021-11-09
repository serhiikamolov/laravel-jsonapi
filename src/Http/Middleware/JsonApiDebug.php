<?php

namespace JsonAPI\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use JsonAPI\Contracts\Response;

class JsonApiDebug
{
    /**
     * Enable query log for all connections
     */
    protected function enableQueryLog(): void
    {
        $connections = config('database.connections');
        foreach ($connections as $connection => $params) {
            try {
                DB::connection($connection)->enableQueryLog();
            } catch (\Exception $exception) {
                continue;
            }
        }
    }

    /**
     * Get query log from all connections
     */
    protected function getQueryLog(): array
    {
        $queryLog = [];
        $total = 0;
        $connections = config('database.connections');
        foreach ($connections as $connection => $params) {
            try {
                if ($queries = DB::connection($connection)->getQueryLog()) {
                    $queryLog[$connection] = $queries;
                    $total = $total + sizeof($queries);
                }
            } catch (\Exception $exception) {
                continue;
            }
        }

        return [$total, $queryLog];
    }

    public function handle($request, Closure $next)
    {
        if (!config('app.debug', false)) {
            $response = $next($request);
            return $response instanceof Response ? $response->unset('debug') : $response;
        }

        $timeStart = microtime(true);

        $this->enableQueryLog();

        /** @var Response $response */
        $response = $next($request);

        $timeEnd = microtime(true);

        if ($response instanceof Response) {
            list($total, $queries) = $this->getQueryLog();
            return $response->debug([
                'time' => ($timeEnd - $timeStart),
                'request' => $request->input() ?? [],
                'database' => [
                    'queries' => $total,
                    'connections' => $queries
                ]
            ]);
        }

        return $response;
    }
}
