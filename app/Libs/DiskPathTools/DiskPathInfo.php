<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-09-19
 * Time: 12:53
 */

namespace App\Libs\DiskPathTools;

use App\Libs\FilenameSanitizer;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;
use Storage;

class DiskPathInfo extends Data implements Wireable
{
    use WireableData;

    public const INFO_SEPARATE = ':';

    public const ARRAY_SEPARATE = ',';

    /** @var array */
    protected $disks_priority = [];

    /**
     * @return DiskPathInfo
     */
    public static function parse(string $string, ?string $file_name = null)
    {
        if (strpos($string, self::INFO_SEPARATE) === false) {
            throw new InvalidArgumentException("Can't parse '$string' to ".DiskPathInfo::class);
        }
        $options = [];
        if ($file_name) {
            $name_length = config('filesystems.name_length');
            $options['name'] = FilenameSanitizer::make_safe_file_name($file_name, $name_length);

        }
        $infos = explode(self::INFO_SEPARATE, $string);

        $disks = explode(self::ARRAY_SEPARATE, array_shift($infos));
        $path = array_shift($infos);
        $size = intval(array_shift($infos));

        return new DiskPathInfo($disks, $path, $size, $options);
    }

    public static function from(...$payloads): static
    {
        if (count($payloads) == 1 && is_string($payloads[0])) {
            return self::parse($payloads[0]);
        } else {
            return parent::from(...$payloads);
        }
    }

    /**
     * DiskPathInfo constructor.
     */
    public function __construct(
        public array|string $disks,
        public string $path,
        public int $size = 0,
        public array $other_info = [],
    ) {
        if (empty($this->disks)) {
            throw new InvalidArgumentException('No disk');
        }

        if (! is_array($disks)) {
            $this->disks = explode(self::ARRAY_SEPARATE, (string) $this->disks);
        }
    }

    /**
     * Get info
     *
     * @param  null  $key
     * @param  null  $default
     * @return array|\ArrayAccess|mixed
     */
    public function info($key = null, $default = null)
    {
        if ($key == null) {
            return $this->other_info;
        } else {
            return Arr::get($this->other_info, $key, $default);
        }
    }

