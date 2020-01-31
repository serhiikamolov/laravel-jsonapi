<?php

namespace SerhiiKamolov\JsonApi\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use SerhiiKamolov\JsonApi\Response\Response;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param Exception $exception
     * @throws Exception
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param Exception $e
     * @return JsonResponse|\Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Exception $e)
    {
        /** @var Response $response */
        $response = App::make(Response::class);
        $status = $this->isHttpException($e) ? $e->getStatusCode() : 500;

        if ($e instanceof AuthenticationException) {
            $status = Response::HTTP_UNAUTHORIZED;
        }

        if ($e instanceof ModelNotFoundException) {
            $status = Response::HTTP_NOT_FOUND;
        }

        if (env('APP_DEBUG')) {
            $response->debug(
                $this->convertExceptionToArray($e)
            );
        }

        return $response->error($status, $e->getMessage());
    }
}
