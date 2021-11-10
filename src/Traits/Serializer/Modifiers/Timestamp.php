<?php
namespace JsonAPI\Traits\Serializer\Modifiers;

use Carbon\Carbon;
use Carbon\CarbonImmutable;

trait Timestamp
{
    /**
     * @param string|null $date
     * @return int|null
     */
    protected function modifierTimestamp(mixed $date): ?int
    {
        if ($date instanceof CarbonImmutable) {
            return $date->timestamp;
        }
        if (is_string($date)) {
            return Carbon::parse($date)->timestamp;
        }

        return null;
    }
}
