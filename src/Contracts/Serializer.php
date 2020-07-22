<?php
namespace JsonAPI\Contracts;

use Illuminate\Contracts\Support\Arrayable;

interface Serializer
{
    /**
     * @param Arrayable $data
     * @return array
     */
    public function serialize(Arrayable $data):array;
}
