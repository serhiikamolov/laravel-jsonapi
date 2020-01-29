<?php
namespace App\Library\Contracts;

interface ApiResponse
{
    public function setLink(array $link);

    public function getLink():array;

    public function error(int $status, $message = '');

    public function debug(array $data=[]);

    public function data(array $data=[]);

    public function paginate();

    public function code(int $code):ApiResponse;

    public function serialize($data, ?Serializer $serializer = null):ApiResponse;

    public function token(string $token):ApiResponse;
}