    /**
     * Set info
     *
     * @return $this
     */
    public function setInfo($key, $value = null): self
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->setInfo($k, $v);
            }

            return $this;
        } else {
            Arr::set($this->other_info, $key, (string) $value);
        }

        return $this;
    }

    public function url()
    {
        return Storage::disk($this->bestDisk())
            ->url($this->path());
    }

    /**
     * @param  \DateTimeInterface|null  $expiration
     * @return string
     */
    public function tempUrl($expiration = null, array $options = [])
    {
        if (! $expiration) {
            $expiration = now()->addMinutes(10);
        }
        try {
            return Storage::disk($this->bestDisk())
                ->temporaryUrl($this->path(), $expiration, $options);
        } catch (\Exception $ex) {
            return \URL::temporarySignedRoute('streamer.pub',
                $expiration,
                [
                    'disk' => $this->bestDisk(),
                    'path' => $this->path(),
                ]
            );
        }

    }

    /**
     * Get disks
     *
     * @return mixed
     */
    public function disks(): array
    {
        return $this->disks;
    }

    /**
     * Get best disk name
     *
     * @return mixed
     */
    public function bestDisk(): string
    {
        $disk = $this->disks()[0];
        $app_id = $this->info('app_id');
        if ($app_id && config('filesystems.disks.'.$app_id.'_'.$disk)) {
            return $app_id.'_'.$disk;
        } else {
            return $disk;
        }
    }

    /**
     * Check has disks
     *
     * @param  mixed  ...$disks
     */
    public function hasDisk(...$disks): bool
    {
        foreach ($disks as $disk) {
            if (! in_array($disk, $this->disks)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  false  $check
     * @param  false  $replace
     * @return $this
     */
    public function addDisks(array $disks, $check = false, $replace = false): DiskPathInfo
    {
        if ($replace) {
            $this->disks = [];
        }

        if ($check) {
            foreach ($disks as $disk) {
                if (! $this->hasDisk($disk)
                    && Storage::disk($disk)->exists($this->path)) {
                    $this->disks[] = $disk;
                }
            }
        } else {
            $this->disks = array_merge($this->disks, $disks);
        }

        return $this;
    }

    public function setDisks(...$disks)
    {
        if (is_array($disks[0])) {
            $this->disks = $disks[0];
        } else {
            $this->disks = $disks;
        }
    }

    /**
     * Get path
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * Get the contents of a file.
     *
     * @param  false  $stream
     * @return string|null
     */
    public function file($max_length = 0, $offset = 0)
    {
        if ($max_length + $offset) {
            try {
                if (config('filesystems.disks.'.$this->bestDisk().'__cdn')) {
                    $stream = Storage::disk($this->bestDisk().'__cdn')->readStream($this->path);
                } else {
                    $stream = Storage::disk($this->bestDisk())->readStream($this->path);
                }
            } catch (\Exception $ex) {
                \Log::alert('Can not get from cdn '.$this->path);
                $stream = Storage::disk($this->bestDisk())->readStream($this->path);
            }
            if ($stream) {
                if ($offset) {
                    fseek($stream, $offset);
                }
                if ($max_length) {
                    return fread($stream, $max_length);
                } else {
                    while (! feof($stream)) {
                        return fread($stream, 8192);
                    }
                }
            } else {
                \Log::alert('Can not read stream '.$this->__toString(), $this->toArray());
            }
        }
        try {
            return Storage::disk($this->bestDisk().'__cdn')->get($this->path);
        } catch (\Exception $ex) {
            return Storage::disk($this->bestDisk())->get($this->path);
        }
    }

    public function content($max_length = 0, $offset = 0)
    {
        $cache = config('filesystems.disks.'.$this->bestDisk().'.__cache_store');
        $key = Str::slug($this->bestDisk().'-'.$this->path.'-'.($max_length ?: '0').'-'.$offset ?: '0');
        $tag = $this->bestDisk();
        if ($cache) {
            try {
                $cache_time = (int) config('filesystems.disks.'.$this->bestDisk().'.__cache_time', 86400);
                $content = \Cache::store($cache)->get($key);
                if (! $content) {
                    $content = $this->file($max_length, $offset);
                    try {
                        \Cache::store($cache)->set($key, $content, $cache_time);
                    } catch (\Exception $ex) {
                        \Log::alert('Can not set cache '.$this->path, [$ex->getMessage()]);
                    }
                }

                return $content;
            } catch (\Exception $exception) {
                \Log::alert('Can not connect with cache : '.$exception->getMessage(), $this->toArray());
            }
        }

        return $this->file($max_length, $offset);
    }

    /**
     * Write the contents of a file.
     *
     * @param  string|resource  $contents
     * @param  mixed  $options
     */
    public function put($contents, $options = [], $zip = false): bool
    {
        $uploading_size = strlen($contents);
        $disk = Storage::disk($this->bestDisk());
        if ($zip && $uploading_size > 1024 &&
            ($disk->getAdapter() instanceof AwsS3V3Adapter || $disk->getAdapter() instanceof \League\Flysystem\AwsS3V3\AwsS3V3Adapter)
        ) {
            $contents = gzcompress($contents, 5);
            $uploading_size = strlen($contents);
            $options['ContentEncoding'] = 'gzip';
        }
        $saved = $disk->put($this->path, $contents, $options);
        if (! $saved) {
            return false;
        }

        $this->size(true);

        return $uploading_size == $this->size;
    }

    /**
     * Check file exists
     */
    public function exists(): bool
    {
        return Storage::disk($this->bestDisk())->has($this->path);
    }

    /**
     * Delete the file
     *
     * @return bool
     */
    public function delete()
    {
        return Storage::disk($this->bestDisk())->delete($this->path);
    }

    /**
     * Get size
     */
    public function size(bool $force = false): ?int
    {
        if (! $this->size || $force) {
            $this->size = Storage::disk($this->bestDisk())->size($this->path);
        }

        return $this->size;
    }

    /**
     * Get a temporary URL for the file at the given path.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function getTempUrl(?\DateTimeInterface $expiration = null, array $options = [])
    {
        if (! $expiration) {
            $expiration = now()->addMinutes(10);
        }

        return \Storage::disk($this->bestDisk())->temporaryUrl($this->path, $expiration, $options);
    }

    public function __toString()
    {
        $data['disks'] = implode(self::ARRAY_SEPARATE, $this->disks);
        $data['path'] = $this->path;
        $data['size'] = $this->size;

        return implode(self::INFO_SEPARATE, $data);
    }
}
