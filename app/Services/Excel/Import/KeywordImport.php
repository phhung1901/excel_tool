<?php
namespace App\Services\Excel\Import;

use App\Models\Data\KeywordRawData;
use App\Models\File;
use App\Models\Keyword;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;

class KeywordImport implements ToCollection, WithUpserts, WithHeadingRow
{
    use Importable;

    protected File $file;
    protected $rows;
    private $rows_count = 0;
    public function __construct(File $file, $rows)
    {
        $this->file = $file;
        $this->rows = $rows;
    }

    public function collection(Collection $collection)
    {
        $this->rows_count = $collection->count();
        if ($collection->count() >= 10000) {
            throw new \Exception('Maximum 10k rows per import');
        }else{
            $count = $this->rows;
            foreach ($collection as $row) {
                $row = $row->toArray();
                if (!array_key_exists('keyword_difficulty', $row) || $row['keyword_difficulty'] == ""){
                    $row['keyword_difficulty'] = null;
                }
                if ($count >= 0 && $row['keyword']) {
                    $keyword = Keyword::data(
                        $row['keyword'],
                        $this->file,
                        new KeywordRawData($row['volume'], $row['keyword_difficulty'] ?? null)
                    );
                    $count--;
                }
            }
        }
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function uniqueBy()
    {
        return 'keyword';
    }

    public function getRowCount(): int
    {
        return $this->rows_count;
    }
}
