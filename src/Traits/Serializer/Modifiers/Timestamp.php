<?php
namespace JsonAPI\Traits\Serializer\Modifiers;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

trait Timestamp
{
    /**
     * @param string|null $date
     * @return mixed
     */
    protected function modifierTimestamp(mixed $date): mixed
    {
        if ($date instanceof CarbonInterface) {
            return $date->timestamp;
        }
        if (is_string($date)) {
            return Carbon::parse($date)->timestamp;
        }
        if (is_array($date)) {
            return array_map(function($date) {
                return Carbon::parse($date)->timestamp;
            }, $date);
        }

        return null;
    }
}
