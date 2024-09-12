<?php

namespace App\Libs\Data;

trait IgnoreNullToDB
{
    public function toJson($options = 0): string
    {
        $result = $this->transform();
        foreach ($result as $k => $v) {
            if ($v === null) {
                unset($result[$k]);
            }
        }

        return count($result) ? json_encode($result, $options) : '{}';
    }
}
