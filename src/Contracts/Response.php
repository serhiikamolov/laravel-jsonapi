<?php

namespace JsonAPI\Contracts;

interface Response
{
    public function links(array $links);

    public function error(int $status, $message = '');

    public function debug(array $data = []);

    public function data(array $data = []);

    public function meta(array $data = [], string $key = 'meta');

    public function paginate();

    public function code(int $code);

    public function serialize($data, $serializer = null);

    public function token(string $token);

    public function unset($key);
}
