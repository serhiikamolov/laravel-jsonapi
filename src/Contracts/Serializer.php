<?php
namespace serhiikamolov\Laravel\JsonApi\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface Serializer
{
    /**
     * @param Model|Collection $data
     * @return array
     */
    public function serialize($data):array;
}
