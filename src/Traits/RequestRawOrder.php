<?php
namespace JsonAPI\Traits;

trait RequestRawOrder {

    public function getRawOrder(string $default = 'id asc'):string
    {
        $orderList = null;

        if ($this->order) {
            $orderList = explode(",", $this->order);
            foreach ($orderList as $key => $value) {
                $orderList[$key] = trim($value, "- ") . " " . ($value[0]==='-' ? 'DESC' : 'ASC');
            }
        }

        return $orderList ? implode(", ", $orderList) : $default;
    }
}