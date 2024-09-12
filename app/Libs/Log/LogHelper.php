<?php

namespace App\Libs\Log;

trait LogHelper
{
    public static function log_info($message, array $context = []): void
    {
        self::log_message('info', $message, $context);
    }

    public static function log_warn($message, array $context = []): void
    {
        self::log_message('warn', $message, $context);
    }

    public static function log_error($message, array $context = []): void
    {
        self::log_message('error', $message, $context);
    }

    public static function log_message($level, $message, array $context = []): void
    {
        [$class, $class_name] = self::log_prefix();
        $context['class'] = $class;
        \Log::log($level, $class_name.' : '.$message, $context);
    }

    public static function log_prefix(): array
    {
        $class = get_called_class();
        $class_name = \Str::afterLast($class, '\\');

        return [$class, $class_name];
    }
}
