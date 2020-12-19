<?php

namespace JsonAPI\Traits\Tests;

use Illuminate\Http\JsonResponse;
use Illuminate\Testing\TestResponse;

trait JsonApiAsserts
{
    /**
     * @param JsonResponse | TestResponse $response
     * @param array|null $fields
     */
    protected function assertJsonApiResponse($response, array $fields = null)
    {
        $response = $response instanceof TestResponse ? $response->baseResponse : $response;

        $data = $response->getData(true);
        $this->assertArrayHasKey('links', $data);
        $this->assertTrue(isset($data['data']) || isset($data['errors']));

        if (isset($data['data']) && $fields) {
            $items = isset($data['data']['items']) ? $data['data']['items'] : $data['data'];

            $this->assertJsonApiDataHasKey($fields, $items);
        }
    }

    /**
     * @param $keys
     * @param $array
     */
    protected function assertJsonApiDataHasKey($keys, $array)
    {
        $array = isset($array[0]) && is_array($array[0]) ? $array[0] : $array;
        foreach ($keys as $key => $field) {
            if (is_array($field)) {
                $this->assertArrayHasKey($key, $array);
                $this->assertJsonApiDataHasKey($field, $array[$key]);
            } else {
                $field = is_numeric($key) ? $field : $key;
                $this->assertArrayHasKey($field, $array ?? []);
            }
        }
    }

    /**
     * @param JsonResponse | TestResponse $response
     * @param array|null $additionalFields
     */
    protected function assertJsonApiAuthResponse($response, array $additionalFields = [])
    {
        $this->assertJsonApiResponse($response, [
            'access_token',
            'token_type',
            'expires_in'
        ] + $additionalFields);
    }

    /**
     * @param JsonResponse | TestResponse $response
     * @param string $error
     * @return bool
     */
    protected function assertJsonApiResponseError($response, string $error)
    {
        $response = $response instanceof TestResponse ? $response->baseResponse : $response;

        $data = $response->getData(true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertTrue(in_array($error, $data['errors']));
    }


    protected function assertJsonApiErrors($response, array $errors)
    {
        $response = $response instanceof TestResponse ? $response->baseResponse : $response;
        $data = $response->getData(true);
        $this->assertEquals($data['errors'], $errors);
    }
}