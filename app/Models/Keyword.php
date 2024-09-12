<?php

namespace App\Models;

use App\Enum\KeywordStatus;
use App\Enum\TaskStatus;
use App\Models\Data\KeywordRawData;
use App\Services\KeywordService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Orchid\Attachment\Attachable;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\Where;
use Orchid\Filters\Types\WhereIn;
use Orchid\Screen\AsSource;

class Keyword extends Model
{
    use Searchable;
    use AsSource, Filterable;
    use Attachable;


    protected $table = 'keywords';
    protected $guarded = [];

    protected $casts = [
        'raw' => KeywordRawData::class,
        'search_results' => 'array',
        'keyword_intent' => 'array',
        'status' => KeywordStatus::class,
        'task_search_status' => TaskStatus::class,
        'task_search_last' => 'timestamp',
        'task_pos_status' => TaskStatus::class,
        'task_pos_last' => 'timestamp',
//        'search_results' => 'array',
    ];

    protected array $allowedFilters = [
        'id' => Where::class,
        'file_id' => WhereIn::class,
        'status' => Where::class,
    ];

    protected array $allowedSorts = [
        'id',
        'keyword',
        'pos',
        'status',
    ];

    public static function data(
        string $keyword_str,
        File $file,
        KeywordRawData|null $rawData = null,
        int|KeywordStatus $status = KeywordStatus::IMPORTED,
        string|null $language = null,
        string|null $country = null,
    )
    {
        $slug = Str::slug($keyword_str, language: $language);
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
        $keyword->status = $status;
        $keyword->language = $language;
        $keyword->country = $country;
        $keyword->save();
        return $keyword;
    }

    public function getTitle()
    {
        return mb_strtoupper($this->keyword);
    }

    public function file()
    {
        return $this->belongsTo(File::class);
    }

    public function status($status): void
    {
        $this->status = $status;
        $this->save();
    }

}
