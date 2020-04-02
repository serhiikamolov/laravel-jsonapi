<?php
namespace serhiikamolov\Laravel\JsonApi\Response;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class Serializer implements \serhiikamolov\Laravel\JsonApi\Contracts\Serializer
{
    protected array $fields = [];

    /**
     * @param Model|Collection $data
     * @return array
     */
    public function serialize($data):array
    {
        if ($data instanceof Collection) {
            return $this->processCollection($data);
        }

        return $this->processItem($data);
    }

    /**
     * @param Collection $collection
     * @return array
     */
    protected function processCollection(Collection $collection)
    {
        $result = [];
        if ($collection->count()) {
            foreach ($collection as $item) {
                $result[] = $this->processItem($item);
            }
        }

        return $result;
    }

    /**
     * @param Model $item
     * @return array
     */
    protected function processItem(Model $item)
    {
        $row = [];

        if (!empty($this->fields)) {
            foreach ($this->fields as $field) {
                $row[$field] = method_exists($this, $field) ? App::call([$this, $field], ['item' => $item]) : $item->$field;
            }
        } else {
            $row = $item->toArray();
        }

        return $row;
    }
}
