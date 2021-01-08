<?php
namespace JsonAPI\Traits\Serializer\Modifiers;

trait Json
{
    /**
     * @param string $data
     * @return array
     */
    protected function modifierJson(string $data): array
    {
        return json_decode($data);
    }
}