<?php

namespace JsonAPI\Response;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use JsonAPI\Contracts\Serializer;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;

class Response extends JsonResponse implements \JsonAPI\Contracts\Response
{

    //const JSONAPI_VERSION = '1.0';

    const PAGINATION_LIMIT = 10;

    protected string $serializer = \JsonAPI\Response\Serializer::class;

    /**
     * ApiResponse constructor.
     * @param mixed|null $data
     * @param int $status
     * @param array $headers
     * @param int $options
     */
    public function __construct(?array $data = null, int $status = 200, array $headers = [], int $options = 0)
    {
        parent::__construct([
            //'jsonapi'=> ['version' => static::JSONAPI_VERSION],
            'links' => [
                'self' => App::make(UrlGenerator::class)->current()
            ],
            'meta' => [],
            'data' => $data
        ], $status, $headers, $options);
    }

    /**
     * @param array $links
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse | Response
     */
    public function links(array $links): static
    {
        $originalData = $this->getData(true);
        $originalData['links'] = $originalData['links'] + $links;

        $this->setData($originalData);

        return $this;
    }

    /**
     * @param int $status
     * @param string|array $message
     * @return $this
     */
    public function error(int $status, $message = ''): static
    {
        $data = $this->getData(true);
        $data["errors"] = is_array($message) ? $message : ['messages' => [$message]];
        unset($data["data"]);

        $this->setStatusCode($status);

        $this->setData($data);

        return $this;
    }

    /**
     * Return response with JWT token
     *
     * @param string $token
     * @param string $type
     * @param int|null $expires
     * @return $this | JsonResponse
     */
    public function token(string $token, string $type = 'bearer', int $expires = null): static
    {
        $this->data([
            'access_token' => $token,
            'token_type' => $type,
            'expires_in' => $expires ?? Auth::guard('api')->factory()->getTTL() * 60
        ]);

        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function data(array $data = []): static
    {
        $originalData = $this->getData(true);
        $originalData['data'] = $data;
        $this->setData($originalData);

        return $this;
    }

    /**
     * Attach field to the response's data
     *
     * @param string|array $key
     * @param $value
     * @return $this
     */
    public function attach($key, $value): static
    {
        $originalData = $this->getData(true);

        if (is_array($key)) {
            $originalData['data'] = array_merge($originalData['data'], $key);
        } else {
            $originalData['data'] = Arr::add($originalData['data'], $key, $value);
        }
        $this->setData($originalData);

        return $this;
    }

    /**
     * Add an additional debug information
     *
     * @param array $data
     * @return $this|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function debug(array $data = []): static
    {
        return $this->meta($data, 'debug');
    }

    /**
     * Add a meta data to response
     *
     * @param array|string $data
     * @param string $key
     * @return $this|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function meta(array|string $data = [], string $key = 'meta'): static
    {
        $originalData = $this->getData(true);
        $originalData[$key] = is_array($data) ? array_merge($originalData[$key] ?? [], $data) : $data;
        $this->setData($originalData);
        return $this;
    }

    /**
     * Unset a given key from the response data
     *
     * @param $key
     * @return $this
     */
    public function unset($key): static
    {
        $originalData = $this->getData(true);
        if (Arr::has($originalData, $key)) {
            $originalData = Arr::except($originalData, $key);
        }
        $this->setData($originalData);
        return $this;
    }

    /**
     * @param Collection $items
     * @return mixed
     */
    public function paginate(): mixed
    {
        $request = $this->getRequest();

        if (!$request->get('page', false)) {
            return $this;
        }

        $requestData = $this->getData(true);
        $items = $requestData['data'];

        $perPage = $request->get('limit', self::PAGINATION_LIMIT);

        $currentPage = Paginator::resolveCurrentPage();
        $currentItems = array_slice($items, $perPage * ($currentPage - 1), $perPage);

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator($currentItems, count($items), $perPage, $currentPage);
        $paginator->appends('limit', request('limit'));
        $paginator->setPath(url()->current());

        return $this->paginatorToData($paginator);
    }

    /**
     * @param LengthAwarePaginator $paginator
     * @param array|null $items
     * @return mixed
     */
    protected function paginatorToData(LengthAwarePaginator $paginator, ?array $items = null)
    {
        return $this
            ->meta([
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->count(),
            ])->data(
                $items ?? $paginator->items()
            )->links([
                'first_page' => $paginator->url(1),
                'last_page' => $paginator->url($paginator->lastPage()),
                'next_page' => $paginator->nextPageUrl(),
                'prev_page' => $paginator->previousPageUrl(),
            ]);
    }

    /**
     * @param Model|Collection|LengthAwarePaginator $data
     * @param Serializer|array|null $serializer
     * @param string $key
     * @return $this
     */
    public function serialize($data, $serializer = null, $key = 'data'): static
    {
        if (!($serializer instanceof Serializer)) {
            $serializer = $this->serializer($serializer);
        }

        if ($data instanceof \Illuminate\Support\Collection) {
            $this->meta([
                'total' => $data->count(),
            ]);
        }

        if ($data instanceof LengthAwarePaginator) {
            return $this->paginatorToData(
                $data,
                $serializer->serialize($data->getCollection())
            );
        } else {
            return $this->meta($serializer->serialize($data), $key);
        }
    }

    /**
     * Add status code to the response
     *
     * @param int $code
     * @return $this
     */
    public function code(int $code): static
    {
        $this->setStatusCode($code);

        return $this;
    }

    /**
     * @param array|null $fields
     * @deprecated
     * @return Serializer
     */
    protected function getSerializer(array $fields = null): Serializer
    {
        return $this->serializer($fields);
    }

    /**
     * @param array|null $fields
     * @return Serializer
     */
    protected function serializer(array $fields = null): Serializer
    {
        return App::make($this->serializer, ['fields' => $fields]);
    }

    /**
     * @return mixed
     */
    protected function getRequest()
    {
        return App::make('request');
    }

    /**
     * @return $this
     */
    public function ok(): static
    {
        return $this->code(Response::HTTP_OK);
    }

    /**
     * @param string $message
     * @return $this
     */
    public function notFound(string $message = 'Not found.'): static
    {
        return $this->error(Response::HTTP_NOT_FOUND, $message);
    }

    /**
     * @return $this
     */
    public function noContent(): static
    {
        return $this->code(Response::HTTP_NO_CONTENT);
    }
}
