<?php

namespace App\Libs\System;

use Illuminate\Support\ServiceProvider;

class MemoryLimitProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->setMemoryLimit();
        }
    }

    protected function setMemoryLimit()
    {
        $current_memory_limit = ini_get('memory_limit');
        $cli_memory_limit = config('app.cli_memory_limit');
        if ($current_memory_limit == -1) {
            return;
        }
        if ($cli_memory_limit == -1) {
            ini_set('memory_limit', $cli_memory_limit);
        } else {
            ini_set('memory_limit', max($this->convertReadableSizeToByte($current_memory_limit), $this->convertReadableSizeToByte($cli_memory_limit)));
        }
    }

    protected function convertReadableSizeToByte($size_string): int
    {
        $size = (int) $size_string;
        $string = str_replace($size, '', $size_string);

        return match ($string) {
            'M','m' => $size * 1048576,
            'K','k' => $size * 1024,
            'G','g' => $size * 1073741824,
            default => $size,
        };
    }
}
