<?php

namespace App\Libs\TLD;

class TLD
{
    protected static ?self $instance = null;

    protected array $suffixes = [
        //        'suffix' => [
        //            'suffix' => 'com'
        //            'depth' => 1
        //        ]
    ];

    protected function __construct()
    {
        $raw = file_get_contents(__DIR__.'/public_suffixes.txt');
        $lines = explode("\n", $raw);
        $lines = array_filter($lines, fn ($item) => $item && ! str_starts_with($item, '//'));
        if (empty($lines)) {
            throw new \Exception('No suffixes list');
        }
        $lines = array_map(fn ($item) => preg_replace("/^(!|\*\.)/", '', $item), $lines);
        $lines = array_unique($lines);
        foreach ($lines as $suffix) {
            $depth = count(explode('.', $suffix));
            $this->suffixes[$suffix] = [
                'suffix' => $suffix,
                'depth' => $depth,
            ];
        }
        usort($this->suffixes, fn ($a, $b) => ($a['depth'] > $b['depth'] || ($a['depth'] == $b['depth'] && $a < $b)) ? -1 : 1);
    }

    public static function getInstance(): self
    {
        if (! self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function matchDomain(string $domain)
    {
        foreach (self::getInstance()->suffixes as $suffix) {
            $regex = "/\.{$suffix['suffix']}$/ui";
            if (preg_match($regex, $domain, $matches)) {
                return $matches[0];
            }
        }

        return false;
    }
}
