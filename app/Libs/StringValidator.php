<?php

namespace App\Libs;

class StringValidator
{
    protected string $content;

    protected array $words = [];

    /** điểm tối thiểu đánh dấu không hợp lệ, nếu trên con số này thì dừng check */
    protected int $min_invalid_score = 100;

    protected int $score = 0;

    protected array $config = [
        'min_words' => 2,
        'min_chars' => 8,
        'ignore_text' => [
            'Microsoft PowerPoint', 'Microsoft Word', 'PowerPoint',
            '(https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|www\.[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9]+\.[^\s]{2,}|www\.[a-zA-Z0-9]+\.[^\s]{2,})',
            '[A-Za-z]{2,}[0-9]{2,}[A-Za-z]{2,}',
        ],
    ];

    protected array $errors = [];

    protected array $rules = [
        [
            'name' => 'Chứa từ quá dài',
            'function' => 'checkTooLongWords',
        ],
        [
            'name' => 'Là tên file',
            'function' => 'checkIsFileName',
        ],
        [
            'name' => 'Quá nhiều ký tự đặc biệt',
            'function' => 'checkSpecialChars',
        ],
        [
            'name' => 'Có thể được nối từ 2 đoạn',
            'function' => 'checkParagraph',
        ],
        [
            'name' => 'Có thể bị merge sai',
            'function' => 'checkWrongMerging',
        ],
        [
            'name' => 'Nếu là tiếng nhật thì phải có kanji',
            'function' => 'checkKanji',
        ],
        [
            'name' => 'Độ dài không tốt (quá ngắn)',
            'function' => 'checkLength',
        ],
        [
            'name' => 'Chứa những từ khóa cấm',
            'function' => 'hasIgnoreText',
        ],
    ];

    /**
     * StringValidator constructor.
     */
    public function __construct(string $string, array $config = [])
    {
        $this->content = trim($string);
        $this->config = array_merge($this->config, $config);
        $this->words = StringUtils::extractUniqueWords($string);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param  null  $min_invalid_score
     */
    public function isValid($min_invalid_score = null): bool
    {
        if ($this->score == 0) {
            $this->check(true);
        }

        return $min_invalid_score ? ($this->score < $min_invalid_score) : ($this->score < $this->min_invalid_score);
    }

    public function getScore(): int
    {
        if ($this->score == 0) {
            $this->check(true);
        }

        return $this->score;
    }

    /**
     * @return $this
     */
    public function check(bool $all = false)
    {
        // reset stat
        $this->score = 0;
        $this->errors = [];

        // check rules
        foreach ($this->rules as $rule) {
            $score = $this->{$rule['function']}();
            if ($score) {
                $this->errors[] = $rule['name'];
            }
            $this->score += $score;
            if (! $all && $this->score >= $this->min_invalid_score) {
                break;
            }
        }

        return $this;
    }

    /*-----------Checker Functions-----------*/
    /*---------------------------------------*/

    /**
     * Chứa 1 từ dài hơn 27 ký tự hoặc 2 từ dài hơn 19 ký tự
     *
     * @return int
     */
    protected function checkTooLongWords()
    {
        $i = 0;
        foreach ($this->words as $word) {
            $word_length = mb_strlen($word);
            if ($word_length > 27) {
                return $this->min_invalid_score;
            } elseif ($word_length > 19 && ++$i > 1) {
                return $this->min_invalid_score;
            }
        }

        return 0;
    }

    /**
     * ___.pdf, ___.doc, ___.xyz
     *
     * @return int
     */
    protected function checkIsFileName()
    {
        $pattern = "/\.[A-Za-z]{2,7}$/ui";
        if (preg_match($pattern, $this->content)) {
            return $this->min_invalid_score;
        }

        return 0;
    }

    /**
     * Chứa ký tự đặc biệt
     *
     * @return float|int
     */
    protected function checkSpecialChars()
    {
        $score = 0;
        //Bắt đầu bằng ký tự đặc biệt
        if (preg_match("/^\W/ui", $this->content)) {
            $score += (int) ($this->min_invalid_score / 2);
        }
        //Chứa ký tự đặc biệt khác
        $content = preg_replace("/[「」『』（）｛｝［］【】“'｜\(\)\[\]\s\t\n]/u", '_', $this->content);
        if ($matches = preg_match_all("/\W/ui", $content, $m)) {
            if ($matches > 6) {
                $score += $this->min_invalid_score;
            } elseif ($matches > 2) {
                $score += $this->min_invalid_score / 2;
            }
        }
        //Các ký tự đặc biệt liền tịt vào nhau
        //% ký tự đặc biệt

        return $score;
    }

    /**
     * được nối từ 2 đoạn
     *
     * @return int
     */
    protected function checkParagraph()
    {
        $dot_count = preg_match_all("/[\.・。•◦∙]\s/ui", $this->content, $matches);
        $score = 0;
        if ($dot_count > 1) {
            $score += $this->min_invalid_score;
        } elseif ($dot_count == 1) {
            $score += (int) ($this->min_invalid_score / 2);
        }

        return $score;
    }

    /**
     * 1 từ có 2 chữ viết hoa
     *
     * @return int
     */
    protected function checkWrongMerging()
    {
        // Đến các từ có 2 chữ in hoa cách nhau
        $matched_count = preg_match_all("/\p{Lu}\p{Ll}+(\p{Lu}\p{Ll}+)+/ui", $this->content);
        $score = 0;
        if ($matched_count > 1) {
            $score += $this->min_invalid_score;
        } elseif ($matched_count == 1) {
            $score += (int) ($this->min_invalid_score / 2);
        }

        return $score;
    }

    /**
     * độ dài không đủ
     *
     * @return int
     */
    protected function checkLength()
    {
        $words_count = count($this->words);
        $str_len = strlen($this->content);

        if ($words_count < $this->config['min_words']) {
            return $this->min_invalid_score;
        }

        if ($str_len < $this->config['min_chars']) {
            return $this->min_invalid_score;
        }

        return 0;
    }

    /**
     * nếu là japanese thì phải có kanji hoặc Katakana
     *
     * @return int
     */
    protected function checkKanji()
    {
        $normalize = StringUtils::normalize($this->content);
        if (StringUtils::isJapanese($normalize)) {
            if (preg_match("/[\p{Han}\p{Katakana}]/ui", $normalize)) {
                return 0;
            }

            return $this->min_invalid_score;
        } else {
            return 0;
        }
    }

    protected function hasIgnoreText()
    {
        $ignore_text = $this->config['ignore_text'];
        if (preg_match('/'.implode('|', $ignore_text).'/ui', $this->content)) {
            return $this->min_invalid_score;
        }

        return 0;
    }
}
