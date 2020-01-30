<?php
namespace JsonApi\Contracts;

interface Response
{
    public function links(array $links);

    public function error(int $status, $message = '');

    public function debug(array $data=[]);

    public function data(array $data=[]);

    public function paginate();

    public function code(int $code):Response;

    public function serialize($data, ?Serializer $serializer = null):Response;

    public function token(string $token):Response;
}
