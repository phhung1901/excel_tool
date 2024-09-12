<?php

namespace App\Libs;

use Smalot\PdfParser\Config;
use Smalot\PdfParser\Parser;
use Spatie\LaravelData\Data;

class PdfInfo extends Data
{
    public function __construct(
        public ?string $author = null,
        public ?string $creator = null,
        public ?string $moddate = null,
        public ?string $creationdate = null,
        public ?string $producer = null,
        public ?string $title = null,
        public ?string $keywords = null,
        public ?string $subject = null,
        public ?int $pages = null,
        public ?string $pdfversion = null,
    ) {

    }

    public static function fromFile(string $string, $cfg = [], ?Config $config = null, $with_additional = false): ?PdfInfo
    {
        if (! file_exists($string)) {
            return null;
        }

        return self::fromContent(file_get_contents($string), $cfg, $config, $with_additional);
    }

    public static function fromContent(string $string, $cfg = [], ?Config $config = null, $with_additional = false): ?self
    {
        if ($config === null) {
            $config = new Config();
            $config->setDecodeMemoryLimit(2 * 1024);
        }
        $parser = new Parser(cfg: $cfg, config: $config);
        try {
            $detail = $parser->parseContent($string)->getDetails();
        } catch (\Exception $ex) {
            \Log::error('Parse pdf error: '.$ex->getMessage());

            return null;
        }
        $detail = array_change_key_case($detail, CASE_LOWER);
        $info = self::from($detail);
        if (! $with_additional) {
            return $info;
        }
        $keys = ['author', 'creator', 'moddate', 'creationdate', 'producer', 'title', 'keywords', 'subject', 'pages', 'pdfversion'];
        $additional_data = \Arr::except($detail, $keys);
        $info->additional($additional_data);

        return $info;
    }
}
