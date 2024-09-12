<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 11/24/15
 * Time: 17:03
 */

namespace App\Libs;

use Illuminate\Support\Str;
use JpnForPhp\Analyzer\TinySegmenter;
use StupidDev\ViEncoder\Encoder\Code;
use StupidDev\ViEncoder\Encoder\Converter;
use StupidDev\ViEncoder\Encoder\Detector;
use StupidDev\ViEncoder\Encoder\EncodeException;
use voku\helper\UTF8;

/** @todo: Tách thành 2 StringUtils và SlugHelper */
class StringUtils
{
    /**
     * @return string
     */
    public static function genSlug($string, $tags = [])
    {
        if (! is_array($tags)) {
            $tags = [];
        }
        $slug = '';
        if (self::isJapanese($string)) {
            $slug = self::genJpSlug($string, $tags);
        }

        return $slug ?: self::genLatinSlug($string, $tags) ?: Str::slug($string) ?: 'pdf';
    }

    public static function isLatin(string $string)
    {
        $string = self::trim($string);
        if (mb_strlen($string) < 2) {
            return ! preg_match("/[\p{Hiragana}\p{Katakana}\p{Han}\p{Hangul}]/ui", $string);
        }

        return ! preg_match("/[\p{Hiragana}\p{Katakana}\p{Han}\p{Hangul}]\s?[\p{Hiragana}\p{Katakana}\p{Han}\p{Hangul}]/ui", $string);
    }

    public static function isJapanese($string)
    {
        //http://www.rikai.com/library/kanjitables/kanji_codes.unicode.shtml
        return preg_match("/[\p{Hiragana}\p{Katakana}\p{Han}]\s?[\p{Hiragana}\p{Katakana}\p{Han}]/ui", $string);
    }

    public static function genJpSlug($string, $tags = [])
    {
        $string = preg_replace("/\p{P}/ui", '', $string);
        $words = (new TinySegmenter())->segment($string);
        $words = array_filter($words, function ($item) {
            return trim($item);
        });
        $words = self::reduceTexts($words, $tags, 0, 30);
        $_string = implode('', $words);
        $slug = preg_replace("/\p{Z}/ui", '', $_string) ?: Str::limit($string, 40, '');

        return UTF8::cleanup($slug);
    }

    public static function genLatinSlug($string, $tags = [])
    {
        $string = (new SeoSlugGenerator($string, $tags))->run();
        $string = mb_strtolower(trim($string));
        $slug = str_replace(' ', '-', $string);

        return UTF8::cleanup($slug);
    }

    public static function normalize(?string $string)
    {
        $string = trim($string);
        $string = self::unicodeConvert($string);
        $string = mb_strtolower($string);
        $string = preg_replace("/\p{Z}+/ui", ' ', $string);
        $string = preg_replace("/[^\p{M}\w\s]+/ui", ' ', $string);
        $string = preg_replace("/\s{2,}/", ' ', $string);

        return trim($string);
    }

    public static function wordsCount(string $string)
    {
        return count(self::extractWords($string));
    }

    public static function extractWords($string)
    {
        $string = preg_replace("/[\W\p{Z}\p{N}]/u", ' ', $string);
        $string = preg_replace("/\s{2,}/", ' ', $string);

        $latin = $cjk = $hangul = $thai = $arabic = $cyrillic = $devanagari = [];
        if (preg_match_all("/[\p{Latin}]{2,}/ui", $string, $matches)) {
            $latin = $matches[0];
        }
        if (preg_match_all("/[\p{Hiragana}\p{Katakana}\p{Han}]/ui", $string, $matches)) {
            $cjk = $matches[0];
        }
        if (preg_match_all("/[\p{Hangul}]/ui", $string, $matches)) {
            $hangul = $matches[0];
        }
        if (preg_match_all("/[\p{Thai}]{1,2}/ui", $string, $matches)) {
            $thai = $matches[0];
        }
        if (preg_match_all("/[\p{Arabic}]{2,}/ui", $string, $matches)) {
            $arabic = $matches[0];
        }
        if (preg_match_all("/[\p{Cyrillic}]{2,}/ui", $string, $matches)) {
            $cyrillic = $matches[0];
        }
        if (preg_match_all("/[\p{Devanagari}]+/ui", $string, $matches)) {
            $devanagari = $matches[0];
        }

        return [...$latin, ...$cjk, ...$hangul, ...$thai, ...$arabic, ...$cyrillic, ...$devanagari];
    }

    public static function extractUniqueWords(string $string)
    {
        $words = self::extractWords($string);

        $unique_words = [];
        foreach ($words as $word) {
            if (strlen($word) < 3) {
                continue;
            }
            $word = preg_replace("/[^\p{L}]/u", '', $word);
            if (! $word) {
                continue;
            }
            $word = mb_strtolower($word);
            if (in_array($word, $unique_words)) {
                continue;
            }
            $unique_words[] = $word;
        }

        return $unique_words;
    }

