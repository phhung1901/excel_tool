<?php
namespace App\Models\Data;

use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;

class FileMetaData extends Data
{
    public function __construct(
        public int $size,
    )
    {}
}
