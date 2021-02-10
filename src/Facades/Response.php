<?php

namespace JsonAPI\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \JsonAPI\Response\Response
 */
class Response extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \JsonAPI\Response\Response::class;
    }
}
