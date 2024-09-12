<?php

namespace App\Libs;

use App\Models\Document;
use App\Models\Ds\RawDocument;

class Locker
{
    public static function documentStartEdit(int|Document|RawDocument $document, $user = null, $timeout = 120): bool
    {
        [$key, $lock_key] = self::makeKeys($document);
        $user ??= \Auth::user();
        $locked = \Cache::lock($lock_key, $timeout, $user)->get();
        if ($locked) {
            \Cache::put($key, $user->email, $timeout);
        }

        return self::documentEditingUser($document) == $user->email || $locked;
    }

    public static function documentEditingUser(int|Document|RawDocument $document, $user = null, $timeout = 120): ?string
    {
        [$key, $lock_key] = self::makeKeys($document);

        return \Cache::get($key);
    }

    public static function documentFinishEdit(int|Document|RawDocument $document, $user = null, $timeout = 120): void
    {
        [$key, $lock_key] = self::makeKeys($document);
        $user ??= \Auth::user();
        \Cache::forget($key);
        \Cache::restoreLock($lock_key, $user)->release();
    }

    protected static function makeKeys(int|Document|RawDocument $document): array
    {
        $id = is_int($document) ? $document : $document->id;
        $key = 'ctv_editing_user_'.$id;
        $lock_key = 'ctv_editing_lock_'.$id;

        return [$key, $lock_key];
    }
}
