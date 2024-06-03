<?php

namespace App\Services\SearxService\Data;

use GuzzleHttp\Psr7\Uri;
use Spatie\LaravelData\Data;

class Result extends Data
{
    public string $domain;

    public function __construct(
        public string $url,
        public string $title = '',
        public string $description = '',
    )
    {
        $this->domain = (new Uri($this->url))->getHost();
    }
}