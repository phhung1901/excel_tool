<?php

namespace App\Libs;

use App\NLP\StopWordsRemover;

class EsHelper
{
    public static function normalize(string $string, $language = null): string
    {
        $string = preg_replace('/[^\p{L}\p{N}]+/ui', ' ', $string);
        $string = app(StopWordsRemover::class)->run($string, $language);
        //        $string = preg_replace('/(\p{L})(\p{N})/ui', '$1 $2', $string);
        //        $string = preg_replace('/(\p{N})(\p{L})/ui', '$1 $2', $string);
        $string = preg_replace("/\s\s+/ui", ' ', $string);

        return trim($string);
    }
}
