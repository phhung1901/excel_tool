<?php

namespace App\Libs\Data;

class HasCachedResult
{
    protected array $cached_results = [];

    protected function tryCached($key, callable $compute)
    {
        if (! isset($this->cached_results[$key])) {
            $this->cached_results[$key] = call_user_func($compute);
        }

        return $this->cached_results[$key];
    }
}
