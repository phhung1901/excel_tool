<?php

namespace App\Libs;

use Illuminate\Database\Events\QueryExecuted;

class SqlLogger
{
    protected static ?self $instance = null;

    protected string $prefix = '';

    protected bool $enabled = false;

    protected int $max_time = 10000;

    protected string $log_level = 'alert';

    protected string $log_stack = 'daily_slow_log';

    protected bool $is_full = false; // collect all log

    protected array $middlewares = [];

    protected array $reflection = [];

    protected array $logs = [];

    public function __construct(array $config = [])
    {
        $this->enabled = $config['enabled'] ?? config('app.slow_log.enabled');

        if (! $this->enabled) {
            return;
        }

        if (empty($config)) {
            $config = config('app.slow_log');
        }

        if (\App::runningInConsole()) {
            $this->max_time = $config['time_to_log_cli'];
            $this->prefix = '[CLI]';
        } else {
            $this->max_time = $config['time_to_log'];
            $this->prefix = '[WEB]['.\Request::ip().']['.\Request::url().']';
            $this->is_full = \Request::has('sql_log');
        }

        if ($this->is_full) {
            $this->max_time = 0;
        } else {
            $this->middleware = \Route::getMiddleware();
        }
    }

    public static function make(): ?self
    {
        if (! self::$instance) {
            self::$instance = new self();
        }
        if (self::$instance->enabled) {
            return self::$instance;
        } else {
            return null;
        }
    }

    public function log(QueryExecuted $queryExecuted)
    {
        $sql = $queryExecuted->sql;
        $bindings = $queryExecuted->bindings;
        $time = $queryExecuted->time;

        if ($time < $this->max_time) {
            return;
        }

        foreach ($bindings as $val) {
            $sql = preg_replace('/\?/', "'{$val}'", $sql, 1);
        }

        if ($this->is_full) {
            $this->logs[] = '['.$time.'ms] '.$sql;

            return;
        }
        try {
            $trace = $this->findSource();
            \Log::channel($this->log_stack)
                ->{$this->log_level}($this->prefix."\n".$time.'ms  '.$sql."\n".$trace);
        } catch (\Exception|\Error $ex) {

        }

    }

    public function getLogs(): array
    {
        if ($this->is_full) {
            return $this->logs;
        }

        return [];
    }

    protected function findSource()
    {
        $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT, 50);

        $sources = [];

        foreach ($stack as $index => $trace) {
            if ($index < 2) {
                continue;
            }
            $sources[] = $this->parseTrace($index, $trace);
        }
        $trace = array_map(function ($i) {
            if ($i) {
                return $i->index.' '.$i->name.':'.$i->line;
            }
        }, array_filter($sources));

        return "\n".implode("\n", $trace);
    }

    protected function parseTrace($index, array $trace)
    {
        $frame = (object) [
            'index' => $index,
            'namespace' => null,
            'name' => null,
            'line' => isset($trace['line']) ? $trace['line'] : '?',
        ];

        if (isset($trace['function']) && $trace['function'] == 'substituteBindings') {
            $frame->name = 'Route binding';

            return $frame;
        }

        if (isset($trace['class'])
            && isset($trace['file'])
            && strpos(
                $trace['file'],
                DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'laravel'.DIRECTORY_SEPARATOR.'framework'
            ) === false
            && strpos(
                $trace['file'],
                DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'barryvdh'.DIRECTORY_SEPARATOR.'laravel-debugbar'
            ) === false
        ) {
            $file = $trace['file'];

            if (isset($trace['object']) && is_a($trace['object'], 'Twig_Template')) {
                [$file, $frame->line] = $this->getTwigInfo($trace);
            } elseif (strpos($file, storage_path()) !== false) {
                $hash = pathinfo($file, PATHINFO_FILENAME);

                if (! $frame->name = $this->findViewFromHash($hash)) {
                    $frame->name = $hash;
                }

                $frame->namespace = 'view';

                return $frame;
            } elseif (strpos($file, 'Middleware') !== false) {
                $frame->name = $this->findMiddlewareFromFile($file);

                if ($frame->name) {
                    $frame->namespace = 'middleware';
                } else {
                    $frame->name = $this->normalizeFilename($file);
                }

                return $frame;
            }

            $frame->name = $this->normalizeFilename($file);

            return $frame;
        }

        return false;
    }

    protected function getTwigInfo($trace)
    {
        $file = $trace['object']->getTemplateName();

        if (isset($trace['line'])) {
            foreach ($trace['object']->getDebugInfo() as $codeLine => $templateLine) {
                if ($codeLine <= $trace['line']) {
                    return [$file, $templateLine];
                }
            }
        }

        return [$file, -1];
    }

    /**
     * Find the template name from the hash.
     *
     * @param  string  $hash
     * @return null|string
     */
    protected function findViewFromHash($hash)
    {
        $finder = app('view')->getFinder();

        if (isset($this->reflection['viewfinderViews'])) {
            $property = $this->reflection['viewfinderViews'];
        } else {
            $reflection = new \ReflectionClass($finder);
            $property = $reflection->getProperty('views');
            $property->setAccessible(true);
            $this->reflection['viewfinderViews'] = $property;
        }

        foreach ($property->getValue($finder) as $name => $path) {
            if (sha1($path) == $hash || md5($path) == $hash) {
                return $name;
            }
        }
    }

    protected function findMiddlewareFromFile($file)
    {
        $filename = pathinfo($file, PATHINFO_FILENAME);

        foreach ($this->middlewares as $alias => $class) {
            if (strpos($class, $filename) !== false) {
                return $alias;
            }
        }
    }

    protected function normalizeFilename($path)
    {
        if (file_exists($path)) {
            $path = realpath($path);
        }

        return str_replace(base_path(), '', $path);
    }
}
