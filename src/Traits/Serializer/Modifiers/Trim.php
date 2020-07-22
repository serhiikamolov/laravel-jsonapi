<?php
namespace JsonAPI\Traits\Serializer\Modifiers;

trait Trim
{
    /**
     * @param string|null $string
     * @return string
     */
    protected function modifierTrim(?string $string): string
    {
        return trim($string);
    }
}