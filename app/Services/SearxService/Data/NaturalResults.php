<?php

namespace App\Services\SearxService\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class NaturalResults extends Data
{
    public function __construct(
        public SearchMetadata $search_metadata,
        public ?int $total = null,
        /** @var Result[] */
        public ?DataCollection $organic_results = null,
        public ?Pagination $pagination = null,
        public ?Pagination $searx_pagination = null,
    ) {}

    public function uniqueByDomain() {
        $unique_items = [];
        $unique_domains = [];
        /** @var Result $item */
        foreach ($this->organic_results as $item) {
            if (!in_array($item->domain, $unique_domains)) {
                $unique_items[] = $item;
                $unique_domains[] = $item->domain;
            }
        }
        $this->organic_results = Result::collection($unique_items);
        return $this;
    }
}