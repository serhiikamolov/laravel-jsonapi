<?php
namespace SerhiiKamolov\JsonApi\Contracts;

use Symfony\Component\HttpFoundation\JsonResponse;

interface Response
{
    public function links(array $links);

    public function error(int $status, $message = '');

    public function debug(array $data=[]);

    public function data(array $data=[]);

    public function paginate();

    public function code(int $code):JsonResponse;

    public function serialize($data, ?Serializer $serializer = null):JsonResponse;

    public function token(string $token):JsonResponse;
}
