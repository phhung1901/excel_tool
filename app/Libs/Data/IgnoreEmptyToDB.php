<?php

namespace App\Libs\Data;

trait IgnoreEmptyToDB
{
    public function toJson($options = 0): string
    {
        $result = $this->transform();
        foreach ($result as $k => $v) {
            if (empty($v)) {
                unset($result[$k]);
            }
        }

        return json_encode($result, $options);
    }
}
