<?php

namespace JsonAPI\Response;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use JsonAPI\Exceptions\SerializerException;
use JsonAPI\Traits\Serializer\Modifiers\Number;
use JsonAPI\Traits\Serializer\Modifiers\Timestamp;
use JsonAPI\Traits\Serializer\Modifiers\Trim;

class Serializer implements \JsonAPI\Contracts\Serializer
{
    use Timestamp, Trim, Number;

    protected array $fields = [];

    /**
     * Serializer constructor.
     * @param array|null $fields
     */
    public function __construct(?array $fields = null)
    {
        $this->fields = $fields ?? $this->fields();
    }

    /**
     * @return array
     */
    public function fields():array
    {
        return $this->fields;
    }

    /**
     * @param Arrayable $data
     * @return array
     * @throws SerializerException
     */
    public function serialize(Arrayable $data): array
    {
        if ($data instanceof Collection) {
            return $this->processCollection($data);
        }

        return $this->processItem($data);
    }

    /**
     * @param Collection $collection
     * @return array
     * @throws SerializerException
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
     * @param Arrayable $item
     * @return mixed
     * @throws SerializerException
     */
    protected function processItem(Arrayable $item)
    {
        $row = [];

        if (!empty($this->fields)) {
            foreach ($this->fields as $field) {
                $modifiers = $this->extractModifiers($field);

                $row[$field] = $this->processModifiers(
                    $this->callMethod($item, $field), // try to call a custom method first
                    $modifiers
                );
            }
        } else {
            $row = $item->toArray();
        }

        return $row;
    }

    /**
     * Try to call a custom method or return default value of the field
     *
     * @param Arrayable $item
     * @param string $field
     * @return mixed
     */
    protected function callMethod(Arrayable $item, string $field)
    {
        if (method_exists($this, $field)) {
            return App::call([$this, $field], ['item' => $item]);
        }
        return $item->$field;
    }

    /**
     * @param $value
     * @param array $modifiers
     * @return mixed
     * @throws SerializerException
     */
    protected function processModifiers($value, array $modifiers = [])
    {
        if ($value && !empty($modifiers)) {
            foreach ($modifiers as $modifier) {
                $method = 'modifier' . ucfirst(trim($modifier));
                if (method_exists($this, $method)) {
                    $value = $this->$method($value);
                } else {
                    throw new SerializerException("Invalid modifier: $modifier");
                }
            }
        }

        return $value;
    }

    /**
     * @param string $field
     * @return array
     * @throws SerializerException
     */
    protected function extractModifiers(string &$field): array
    {
        $fieldParts = explode(":", $field);
        $field = $fieldParts[0];

        if (sizeof($fieldParts) === 1) {
            return [];
        } elseif (sizeof($fieldParts) > 2) {
            throw new SerializerException("Invalid modifiers format: $field");
        }

        return explode(',', $fieldParts[1]);
    }
}
