<?php

namespace App\Libs;

use App\Libs\TLD\TLD;
use Illuminate\Support\Facades\Http;
use League\Uri\BaseUri;
use League\Uri\Uri;

class UrlHelper
{
    public static function hash(string|Uri $url): string
    {
        $uri = $url instanceof Uri ? $url : Uri::new($url);

        return hash('sha256', $uri);
    }

    public static function domain(string|Uri $url, $ignore_www = true): string
    {
        $uri = $url instanceof Uri ? $url : Uri::new($url);
        if ($ignore_www) {
            $domain = preg_replace("/^www\d*\./", '', $uri->getHost());
        } else {
            $domain = $uri->getHost();
        }

        return $domain;
    }

    public static function domainReversed(string|Uri $url): string
    {
        $domain = self::domain($url);
        $domain_chars = preg_split('//ui', $domain);

        return implode('', array_reverse($domain_chars));
    }

    public static function path(string|Uri $url): string
    {
        $uri = $url instanceof Uri ? $url : Uri::new($url);

        return $uri->getPath();
    }

    public static function domainWithHash(string|Uri $url): array
    {
        $uri = $url instanceof Uri ? $url : Uri::new($url);

        return [$uri->getHost(), self::hash($uri)];
    }

    public static function assertFull(string $href, string|Uri $referer): string
    {
        if (str_contains($href, '//')) {
            return $href;
        }

        return BaseUri::from($referer)->resolve($href)->__toString();
    }

    /**
     * @return array|Uri|string|string[]
     */
    public static function encode(string|Uri $url)
    {
        $link = str_replace("\n", '', $url);
        $link = preg_replace("/\s\s+/", '', $link);
        $is_encoded = preg_match('~%[0-9A-F]{2}~i', $link);
        if ($is_encoded) {
            return str_replace(' ', '%20', $link);
        }
        $matches = [];
        $is_match = preg_match('/^https?\:\/\/([\w\-]+\.)+[\w\-]+(\:\d+)?\/?/', $link, $matches);
        if ($is_match) {
            $base = $matches[0];
            $remain = str_replace($base, '', $link);
        } else {
            $base = '';
            $remain = $link;
        }
        $remain = str_replace('/', '__slash__', $remain);
        $remain = str_replace('?', '__question_mark__', $remain);
        $remain = str_replace('&', '__and_mark__', $remain);
        $remain = str_replace('=', '__equal_mark__', $remain);
        $remain = str_replace(',', '__comma_mark__', $remain);
        $remain = str_replace('#', '__sharp_mark__', $remain);
        $remain = str_replace('~', '__approximate__', $remain);
        $remain = str_replace(' ', '__space__', $remain);
        $remain = urlencode($remain);
        $remain = str_replace('__slash__', '/', $remain);
        $remain = str_replace('__question_mark__', '?', $remain);
        $remain = str_replace('__and_mark__', '&', $remain);
        $remain = str_replace('__equal_mark__', '=', $remain);
        $remain = str_replace('__comma_mark__', ',', $remain);
        $remain = str_replace('__sharp_mark__', '#', $remain);
        $remain = str_replace('__approximate__', '~', $remain);
        $remain = str_replace('__space__', '%20', $remain);

        return $base.$remain;
    }

    public static function tld(string $domain, bool $check_public_suffices = true): ?string
    {
        if ($check_public_suffices && $tld = TLD::matchDomain($domain)) {
            return $tld;
        }

        preg_match("/^(https?:\/\/)?([^@\/]+@)?[a-z0-9.\-]+(\.[a-z]{2,10})(:[0-9]+)?\/?$/", $domain, $m);

        return $m[3] ?? null;
    }

    public static function ipInfo(?string $ip)
    {
        if (! $ip) {
            return null;
        }
        $response = Http::get('https://api.iplocation.net', ['ip' => $ip]);

        return json_decode($response->body(), true);
    }

    public static function homepage(string|Uri $url, bool $check_tld = true)
    {
        $uri = $url instanceof Uri ? $url : Uri::createFromString($url);
        $domain = $uri->getHost();
        $scheme = $uri->getScheme();

        if ($check_tld) {
            $tld = self::tld($domain);
            if (preg_match("/\.([^\.]+$tld)/ui", $domain, $matches)) {
                $domain = $matches[1];
            }
        }

        if ($scheme !== null) {
            $scheme = $scheme.':';
        }

        if ($domain !== null) {
            $domain = '//'.$domain;
        }

        return $scheme.$domain;

    }
}
