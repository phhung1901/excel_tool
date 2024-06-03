<?php

namespace App\Services\SearxService\Data;

use League\Uri\Uri;
use Spatie\LaravelData\Data;

class Pagination extends Data
{
    public function __construct(
        public Uri|string|null $previous,
        public Uri|string|null $current,
        public Uri|string|null $next,
        public array $other_pages = [],
    )
    {}

}