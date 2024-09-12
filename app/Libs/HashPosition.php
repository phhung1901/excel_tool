<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2020-04-18
 * Time: 23:47
 */

namespace App\Libs;

use Hashids\Hashids;

class HashPosition
{
    protected static $salt = '1library2020';

    protected static $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    protected static $hashids;

    protected static $max_number = 3600;

    public static function encode(...$numbers)
    {
        if (! self::validateNumbers(...$numbers)) {
            throw new \Exception('Number > '.self::$max_number.' : '.implode('.', $numbers));
        }

        return self::getHashids()->encode($numbers);
    }

    public static function decode($code): array
    {
        $numbers = self::getHashids()->decode($code);

        return $numbers;
    }

    public static function setSalt($salt)
    {
        self::$salt = $salt;
        self::$hashids = new Hashids(self::$salt, 2, self::$chars);
    }

    protected static function getHashids(): Hashids
    {
        if (! self::$hashids) {
            self::$hashids = new Hashids(self::$salt, 2, self::$chars);
        }

        return self::$hashids;
    }

    public static function validateNumbers(...$numbers)
    {
        foreach ($numbers as $number) {
            if ($number > self::$max_number) {
                return false;
            }
        }

        return true;
    }
}
