<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-10-02
 * Time: 10:24
 */

namespace App\Libs;

use HocVT\TikaSimple\TikaSimpleClient;
use Illuminate\Support\Arr;
use Symfony\Component\Mime\MimeTypes;

class TikaHelper
{
    protected $non_space_languages = [
        'ja',
        'th',
        'zh-CN',
    ];

    protected $host = 'localhost:9998';

    protected $client;

    protected $mime_helper;

    protected $timeout = 15;

    protected $retry = 2;

    protected $retry_sleep = 1000; // milliseconds

    /**
     * TikaHelper constructor.
     */
    public function __construct()
    {
        $this->host = config('doc_services.tika.host').
            ':'.
            config('doc_services.tika.port');
        $this->client = $this->createClient();
        $this->mime_helper = new MimeTypes();
        $this->timeout = config('doc_services.tika.timeout');
    }

    public function createClient(): TikaSimpleClient
    {
        return new TikaSimpleClient($this->host, [
            'timeout' => $this->timeout,
        ]);
    }

    public function timeout(int $timeout): self
    {
        $this->timeout = $timeout;
        $this->client = $this->createClient();

        return $this;
    }

    public function retry(int $times): self
    {
        $this->retry = $times;

        return $this;
    }

    public function retry_sleep(int $mini_seconds): self
    {
        $this->retry_sleep = $mini_seconds;

        return $this;
    }

    public function getMime($path)
    {
        return retry($this->retry, fn () => $this->client->mimeFile($path), $this->retry_sleep);
    }

    public function getExtension($path)
    {
        $mime_info = $this->getMime($path);
        $ext = $this->mime_helper->getExtensions($mime_info)[0];

        return $ext;
    }

    public function getLanguage(string $string): string
    {
        return retry($this->retry, fn () => $this->client->language($string), $this->retry_sleep);
    }

    public function getDocumentInfo($path): array
    {
        $meta_data = retry($this->retry, fn () => $this->client->rmetaFile($path, 'text'), $this->retry_sleep);
        $content = $meta_data['X-TIKA:content'] ?? '';

        $data = [
            'title' => $meta_data['pdf:docinfo:title'] ?? $meta_data['dc:title'] ?? $meta_data['title'] ?? '',
            'pages' => $meta_data['meta:page-count'] ?? $meta_data['Page-Count'] ?? $meta_data['xmpTPg:NPages'] ?? 0,
            'words' => $meta_data['meta:word-count'] ?? 0,
            'size' => filesize($path),
            'characters' => mb_strlen($content),
            'mime_type' => $meta_data['Content-Type'],
            'extension' => $this->mime_helper->getExtensions($meta_data['Content-Type'])[0],
            'content' => str_replace("\n\n", "\n", $content),
        ];

        [$data['characters'], $data['words'], $data['description'], $data['language_code']]
            = retry($this->retry, fn () => $this->parse_content($meta_data), $this->retry_sleep);

        return $data;
    }

    public function getDocumentInfoFromContent($file): array
    {
        $meta_data = retry($this->retry, fn () => $this->client->rmeta($file, 'text'), $this->retry_sleep);
        $content = $meta_data['X-TIKA:content'] ?? '';

        $data = [
            'title' => $meta_data['pdf:docinfo:title'] ?? $meta_data['dc:title'] ?? $meta_data['title'] ?? '',
            'pages' => $meta_data['meta:page-count'] ?? $meta_data['Page-Count'] ?? $meta_data['xmpTPg:NPages'] ?? 0,
            'words' => $meta_data['meta:word-count'] ?? 0,
            'size' => strlen($file),
            'characters' => mb_strlen($content),
            'mime_type' => $meta_data['Content-Type'],
            'extension' => $this->mime_helper->getExtensions($meta_data['Content-Type'])[0],
            'content' => str_replace("\n\n", "\n", $content),
        ];

        [$data['characters'], $data['words'], $data['description'], $data['language_code']]
            = retry($this->retry, fn () => $this->parse_content($meta_data), $this->retry_sleep);

        return $data;
    }

    protected function parse_content($meta_data): array
    {

        $content = trim($meta_data['X-TIKA:content'] ?? '');

        $content = preg_replace("/[\n\t\r\s\b\.\-\_…·\:]+/ui", ' ', $content);

        $content = trim($content);
        $characters = mb_strlen($content);
        $sample_content = \Illuminate\Support\Str::limit($content, 10000, '');

        try {
            [$language, $other_languages] = $this->getLanguages($sample_content);
        } catch (\Exception $ex) {
            \Log::alert('Detect langauge error : '.$ex->getMessage());
            $language = $this->getLanguage($sample_content);
        }

        $description = \Illuminate\Support\Str::limit($content, 200, '[.]');

        if (in_array($language, $this->non_space_languages)) {
            $words = mb_strlen(str_replace(' ', '', $content));
        } else {
            $words = count(explode(' ', $content));
        }

        return [$characters, $words, $description, $language];
    }

    public function getLanguages($content): array
    {
        $min_letter = 20;
        $sample_size = 6;
        $content = strip_tags($content);
        $content = preg_replace("/\n+/", ' ', $content);
        $sentences = preg_split("/\s*[。.;]\s*/ui", $content);
        $sentences = array_map(fn ($s) => ['c' => $s, 'l' => $this->letterCharacter($s, $min_letter)], $sentences);
        $sentences = array_filter($sentences, fn ($s) => $s['l'] > $min_letter);
        if (count($sentences) > $sample_size) {
            $sentences = Arr::random($sentences, $sample_size);
        }
        if (count($sentences) < 1) {
            return [null, null];
        }
        $languages = [];
        foreach ($sentences as $sentence) {
            $language = $this->getLanguage($sentence['c']);
            //            dump($language . " --------- " . $sentence['c']);
            if (isset($languages[$language])) {
                $languages[$language]++;
            } else {
                $languages[$language] = 1;
            }
        }
        arsort($languages);
        $languages = array_keys($languages);

        return [array_shift($languages), $languages];
    }

    protected function letterCharacter($string, $min = 10): string
    {
        if (strlen($string) < $min) {
            return 0;
        }

        //        return preg_match_all("/[\p{L}\p{N}]/s",$string);
        return preg_match_all("/\p{L}/s", $string);
    }
}
