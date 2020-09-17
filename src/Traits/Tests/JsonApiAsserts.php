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
            $this->assertEquals($fields, array_keys(
                isset($data['data']['items']) ? $data['data']['items'] : $data['data']
            ));
        }
    }

    /**
     * @param JsonResponse | TestResponse $response
     */
    protected function assertJsonApiAuthResponse($response): void
    {
        $this->assertJsonApiResponse($response, [
            'access_token',
            'token_type',
            'expires_in'
        ]);
    }

    /**
     * @param JsonResponse | TestResponse $response
     * @param string $error
     * @return bool
     */
    protected function assertJsonApiResponseError($response, string $error): void
    {
        $response = $response instanceof TestResponse ? $response->baseResponse : $response;

        $data = $response->getData(true);
        $this->assertArrayHasKey('errors', $data);
        return in_array($error, $data['errors']);
    }

}