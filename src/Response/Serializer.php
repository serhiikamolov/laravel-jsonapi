<?php
namespace JsonApi\Response;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class Serializer implements \JsonApi\Contracts\Serializer
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
    protected function processItem(Model $item):array
    {
        $row = [];

        if (!empty($this->fields)) {
            foreach ($this->fields as $field) {
                $row[$field] = $item->$field ?? $this->$field($item);
            }
        } else {
            $row = $item->toArray();
        }

        return $row;
    }
}