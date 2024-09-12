<?php

namespace App\Models;

use App\Enum\FileField;
use App\Enum\FileSource;
use App\Enum\FileStatus;
use App\Enum\FileType;
use App\Models\Data\FileMetaData;
use Illuminate\Database\Eloquent\Model;
use Orchid\Attachment\Attachable;
use Orchid\Screen\AsSource;

/**
 * @property \Illuminate\Database\Eloquent\Collection $attachment
 */
class File extends Model
{
    use AsSource, Attachable;

    const DEFAULT_LANGUAGE = 'en';
    const DEFAULT_COUNTRY = 'US';

    protected $table = 'files';
    protected $guarded = [];

    protected $with = ['attachment'];

    protected $casts = [
        'suffix' => FileType::class,
        'source' => FileSource::class,
//        'field' => FileField::class,
        'status' => FileStatus::class,
        'meta' => FileMetaData::class . ":default",
    ];

    public static function boot()
    {
        parent::boot();
        static::deleting(function (File $model) {
            $model->attachment->each->delete();
        });
    }

    public function keywords()
    {
        return $this->hasMany(Keyword::class, 'file_id');
    }

    public static function data(
        string $file_name,
        string $country,
        array $meta = [],
        string $source = null,
        string $field = null,
        ?string $language = null,
    )
    {
        $file = File::where('name', $file_name)->first();
        if (!$file) {
            $file = new File();
        }
        $file->name = $file_name;
        $file->country = $country;
        $file->meta = $meta;
        $file->source = $source;
        $file->language = $language;
        $file->field = $field;
        $file->save();

        return $file;
    }

    public function getPath()
    {
        $attachment = $this->attachment()->first();
        return $attachment->path.$attachment->name.".".$attachment->extension;
    }

    public function status($status): void
    {
        $this->status = $status;
        $this->save();
    }
}
