<?php
namespace App\Models\Data;

use Spatie\LaravelData\Data;

class KeywordRawData extends Data
{
    public function __construct(
        public ?int $volume = null,
        public ?int $kd = null,
    )
    {}
}
