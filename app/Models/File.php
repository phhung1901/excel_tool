<?php

namespace App\Models;

use App\Models\Data\FileMetaData;
use App\Models\Enum\FileStatus;
use Illuminate\Database\Eloquent\Model;
use Orchid\Attachment\Attachable;
use Orchid\Screen\AsSource;

class File extends Model
{
    use AsSource, Attachable;

    protected $table = 'files';
    protected $guarded = [];
    protected $casts = [

    ];

    public static function data(
        string $file_name,
        FileMetaData|null $meta = null,
        string $suffix = null,
        string $source = null,
        string $campaign = null,
        string $field = null,
    )
    {
        $file = File::where('name', $file_name)->first();
        if (!$file){
            $file = new File();
        }
        $file->name = $file_name;
        if ($suffix){
            $file->suffix = $suffix;
        }
        if ($source){
            $file->source = $source;
        }
        if ($campaign){
            $campaign ?? $file->campaign = $campaign;
        }
        if ($field){
            $field ?? $file->field = $field;
        }
        if ($meta){
            $meta ?? $file->meta = $meta;
        }

        $file->save();
        return $file;
    }

    public function keywords()
    {
        return $this->hasMany(Keyword::class);
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
