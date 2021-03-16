<?php
namespace JsonAPI\Traits\Serializer\Modifiers;

use Carbon\Carbon;

trait Timestamp
{
    /**
     * @param string|null $date
     * @return int|null
     */
    protected function modifierTimestamp(?string $date): ?int
    {
        return $date ? Carbon::parse($date)->timestamp : null;
    }
}