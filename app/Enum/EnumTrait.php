<?php

namespace App\Enum;

trait EnumTrait
{
    public static function toOptions(): array
    {
        $results = [];
        foreach (self::cases() as $item) {
            $results[$item->value] = $item->name;
        }

        return $results;
    }

    /**
     * Return name from enum value
     */
    public static function search($value): string
    {
        try {
            return self::tryFrom($value)->name;
        } catch (\Throwable $ex) {
            return '';
        }
    }
}
