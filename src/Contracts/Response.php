<?php
namespace JsonApi\Contracts;

interface Response
{
    public function setLink(array $link);

    public function getLink():array;

    public function error(int $status, $message = '');

    public function debug(array $data=[]);

    public function data(array $data=[]);

    public function paginate();

    public function code(int $code):Response;

    public function serialize($data, ?Serializer $serializer = null):Response;

    public function token(string $token):Response;
}
