<?php
namespace JsonApi\Library;

use JsonApi\Contracts\Serializer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\App;
use JsonApi\ResponseSerializer;

class ApiResponse extends JsonResponse implements \JsonApi\Contracts\ApiResponse
{

    const PAGINATION_LIMIT = 10;

    /**
     * @var array
     */
    protected array $link = [];

    /**
     * ApiResponse constructor.
     * @param mixed|null $data
     * @param int $status
     * @param array $headers
     * @param int $options
     */
    public function __construct(?array $data = null, int $status = 200, array $headers = [], int $options = 0)
    {
        $response = [
            'jsonapi'=> ['version' => '1.0'],
            'link' => $this->getLink(),
            'data' => $data
        ];
        parent::__construct($response, $status, $headers, $options);
    }

    /**
     * @param array $link
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function setLink(array $link)
    {
        $this->link = $link;
        $originalData = $this->getData(true);
        $originalData['link'] = $this->getLink();

        return $this->setData($originalData);
    }

    /**
     * @return array
     */
    public function getLink():array
    {
        return [
            'self' => url()->current()
        ] + $this->link;
    }

    /**
     * @param int $status
     * @param mixed $message
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse|\JsonApi\Contracts\ApiResponse
     */
    public function error(int $status, $message = ''):\JsonApi\Contracts\ApiResponse
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
     * @return Contracts\ApiResponse
     */
    public function token(string $token, string $type = 'bearer', int $expires = 0):\JsonApi\Contracts\ApiResponse
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
     * @return Contracts\ApiResponse
     */
    public function data(array $data=[]):\JsonApi\Contracts\ApiResponse
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
    public function paginate():\JsonApi\Contracts\ApiResponse
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

        return $this->data([
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->count(),
            'items' => $paginator->items(),
        ])->setLink([
            'first_page' => $paginator->url(1),
            'last_page' => $paginator->url($paginator->lastPage()),
            'next_page' => $paginator->nextPageUrl(),
            'prev_page' => $paginator->previousPageUrl(),
        ]);
    }

    /**
     * @param Model|Collection $data
     * @param Serializer|null $serializer
     * @return JsonResponse
     */
    public function serialize($data, ?Serializer $serializer = null): \JsonApi\Contracts\ApiResponse
    {
        if (!$serializer) {
            $serializer = $this->getSerializer();
        }

        return $this->data($serializer->serialize($data));
    }

    public function code(int $code): \JsonApi\Contracts\ApiResponse
    {
        return $this->setStatusCode($code);
    }

    /**
     * @return Serializer
     */
    protected function getSerializer(): Serializer
    {
        return App::make(ResponseSerializer::class);
    }

    protected function getRequest()
    {
        return request();
    }
}
