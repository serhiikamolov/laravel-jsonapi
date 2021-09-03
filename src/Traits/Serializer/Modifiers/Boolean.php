<?php

namespace JsonAPI\Traits\Serializer\Modifiers;

trait Boolean
{
    /**
     * @param mixed $data
     * @return bool
     */
    protected function modifierBoolean(mixed $data): bool
    {
        return $data && (int)$data === 1;
    }
}