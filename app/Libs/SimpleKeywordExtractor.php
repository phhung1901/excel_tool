<?php

namespace App\Libs;

use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class SimpleKeywordExtractor
{
    use MultiLanguageProcessTrait;

    protected $isLatin = true; // true với tiếng Nhật, Hàn, Trung

    protected $content;

    protected $default_stopword;

    /**
     * SimpleKeywordExtractor constructor.
     *
     * @param  bool  $isLatin
     * @param  null  $stopwords
     */
    public function __construct($content, $isLatin = true, $stopwords = null)
    {
        $this->content = $content;
        $this->isLatin = $isLatin;
        $this->default_stopword = $stopwords;
    }

    public static function fromText($text, $stopwords = null)
    {
        $text = strip_tags($text);

        return new self($text, true, $stopwords);
    }

    public static function fromHtml($html, $stopwords = null)
    {
        // parse html, các cụm từ trong heading, bold, italic được double
        $crawler = new Crawler();
        $crawler->addHtmlContent($html);
        $special_texts = [];
        $crawler->filterXPath('//*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::b or self::i or self::strong or self::em]')
            ->each(function (Crawler $c) use (&$special_texts) {
                $special_texts[] = $c->text();
            });
        $text = strip_tags($html.' '.implode(' ', $special_texts));

        return new self($text, true, $stopwords);
    }

    /**
     * @param  int  $min_score
     * @return KeywordsList|void
     *
     * @throws \voku\helper\StopWordsLanguageNotExists
     */
    public function getKeywords($limit, $min_score = 1)
    {
        return $this->isLatin ? $this->getKeywordsLatin($limit, $min_score) : $this->getKeywordsNonLatin($limit, $min_score);
    }

    /**
     * @param  int  $min_score
     * @return KeywordsList
     *
     * @throws \voku\helper\StopWordsLanguageNotExists
     */
    protected function getKeywordsLatin($limit, $min_score = 1)
    {
        $language = $this->getLanguageDetector()->detect(Str::words($this->content, 120))->__toString();
        $stopwords = $this->getStopwordsGenerator()->getStopWordsFromLanguage($language);
        $content = preg_replace("/\b(".implode('|', $stopwords).")\b/", ' ', $this->content);
        $words = $this->tokenize($content);
        $words_count = [];
        foreach ($words as $word) {
            $word = Str::lower($word);
            // xoa chu qua ngan hoac khong chua chu cai/so
            if (mb_strlen($word) < 3 || ! preg_match("/[\p{L}\d]/ui", $word)) {
                continue;
            }
            if (isset($words_count[$word])) {
                $words_count[$word]++;
            } else {
                $words_count[$word] = 1;
            }
        }

        $result = [];
        $min_score = $min_score * 100;
        foreach ($words_count as $k => $v) {
            $score = $v * 100 + mb_strlen($k);
            if ($score >= $min_score) {
                $result[$k] = $score;
            }
        }
        arsort($result);
        $result = array_slice($result, 0, $limit);

        return new KeywordsList($result, $language);
    }

    protected function getKeywordsNonLatin($limit, $min_score = 0)
    {

    }

    public function tokenize($str)
    {
        $arr = [];
        // for the character classes
        // see http://php.net/manual/en/regexp.reference.unicode.php
        $pat = '/
                    ([\pZ\pC]*)			# match any separator or other
                                        # in sequence
                    (
                        [^\pP\pZ\pC]+ |	# match a sequence of characters
                                        # that are not punctuation,
                                        # separator or other
                        [\p{L}]				# match punctuations one by one
                    )
                    ([\pZ\pC]*)			# match a sequence of separators
                                        # that follows
                /xu';
        if (preg_match_all($pat, $str, $arr)) {
            return $arr[2];
        } else {
            return [];
        }
    }
}
