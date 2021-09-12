<?php

namespace JsonAPI\Response;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use JsonAPI\Contracts\Serializer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
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
    public function links(array $links): Response
    {
        $originalData = $this->getData(true);
        $originalData['links'] = $originalData['links'] + $links;

        $this->setData($originalData);

        return $this;
    }

    /**
     * @param int $status
     * @param string|array $message
     * @return JsonResponse | Response
     */
    public function error(int $status, $message = ''): Response
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
     * @return Response | JsonResponse
     */
    public function token(string $token, string $type = 'bearer', int $expires = null): Response
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
     * @return Response
     */
    public function data(array $data = []): Response
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
     * @return Response
     */
    public function attach($key, $value): Response
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
     * @return Response|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function debug(array $data = []): Response
    {
        return $this->meta($data, 'debug');
    }

    /**
     * Add a meta data to response
     *
     * @param array $data
     * @param string $key
     * @return Response|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function meta(array $data = [], string $key = 'meta'): Response
    {
        $originalData = $this->getData(true);
        $originalData[$key] = array_merge($originalData[$key] ?? [], $data);
        $this->setData($originalData);
        return $this;
    }

    /**
     * Unset a given key from the response data
     *
     * @param $key
     * @return $this
     */
    public function unset($key): Response
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
    public function paginate(): Response
    {
        $request = $this->getRequest();

        if (!$request->get('page', false)) {
            return $this;
        }

        $requestData = $this->getData(true);
        $items = $requestData['data'];

        $perPage = $request->get('limit', self::PAGINATION_LIMIT);

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = array_slice($items, $perPage * ($currentPage - 1), $perPage);

        $paginator = new LengthAwarePaginator($currentItems, count($items), $perPage, $currentPage);
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
    public function serialize($data, $serializer = null, $key = 'data'): Response
    {
        if (!($serializer instanceof Serializer)) {
            $serializer = $this->getSerializer($serializer);
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
     * @return Response
     */
    public function code(int $code): Response
    {
        $this->setStatusCode($code);

        return $this;
    }

    /**
     * @param array|null $fields
     * @return Serializer
     */
    protected function getSerializer(array $fields = null): Serializer
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
    public function ok(): Response
    {
        return $this->code(Response::HTTP_OK);
    }

    /**
     * @param string $message
     * @return $this
     */
    public function notFound(string $message = 'Not found.'): Response
    {
        return $this->error(Response::HTTP_NOT_FOUND, $message);
    }

    /**
     * @return $this
     */
    public function noContent(): Response
    {
        return $this->code(Response::HTTP_NO_CONTENT);
    }
}
