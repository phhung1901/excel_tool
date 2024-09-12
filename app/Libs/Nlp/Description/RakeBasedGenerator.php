<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-11-19
 * Time: 13:32
 */

namespace App\Libs\Nlp\Description;

use DonatelloZa\RakePlus\RakePlus;

class RakeBasedGenerator
{
    protected $language = 'en_US';

    protected $pages = [];

    protected $keywords = []; // input keywords

    protected $rake_keywords = []; // rake generated keywords

    protected $description;

    protected function __construct($pages = [], $keywords = [], $language = null)
    {
        $this->pages = $pages;
        $this->keywords = $keywords;
    }

    public static function fromRawText(string $text): self
    {
        $pages = preg_split("/\014/ui", $text);
        foreach ($pages as $k => $v) {
            $pages[$k] = preg_replace("/[\n\t\r\s]+/ui", ' ', $v);
        }

        return new self($pages);
    }

    public static function fromDSFullText(string $text): self
    {
        $pages = preg_split("/\<span class\=\'text_page_counter\'\>\(\d+\)\<\/span\>/", $text);
        foreach ($pages as $k => $v) {
            $pages[$k] = strip_tags(html_entity_decode(preg_replace("/[\n\t\r\s]+/ui", ' ', $v)));
        }

        return new self($pages);
    }

    public function getKeywords($limit = 10, $generated_only = false)
    {
        if (count($this->rake_keywords) == 0) {
            $this->generate();
        }
        if ($generated_only) {
            return array_slice($this->rake_keywords, 0, $limit);
        } else {
            return array_slice(array_merge($this->keywords, $this->rake_keywords), 0, $limit);
        }
    }

    public function getDescription($limit = 190)
    {
        if (! $this->description) {
            $this->generate();
        }

        return $this->description;
    }

    protected function generate()
    {

        $perfect_pages = [];

        if (count($this->pages) < 100) {
            foreach ($this->pages as $k => $page) {
                if ($content = $this->getPerfectSentences($page)) {
                    $perfect_pages[$k] = $content;
                }
            }
        } else {
            $pages_count = count($this->pages);
            $padding_pages = 40 + 1;
            for ($i = 0; $i < $padding_pages; $i++) {
                if ($content = $this->getPerfectSentences($this->pages[$i])) {
                    $perfect_pages[$i] = $content;
                }
            }
            for ($i = $pages_count - $padding_pages; $i < $pages_count; $i++) {
                if ($content = $this->getPerfectSentences($this->pages[$i])) {
                    $perfect_pages[$i] = $content;
                }
            }
        }
        $document_content = implode('. ', $perfect_pages);
        $rake_plus = RakePlus::create($document_content, $this->language);

        $this->rake_keywords = array_slice($rake_plus->sortByScore('desc')->scores(), 0, 40);

    }

    protected function getPerfectSentences($content)
    {
        $sentences = explode('. ', $content);
        $perfect_sentences = [];
        foreach ($sentences as $sentence) {
            if ($this->isPerfectSentence(trim($sentence))) {
                $perfect_sentences[] = trim($sentence);
                dump(trim($sentence));
            }
        }

        return implode('. ', $perfect_sentences).(count($perfect_sentences) ? '.' : '');
    }

    protected $perfect_sentence_rules = [
        'min_characters' => 10, // tối thiểu 20 ký tự
        'min_words' => 3, // tối thiểu 5 chữ
        'min_letter_percent' => 0.7, // tối thiểu 70% là chữ cái
        'max_single_character_percent_by_word' => 0.2, // không quá 20% chữ là chữ có độ dài là 1
    ];

    protected function isPerfectSentence($string)
    {
        $characters_count = mb_strlen($string);

        if ($characters_count < $this->perfect_sentence_rules['min_characters']) {
            return false;
        }

        $words = preg_split("/[\s\n\t;\(\)]+/", $string);
        $word_count = count($words);

        if ($word_count < $this->perfect_sentence_rules['min_words']) {
            return false;
        }

        if (preg_match_all("/\p{L}/ui", $string, $maches)) {
            $letters_count = count($maches[0]);
            if ($letters_count / $characters_count < $this->perfect_sentence_rules['min_letter_percent']) {
                return false;
            }
        } else {
            return false;
        }

        $single_words_count = 0;
        foreach ($words as $word) {
            if (mb_strlen($word) == 1) {
                $single_words_count++;
            }
        }

        if ($single_words_count / $word_count > $this->perfect_sentence_rules['max_single_character_percent_by_word']) {
            return false;
        }

        return true;
    }
}
