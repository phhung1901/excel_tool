<?php

namespace App\Libs\Async;

use App\Jobs\AsynableJob;
use Illuminate\Bus\UniqueLock;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Process;
use Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;
use Laravel\SerializableClosure\SerializableClosure;

class AsyncProcess
{
    protected ?int $timeout = 120;

    protected string $command;

    public function withoutTimeout(): self
    {
        $this->timeout = null;

        return $this;
    }

    public function timeout(?int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * @throws PhpVersionNotSupportedException
     */
    public function dispatch(callable|AsynableJob $object): void
    {
        if ($object instanceof \Closure) {
            $object = new SerializableClosure($object);
        } elseif (! $this->shouldDispatch($object)) {
            return;
        }

        $this->command = sprintf(
            '%s %s laravel-async:exec %s %s 2>&1 > /dev/null &',
            config('laravel-async.php_path'),
            base_path('artisan'),
            escapeshellarg(serialize($object)),
            \Auth::user() ? '--user '.\Auth::id() : '',
        );

        $this->exec();
    }

    private function exec(): void
    {
        if ($this->timeout) {
            $this->command .= ' echo $!';
        }

        $result = Process::run($this->command);

        $pid = (int) $result->output();

        if ($this->timeout && $pid) {
            $killCommand = sprintf(
                '(sleep %d && kill %d) 2>&1 > /dev/null &',
                $this->timeout,
                $pid
            );
            Process::run($killCommand);
        }
    }

    protected function shouldDispatch($job): bool
    {
        if (! $job instanceof ShouldBeUnique) {
            return true;
        }
        /** @var AsynableJob $job */
        $job->setUniqueFor($this->timeout);

        return (new UniqueLock(Container::getInstance()->make(Cache::class)))
            ->acquire($job);
    }
}
