<?php
namespace serhiikamolov\Laravel\JsonApi\Traits\Serializer\Modifiers;

trait Number
{
    /**
     * @param int|null $value
     * @return int
     */
    protected function modifierNumber(?int $value): int
    {
        return $value ?? 0;
    }
}