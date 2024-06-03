<?php

namespace App\Services\SearxService\Data;

use Spatie\LaravelData\Data;

class SearchMetadata extends Data
{
    public function __construct(
        public string $status,
        public string $message = '',
        public string $engine_url = '',
        public string $proxy = '',
        public string $created_at = '',
        public float  $total_time_taken = 0,
    ) {}
}