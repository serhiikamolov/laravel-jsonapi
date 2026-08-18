<?php

namespace JsonAPI\Traits;

trait RequestRawOrder
{
    /**
     * Return the list of fields that may be used in raw ordering.
     *
     * Override this method in the request class:
     *
     * protected function rawOrderFields(): array
     * {
     *     return ['id', 'created_at'];
     * }
     */
    protected function rawOrderFields(): array
    {
        return [];
    }

    public function getRawOrder(string $default = 'id asc'): string
    {
        $order = $this->order ?? null;
        $allowedFields = $this->rawOrderFields();

        if (!$order || empty($allowedFields)) {
            return $default;
        }

        $orderList = [];
        foreach (explode(',', $order) as $value) {
            $value = trim($value);
            $field = ltrim($value, '-');

            if (!in_array($field, $allowedFields, true)) {
                continue;
            }

            $orderList[] = $field . ' ' . (str_starts_with($value, '-') ? 'DESC' : 'ASC');
        }

        return $orderList ? implode(', ', $orderList) : $default;
    }
}
