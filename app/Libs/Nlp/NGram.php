<?php

namespace App\Libs\Nlp;

class NGram
{
    /**
     * The length of the n-gram.
     *
     * @var int
     */
    protected $n;

    /**
     * @var string
     */
    protected $string;

    public function __construct(int $n, string $string)
    {
        $this->setN($n);
        $this->setString($string);
    }

    public static function for(string $text, int $n = 3)
    {
        return (new static($n, $text))->get();
    }

    public static function bigram(string $text): array
    {
        return self::for($text, 2);
    }

    public static function trigram(string $text): array
    {
        return self::for($text, 3);
    }

    /**
     * Generate the N-gram for the provided string.
     */
    public function get(): array
    {
        $nGrams = [];

        $text = $this->getString();
        $max = \mb_strlen($text);
        $n = $this->getN();
        for ($i = 0; $i + $n <= $max; $i++) {
            $partial = '';
            for ($j = 0; $j < $n; $j++) {
                $partial .= $text[$j + $i];
            }
            $nGrams[] = $partial;
        }

        return $nGrams;
    }

    public function getN(): int
    {
        return $this->n;
    }

    public function setN(int $n): NGram
    {
        if ($n < 1) {
            throw new \InvalidArgumentException('Provided number cannot be smaller than 1');
        }

        $this->n = $n;

        return $this;
    }

    public function setString(string $string): NGram
    {
        $this->string = $string;

        return $this;
    }

    public function getString(): string
    {
        return $this->string;
    }
}
