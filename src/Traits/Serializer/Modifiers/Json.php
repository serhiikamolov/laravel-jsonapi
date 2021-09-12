<?php
namespace JsonAPI\Traits\Serializer\Modifiers;

trait Json
{
    /**
     * @param string|null $data
     * @return array|null
     */
    protected function modifierJson(?string $data):? array
    {
        return $data ? json_decode($data, true) : null;
    }
}