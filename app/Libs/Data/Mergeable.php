<?php

namespace App\Libs\Data;

trait Mergeable
{
    public function merge(self $other): self
    {
        foreach ($other as $k => $v) {
            $this->$k = $v;
        }

        return $this;
    }
}
