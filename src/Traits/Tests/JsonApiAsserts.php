<?php
namespace JsonAPI\Traits\Tests;

use Illuminate\Http\JsonResponse;

trait JsonApiAsserts
{
    protected function assertJsonApiResponse(JsonResponse $response)
    {
        $data = $response->getData(true);
        $this->assertArrayHasKey('jsonapi', $data);
        $this->assertArrayHasKey('links', $data);
        $this->assertTrue(isset($data['data']) || isset($data['errors']));
    }

    protected function assertJsonApiAuthResponse(JsonResponse $response)
    {
        $data = $response->getData(true);
        $this->assertArrayHasKey('access_token', $data['data']);
        $this->assertArrayHasKey('token_type', $data['data']);
        $this->assertArrayHasKey('expires_in', $data['data']);
    }

    protected function assertJsonApiResponseError(JsonResponse $response, string $error)
    {
        $data = $response->getData(true);
        $this->assertArrayHasKey('errors', $data);
        return in_array($error, $data['errors']);
    }

}