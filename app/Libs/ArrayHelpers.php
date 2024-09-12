<?php

namespace App\Libs;

use App\Services\AppConfiguration;

class ArrayHelpers
{
    public static function makeAppConfigurationsArray()
    {
        return self::makeFlattenConfigArray(AppConfiguration::all());
    }

    public static function makeFlattenConfigArray(array $includes, array $excludes = []): array
    {
        $default_configurations = [];
        foreach ($includes as $namespace) {
            $default_configurations[$namespace] = config($namespace);
        }
        $default_configurations = self::extractKeyValue($default_configurations, []);
        foreach ($default_configurations as $k => $v) {
            foreach ($excludes as $exclude) {
                if (str_starts_with($k, $exclude)) {
                    unset($default_configurations[$k]);
                }
            }
        }

        return $default_configurations;
    }

    /**
     * Kiểm tra mảng có phải kết hợp hay tuần tự
     *
     * @return bool
     */
    public static function isAssoc(array $arr)
    {
        if ($arr === []) {
            return false;
        }

        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * Lưu ý không hỗ trợ array với key là số nguyên liên tục
     */
    public static function extractKeyValue($array, $prefixes = []): array
    {
        $result = [];
        foreach ($array as $k => $v) {
            if (! is_array($v) || ! self::isAssoc($v)) {
                $result[implode('.', array_merge($prefixes, [$k]))] = $v;

                continue;
            }
            if (self::isAssoc($v)) {
                $_result = self::extractKeyValue($v, array_merge($prefixes, [$k]));
                $result = array_merge($result, $_result);
            }
        }

        return $result;
    }

    public static function toKeyValueText(array $array): string
    {
        foreach ($array as $k => $v) {
            $array[$k] = $k.'='.$v;
        }

        return implode("\n", $array);
    }

    public static function fromKeyValueText(string $string): array
    {
        $lines = explode("\n", $string);
        $result = [];
        foreach ($lines as $line) {
            if (! trim($line)) {
                continue;
            }
            [$k, $v] = explode('=', $line, 2);
            $v = trim($v);
            if ($v !== null) {
                $result[$k] = match ($v) {
                    'true' => true,
                    'false' => false,
                    'null' => null,
                    default => $v,
                };
            }
        }

        return $result;
    }
}
