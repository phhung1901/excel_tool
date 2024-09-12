<?php

namespace App\Libs\Nlp\Description;

use App\DocumentProcess\Chunker\SentenceTokenizer\SpacySentenceTokenizer;
use App\Libs\StringUtils;
use App\NLP\TextProcessor;
use ThikDev\PdfParser\Objects\Document;
use ThikDev\PdfParser\Objects\Page;

class NlpRankGenerator
{
    protected $pages = [];

    protected $keywords = []; // input keywords

    protected $rake_keywords = []; // rake generated keywords

    protected $important_sentences = [];

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
            $pages[$k] = preg_replace('/[[:cntrl:]\(\)]/', '', $pages[$k]);
        }

        return new self($pages);
    }

    public static function fromRTDocument(Document $document): self
    {
        $pages = [];
        /** @var Page $page */
        foreach ($document->getPages() as $page) {
            $pages[] = $page->getText();
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

    public function getImportantSentences()
    {
        if (count($this->important_sentences) == 0) {
            $this->generate();
        }

        return $this->important_sentences;
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

        $api = new TextProcessor();
        $api->process($document_content);
        $this->rake_keywords = $api->importantWords(50);
        $this->important_sentences = $api->importantSentences(50, 1);
        $this->description = '';

    }

    protected function getPerfectSentences($content)
    {
        $_sentences = preg_split(
            '/(\n+)|(\.\s|。\s|\?\s|\!\s|…\s)(?![^\(]*\))/',
            $content,
            -1,
            PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE
        );
        $sentences = [];
        foreach ($_sentences as $_sentence) {
            $dot_count = preg_match_all("/[\.・。•◦∙]\s/ui", $_sentence, $matches);
            if ($dot_count > 1) {
                $__sentences = (new SpacySentenceTokenizer())->tokenize($_sentence);
                foreach ($__sentences as $__sentence) {
                    $sentences[] = $__sentence;
                }
            } else {
                $sentences[] = $_sentence;
            }
        }

        $perfect_sentences = [];
        foreach ($sentences as $sentence) {
            if ($this->isPerfectSentence(trim($sentence))) {
                $perfect_sentences[] = trim(preg_replace('/^[^\p{L}\p{N}]+/ui', '', $sentence));
            }
        }

        return implode('. ', $perfect_sentences).(count($perfect_sentences) ? '.' : '');
    }

    protected function isPerfectSentence($string)
    {
        $perfect_sentence_rules = [
            'min_characters' => 10, // tối thiểu 20 ký tự
            'min_words' => 5, // tối thiểu 5 chữ
            'min_letter_percent' => 0.7, // tối thiểu 70% là chữ cái
            'max_single_character_percent_by_word' => 0.2, // không quá 20% chữ là chữ có độ dài là 1
        ];

        $characters_count = mb_strlen($string);

        if ($characters_count < $perfect_sentence_rules['min_characters']) {
            return false;
        }

        $words = StringUtils::extractWords($string);
        $words_count = count($words);

        if ($words_count < $perfect_sentence_rules['min_words']) {
            return false;
        }

        if (preg_match_all("/\p{L}/ui", $string, $maches)) {
            $letters_count = count($maches[0]);
            if ($letters_count / $characters_count < $perfect_sentence_rules['min_letter_percent']) {
                return false;
            }
        } else {
            return false;
        }

        if (StringUtils::isLatin($string)) {
            $single_words_count = 0;
            foreach ($words as $word) {
                if (mb_strlen($word) == 1) {
                    $single_words_count++;
                }
            }

            if ($single_words_count / $words_count > $perfect_sentence_rules['max_single_character_percent_by_word']) {
                return false;
            }
        }

        return true;
    }
}
