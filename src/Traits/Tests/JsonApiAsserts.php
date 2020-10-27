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
        $this->assertArrayHasKey('jsonapi', $data);
        $this->assertArrayHasKey('links', $data);
        $this->assertTrue(isset($data['data']) || isset($data['errors']));

        if (isset($data['data']) && $fields) {
            $items = isset($data['data']['items']) ? $data['data']['items'] : $data['data'];
            $item = isset($items[0]) && is_array($items[0]) ? $items[0] : $items;

            $this->assertArrayHasKeys($fields, $item);
        }
    }

    /**
     * @param $keys
     * @param $array
     */
    protected function assertArrayHasKeys($keys, $array)
    {
        foreach ($keys as $key => $field) {
            if (is_array($field)) {
                $this->assertArrayHasKeys($field, $array[$key]);
            } else {
                $this->assertArrayHasKey($field, $array);
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