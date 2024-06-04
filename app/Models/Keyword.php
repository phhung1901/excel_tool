<?php

namespace App\Models;

use App\Models\Data\KeywordRawData;
use App\Models\Enum\KeywordStatus;
use App\Services\KeywordService;
use App\Services\Tokenizer\TokenizerClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\Where;
use Orchid\Filters\Types\WhereIn;
use Orchid\Screen\AsSource;

class Keyword extends Model
{
    use Searchable;
    use AsSource, Filterable;

    protected $table = 'keywords';
    protected $guarded = [];

    protected $casts = [
        'raw' => KeywordRawData::class,
        'search_results' => 'array',
        'keyword_intent' => 'array',
    ];

    protected array $allowedFilters = [
        'id' => Where::class,
        'file_id' => WhereIn::class,
    ];

    protected array $allowedSorts = [
        'id',
        'keyword',
        'pos',
    ];

    public static function data(
        string $keyword_str,
        File $file,
        KeywordRawData|null $rawData = null,
        array|null $search_results = null,
        string $pos = null,
        int $status = KeywordStatus::IMPORTED,
    )
    {
        $slug = Str::slug($keyword_str);
        $keyword = Keyword::where('slug', $slug)->first();
        if (!$keyword) {
            $keyword = new Keyword();
        }
        $keyword->keyword = $keyword_str;
        $keyword->slug = $slug;
        $keyword->file_id = $file->id;
        $keyword->source = $file->source;
        $keyword->field = $file->field;
        $keyword->raw = $rawData;
        $keyword->search_results = $search_results;
        $keyword->pos = $pos;
        $keyword->status = $status;
        $keyword->save();
        return $keyword;
    }

    public static function genPOS(string $keyword, $country)
    {
        $pos = (new TokenizerClient())->tokenize($keyword);
        return KeywordService::remove_stopwords($pos, $country);
    }

    public function file()
    {
        return $this->belongsTo(File::class);
    }
}
