<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-10-29
 * Time: 01:14
 */

namespace App\Libs;

class TagNameSanitizer
{
    public static function normalize($string)
    {
        $string = mb_strtolower($string);
        $string = preg_replace("/[^\p{M}\w\s]+/ui", ' ', $string);
        $string = preg_replace("/\s{2,}/", ' ', $string);

        return $string;
    }
}
