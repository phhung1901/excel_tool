<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2020-03-26
 * Time: 10:43
 */

namespace App\Libs;

use LanguageDetection\Language;
use voku\helper\StopWords;

class SeoSlugGenerator
{
    public static $language;

    public static $stopwords;

    protected $title;

    protected $expected_length = 8; // độ dài mong đợi

    protected $tags = [];

    /**
     * SeoSlugGenerator constructor.
     */
    public function __construct($title, array $tags = [])
    {
        $this->title = $title;
        $this->tags = $tags;
    }

    public function run()
    {
        try {
            $title = $this->removeStopWords($this->title);
        } catch (\Exception $ex) {
            $title = $this->title;
        }
        $title = $this->removeDuplicated($title);
        $title = $this->reduceTexts($title, $this->flatTags());

        return $title;
    }

    /**
     * Xoá stop words theo language
     *
     *
     * @return string|string[]|null
     *
     * @throws \voku\helper\StopWordsLanguageNotExists
     */
    protected function removeStopWords($title)
    {
        $words = explode(' ', $title);
        if (count($words) <= $this->expected_length) {
            return $title;
        }
        // remove stopwords
        $languages = $this->getLanguageDetector()->detect($title)->close();
        if (! count($languages)) {
            return $title;
        }
        $stopwords = $this->getStopwordsGenerator()->getStopWordsFromLanguage(array_key_first($languages));
        foreach ($stopwords as &$word) {
            $word = '/\b'.preg_quote($word, '/').'\b\s*/iu';
        }

        return preg_replace($stopwords, '', $title);
    }

    /**
     * Xoá các chữ trùng nhau
     *
     *
     * @return string
     */
    protected function removeDuplicated($title)
    {
        // remove special character
        $title = preg_replace("/[^\p{L}\p{N}]+/ui", ' ', $title);
        $title = preg_replace("/\p{N}{5,}/ui", ' ', $title);

        $words = explode(' ', $title);
        if (count($words) <= $this->expected_length) {
            return $title;
        }
        $result = [];
        foreach ($words as $word) {
            if (! in_array($word, $result)) {
                $result[] = $word;
            }
        }

        return implode(' ', $words);
    }

    /**
     * Giảm số chữ xuống expected length bằng cách xoá dần các chữ ngắn và ko thuộc keywords/tags
     *
     * @param  array  $keywords
     * @return string
     */
    protected function reduceTexts($title, $keywords = [])
    {
        $words = explode(' ', $title);
        if (count($words) <= $this->expected_length) {
            return $title;
        }
        $words_weight = [];
        foreach ($words as $k => $word) {
            $words_weight[$k] = strlen($word) * (int) (in_array(mb_strtolower($word), $keywords) ? 1.5 : 1);
            if (preg_match('~^\p{Lu}~u', $word)) {
                $words_weight[$k]++;
            }
        }

        arsort($words_weight);
        $words_weight = array_slice($words_weight, $this->expected_length - count($words_weight), null, true);
        foreach ($words_weight as $k => $v) {
            unset($words[$k]);
        }

        return implode(' ', $words);
    }

    protected function flatTags()
    {
        $flatted = implode(' ', $this->tags);

        return explode(' ', $flatted);
    }

    /**
     * @return Language
     */
    protected function getLanguageDetector()
    {
        if (! self::$language) {
            self::$language = new Language();
        }

        return self::$language;
    }

    /**
     * @return StopWords
     */
    protected function getStopwordsGenerator()
    {
        if (! self::$stopwords) {
            self::$stopwords = new StopWords();
        }

        return self::$stopwords;
    }
}
