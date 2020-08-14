<?php
namespace JsonAPI\Contracts;

use Illuminate\Contracts\Support\Arrayable;

interface Serializer
{
    /**
     * Default list of fields
     * @return array
     */
    public function fields():array;

    /**
     * @param Arrayable $data
     * @return array
     */
    public function serialize(Arrayable $data):array;
}
