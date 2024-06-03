<?php
namespace App\Services\Excel\Export;

use App\Models\Enum\KeywordStatus;
use App\Models\File;
use App\Models\Keyword;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class KeywordExport implements FromCollection, WithHeadings
{
    public File $file;

    public function __construct(File $file)
    {
        $this->file = $file;
    }

    public function collection()
    {
        $keywords = Keyword::select('keyword', 'raw')
            ->where('file_id', $this->file->id)
            ->where('status', KeywordStatus::ORIGIN_KEYWORD)
            ->orderBy('keyword', 'asc')
            ->get();
        $keywords = $keywords->map(function ($keyword) {
            return ['keyword' => $keyword->keyword, 'volume' => $keyword->raw->volume, 'kd' => $keyword->raw->kd];
        });

        return Collection::make($keywords);
    }

    public function headings(): array
    {
       return [
           ['Keyword', 'Volume', 'Keyword Difficulty'],
       ];
    }
}
