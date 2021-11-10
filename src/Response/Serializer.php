<?php

namespace JsonAPI\Response;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use JsonAPI\Exceptions\SerializerException;
use JsonAPI\Traits\Serializer\Modifiers\Boolean;
use JsonAPI\Traits\Serializer\Modifiers\Json;
use JsonAPI\Traits\Serializer\Modifiers\Number;
use JsonAPI\Traits\Serializer\Modifiers\Timestamp;
use JsonAPI\Traits\Serializer\Modifiers\Trim;

class Serializer implements \JsonAPI\Contracts\Serializer
{
    use Timestamp, Trim, Number, Json, Boolean;

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
    public function fields(): array
    {
        return $this->fields;
    }

    /**
     * Limit the set of fields for serialization
     *
     * @param array $fields
     * @return array
     */
    public function only(array $fields): \JsonAPI\Contracts\Serializer
    {
        $onlyFields = [];

        foreach ($this->fields as $key => $value) {
            if ((is_string($key) && in_array($key, $fields)) || in_array($value, $fields)) {
                $onlyFields[$key] = $value;
            }
        }

        $this->fields = $onlyFields;

        return $this;
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
            foreach ($this->fields as $key => $field) {

                $modifiers = [];
                $modifiers = $this->extractModifiers(is_string($key) && is_string($field) ? "$key:$field" : $field);
                $field = is_string($key) ? $key : $field;

                $row[$field] = $this->processModifiers(
                    is_string($field)
                        ? $this->callMethod($item, $field)
                        : $this->callMethod($item, $key), // try to call a custom method first
                    $modifiers,
                    $item
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
        $fieldCamelCase = Str::camel($field);

        if (method_exists($this, $field)) {
            return App::call([$this, $field], ['item' => $item, 'model' => $item]);
        } else if (method_exists($this, $fieldCamelCase)) {
            return App::call([$this, $fieldCamelCase], ['item' => $item, 'model' => $item]);
        }
        return $item->$field ?? $item->$fieldCamelCase;
    }

    /**
     * @param $value
     * @param array $modifiers
     * @param Arrayable $item
     * @return array|mixed
     * @throws SerializerException
     */
    protected function processModifiers($value = null, array $modifiers = [], Arrayable $item = null)
    {
        if (!empty($modifiers)) {
            foreach ($modifiers as $modifier) {
                // check whether a  serializer class given
                $modifier = is_string($modifier) && class_exists($modifier) ? new $modifier() : $modifier;

                if ($modifier instanceof \JsonAPI\Contracts\Serializer) {
                    $value = $value ? $modifier->serialize($value) : null;
                } else {
                    $method = 'modifier' . ucfirst(trim($modifier));
                    if (method_exists($this, $method)) {
                        $value = $this->$method($value);
                    } else {
                        if ($item) {
                            $value = Arr::get($item, $modifier, null);
                        }
                        if (empty($value)) {
                            throw new SerializerException("Invalid modifier: $modifier");
                        }
                    }
                }
            }
        }

        return $value;
    }

    /**
     * @param mixed $field
     * @return array
     * @throws SerializerException
     */
    protected function extractModifiers(mixed $field): array
    {
        if (is_string($field)) {
            $fieldParts = explode(":", $field);
            $field = $fieldParts[0];

            if (sizeof($fieldParts) === 1) {
                return [];
            } elseif (sizeof($fieldParts) > 2) {
                throw new SerializerException("Invalid modifiers format: $field");
            }

            return explode(',', $fieldParts[1]);
        }

        return is_array($field) ? $field : [$field];
    }
}