    public static function makeDocumentNameFromFileName($file_name)
    {
        $name = preg_replace("/\.\w+$/", '', $file_name);
        if (str_contains($name, ' ')) {
            return str_replace('_', ' ', $name);
        } else {
            return str_replace(['_', '-', '+'], ' ', $name);
        }
    }

    protected static function reduceTexts(array $words, $keywords = [], $max_words = 0, $max_chars = 0)
    {
        $words_weight = [];
        $words_length = [];
        foreach ($words as $k => $word) {
            $words_length[$k] = mb_strlen(preg_replace('/[a-zàâçéèêëîïôûùüÿñæœ]/ui', '', $word)) ?: 1;
            $words_weight[$k] = $words_length[$k] * (int) (in_array(mb_strtolower($word), $keywords) ? 1.5 : 1);
            if (preg_match('~^\p{Lu}~u', $word)) {
                $words_weight[$k]++;
            }
        }

        arsort($words_weight);
        if ($max_words) {
            $words_weight = array_slice($words_weight, $max_words - count($words_weight), null, true);
            foreach ($words_weight as $k => $v) {
                unset($words[$k]);
            }
        } elseif ($max_chars) {
            $indexes = [];
            while (array_sum($words_length) > $max_chars && count($words_length) > 1) {
                $last_index = array_key_last($words_weight);
                array_pop($words_weight);
                unset($words_length[$last_index]);
                $indexes[] = $last_index;
            }
            foreach ($indexes as $k) {
                unset($words[$k]);
            }
        } else {
            throw new \Exception('Phai khai bao max_words hoặc max_char');
        }

        return $words;
    }

    public static function unicodeConvert(string $string)
    {
        $unicode = [
            'Ⅰ' => 'I',
            'Ⅱ' => 'II',
            'Ⅲ' => 'III',
            'Ⅳ' => 'IV',
            'Ⅴ' => 'V',
            'Ⅵ' => 'VI',
            'Ⅶ' => 'VII',
            'Ⅷ' => 'VIII',
            'Ⅸ' => 'IX',
            'Ⅹ' => 'X',
            '０' => '0',
            '１' => '1',
            '２' => '2',
            '３' => '3',
            '４' => '4',
            '５' => '5',
            '６' => '6',
            '７' => '7',
            '８' => '8',
            '９' => '9',
        ];

        foreach ($unicode as $key => $value) {
            $string = preg_replace("/$key/ui", $value, $string);
        }

        return $string;
    }

    public static function trim($str)
    {
        return preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $str);
    }

    protected static ?string $last_encoding = null;

    public static function vnEncodeFix(?string $string, bool $use_last_encoding = false): ?string
    {

        if ($use_last_encoding) {
            $sourceEncode = Detector::usingCode($string);
            if (! $sourceEncode || $sourceEncode == Code::CHARSET_UNICODE) {
                $sourceEncode = self::$last_encoding;
            } else {
                self::$last_encoding = $sourceEncode;
            }
        } else {
            $sourceEncode = null;
        }

        try {
            $string = Converter::changeEncode($string, Code::CHARSET_UNICODE, $sourceEncode);
        } catch (EncodeException $ex) {
            //            dump("Warning encode : " . $ex->getMessage());
        }
        [$string] = self::fixVnSimpleError($string);

        return $string;
    }

    public static function fixVnSimpleError($string, $matched_before = '', $check_string = null): array
    {
        $check_string = $check_string ?: $string;
        $matching = '';
        $matched = false;
        if (str_contains($matched_before, 'Ƣ') || $matched = preg_match("/\wƢ[ơờớởu]/ui", $check_string)) {
            $string = preg_replace('/Ƣ/u', 'Ư', $string);
            $string = preg_replace('/ƣ/u', 'ư', $string);
            $matching .= $matched ? 'Ƣ' : '';
        }
        $matched = false;
        if (str_contains($matched_before, '−') || $matched = preg_match("/\w\−[ơờớởu]/ui", $check_string)) {
            $string = preg_replace("/\−/ui", 'ư', $string);
            $matching .= $matched ? '−' : '';
        }

        return [$string, $matching];
    }

    public static function resetLastEncode(): void
    {
        self::$last_encoding = null;
    }

    public static function shouldBreakAll($string)
    {
        return preg_match("/\S{15,}/ui", $string);
    }

    public static function convertBracketsToDots($name): string
    {
        return str_replace(['[', ']'], ['.', ''], $name);
    }

    public static function convertDotsToBrackets($name): string
    {
        $names = explode('.', $name);
        $input_name = array_shift($names);
        while ($name = array_shift($names)) {
            $input_name .= '['.$name.']';
        }

        return $input_name;
    }

    public static function canBeTitle(string $string): bool
    {
        // check length by words
        $words_count = StringUtils::wordsCount($string);
        if ($words_count < 2 || $words_count > 30) {
            return false;
        }
        // have latin character and no space
        if (preg_match("/\p{Latin}/ui", $string) && ! preg_match("/\p{Latin}\s/ui", $string)) {
            return false;
        }

        return true;
    }
}
