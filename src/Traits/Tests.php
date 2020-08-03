<?php
namespace JsonAPI\Traits;

use Illuminate\Http\JsonResponse;

trait Tests
{
    protected function assertJsonApiResponse(JsonResponse $response)
    {
        $data = $response->getData(true);
        $this->assertArrayHasKey('jsonapi', $data);
        $this->assertArrayHasKey('links', $data);
        $this->assertTrue(isset($data['data']) || isset($data['errors']));
    }

    protected function assertAuthTokenResponse(JsonResponse $response)
    {
        $data = $response->getData(true);
        $this->assertArrayHasKey('access_token', $data['data']);
        $this->assertArrayHasKey('token_type', $data['data']);
        $this->assertArrayHasKey('expires_in', $data['data']);
    }
}