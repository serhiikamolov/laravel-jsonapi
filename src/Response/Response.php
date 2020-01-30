<?php
namespace JsonApi\Response;

use Illuminate\Contracts\Routing\UrlGenerator;
use JsonApi\Contracts\Serializer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\App;

class Response extends JsonResponse implements \JsonApi\Contracts\Response
{

    const JSONAPI_VERSION = '1.0';

    const PAGINATION_LIMIT = 10;

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
            'jsonapi'=> ['version' => static::JSONAPI_VERSION],
            'links' => [
                'self' => App::make(UrlGenerator::class)->current()
            ],
            'data' => $data
        ], $status, $headers, $options);
    }

    /**
     * @param array $links
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function links(array $links)
    {
        $originalData = $this->getData(true);
        $originalData['links'] = $originalData['links'] + $links;

        return $this->setData($originalData);
    }

    /**
     * @param int $status
     * @param mixed $message
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse|\JsonApi\Contracts\Response
     */
    public function error(int $status, $message = ''):\JsonApi\Contracts\Response
    {
        $data = $this->getData(true);
        $data["errors"] = is_array($message) ? $message : [$message];
        unset($data["data"]);

        $this->setStatusCode($status);

        return $this->setData($data);
    }

    /**
     * Return response with token
     *
     * @param string $token
     * @param string $type
     * @param int $expires
     * @return \JsonApi\Contracts\Response
     */
    public function token(string $token, string $type = 'bearer', int $expires = 0):\JsonApi\Contracts\Response
    {
        return $this->data([
            'access_token' => $token,
            'token_type' => $type,
            'expires_in' => $expires //auth()->factory()->getTTL() * 60
        ]);
    }
    /**
     * @param array $data
     * @param array $data
     * @return Contracts\Response
     */
    public function data(array $data=[]):\JsonApi\Contracts\Response
    {
        $originalData = $this->getData(true);
        $originalData['data'] = $data;
        return $this->setData($originalData);
    }

    /**
     * Add an additional debug information
     *
     * @param array $data
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function debug(array $data=[])
    {
        $originalData = $this->getData(true);
        $originalData['debug'] = $data;
        return $this->setData($originalData);
    }

    /**
     * @param Collection $items
     * @return mixed
     */
    public function paginate():\JsonApi\Contracts\Response
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
        return $this->data([
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->count(),
            'items' => $items ?? $paginator->items(),
        ])->links([
            'first_page' => $paginator->url(1),
            'last_page' => $paginator->url($paginator->lastPage()),
            'next_page' => $paginator->nextPageUrl(),
            'prev_page' => $paginator->previousPageUrl(),
        ]);
    }
    /**
     * @param Model|Collection|LengthAwarePaginator $data
     * @param Serializer|null $serializer
     * @return JsonResponse
     */
    public function serialize($data, ?Serializer $serializer = null): \JsonApi\Contracts\Response
    {
        if (!$serializer) {
            $serializer = $this->getSerializer();
        }

        if ($data instanceof LengthAwarePaginator) {

            return $this->paginatorToData(
                $data,
                $serializer->serialize($data->getCollection())
            );

        } else {
            return $this->data($serializer->serialize($data));
        }
    }

    /**
     * Add status code to the response
     *
     * @param int $code
     * @return \JsonApi\Contracts\Response
     */
    public function code(int $code): \JsonApi\Contracts\Response
    {
        return $this->setStatusCode($code);
    }

    /**
     * @return Serializer
     */
    protected function getSerializer(): Serializer
    {
        return App::make(\JsonApi\Response\Serializer::class);
    }

    /**
     * @return mixed
     */
    protected function getRequest()
    {
        return App::make('request');
    }
}
