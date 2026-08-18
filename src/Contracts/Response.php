<?php

namespace JsonAPI\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface Response
{
    public function links(array $links): static;

    public function error(int $status, string|array $message = ''): static;

    public function debug(array $data = []): static;

    public function data(array $data = []): static;

    public function meta(array|string $data = [], string $key = 'meta'): static;

    public function paginate(): mixed;

    public function code(int $code): static;

    public function serialize(
        Model|Collection|LengthAwarePaginator $data,
        Serializer|array|null $serializer = null,
        string $key = 'data'
    ): static;

    public function token(string $token, string $type = 'bearer', ?int $expires = null): static;

    public function unset(string|array $key): static;
}
