<?php
namespace serhiikamolov\Laravel\JsonApi\Traits\Serializer\Modifiers;

use Carbon\Carbon;

trait Timestamp
{
    /**
     * @param string|null $date
     * @return int
     */
    protected function modifierTimestamp(?string $date): int
    {
        return Carbon::parse($date)->timestamp;
    }
}