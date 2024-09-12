<?php
namespace App\Models\Data;

use Spatie\LaravelData\Data;

class FileMetaData extends Data
{
    public function __construct(
        public int   $size = 0,
        public int   $total_keywords = 0,
        public int   $imported_keywords = 0,
        public int   $serp_keywords = 0,
        public float $progress = 0,
    )
    {
        if ($this->imported_keywords && !$this->progress) {
            $this->updateProgress();
        }
    }

    public function updateProgress()
    {
        $this->progress = round(($this->serp_keywords/$this->imported_keywords)*100, 2);
    }
}
