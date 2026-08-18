<?php

namespace JsonAPI\Traits\Tests;

use Illuminate\Http\JsonResponse;
use Illuminate\Testing\TestResponse;

trait JsonApiAsserts
{
    /**
     * @param array|null $fields
     */
    protected function assertJsonApiResponse(JsonResponse|TestResponse $response, ?array $fields = null): void
    {
        $response = $this->getJsonResponse($response);

        $data = $response->getData(true);
        $this->assertArrayHasKey('links', $data);
        $this->assertTrue(isset($data['data']) || isset($data['errors']));

        if (isset($data['data']) && $fields) {
            $items = isset($data['data']['items']) ? $data['data']['items'] : $data['data'];

            $this->assertJsonApiDataHasKey($fields, $items);
        }
    }

    /**
     * @param array $keys
     * @param array $array
     */
    protected function assertJsonApiDataHasKey(array $keys, array $array): void
    {
        $array = isset($array[0]) && is_array($array[0]) ? $array[0] : $array;
        foreach ($keys as $key => $field) {
            if (is_array($field)) {
                $this->assertArrayHasKey($key, $array);
                $this->assertJsonApiDataHasKey($field, $array[$key]);
            } else {
                $field = is_numeric($key) ? $field : $key;
                $this->assertArrayHasKey($field, $array);
            }
        }
    }

    /**
     * @param array $additionalFields
     */
    protected function assertJsonApiAuthResponse(
        JsonResponse|TestResponse $response,
        array $additionalFields = []
    ): void {
        $this->assertJsonApiResponse($response, [
            'access_token',
            'token_type',
            'expires_in'
        ] + $additionalFields);
    }

    /**
     * @param string $error
     */
    protected function assertJsonApiResponseError(JsonResponse|TestResponse $response, string $error): void
    {
        $response = $this->getJsonResponse($response);

        $data = $response->getData(true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertTrue(in_array($error, $data['errors']));
    }


    protected function assertJsonApiErrors(JsonResponse|TestResponse $response, array $errors): void
    {
        $response = $this->getJsonResponse($response);
        $data = $response->getData(true);
        $this->assertEquals($data['errors'], $errors);
    }

    protected function getJsonResponse(JsonResponse|TestResponse $response): JsonResponse
    {
        $response = $response instanceof TestResponse ? $response->baseResponse : $response;

        if (!$response instanceof JsonResponse) {
            throw new \InvalidArgumentException('Expected JSON response.');
        }

        return $response;
    }
}
