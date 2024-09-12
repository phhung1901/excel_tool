<?php

namespace App\Libs;

use Symfony\Component\DomCrawler\Crawler;

class MetaInfoExtractor
{
    protected $title_metas = [
        'citation_title',
        'dc.title',
        'eprints.title',
        'og:title',
        'twitter:title',
    ];

    protected $keywords_metas = [
        'citation_keywords',
        'eprints.keywords',
        'dc.subject',
        'keywords',
    ];

    protected $published_date_metas = [
        'og:datepublished', // lower case all chars
        'og:datemodified',
        'og:dateposted',
        'og:datecreated',
        'datepublished', // wp
        'datemodified', // wp
        'dc.date.created',
        'dc:date',
        'dcterms.created', // https://www.alice.cnptia.embrapa.br/alice/handle/doc/1149297
        'dcterms.dateaccepted',
        'citation_date',
        'eprints.date',
        'eprints.datestamp',
    ];

    protected $author_metas = [
        'author',
        'article:author',
        'dc.creator',
        'dc.creator.personalname',
        'citation_author',
        'eprints.creators_name',
    ];

    protected $institution_metas = [
        'eprints.institution',
        'citation_author_institution',
        'bepress_citation_author_institution',
    ];

    protected $publisher_metas = [
        'dc.publisher',
        'dc.source',
    ];

    protected $doi_metas = [
        'citation_doi',
    ];

    protected $issn_metas = [
        'dc.source.issn',
    ];

    protected $license_metas = [

    ];

    protected $keyword_separators = [
        ',', ';', // common
        '，', '；', // chinese
        '،', // arabic
        '•', // others
    ];

    protected $author_separators = [
        ';', // common
        '；', // chinese
        '،', // arabic
        '•', // others
    ];

    public static function getInfo(string|Crawler $html)
    {
        $instance = new self();

        return $html instanceof Crawler ? $instance->parseInfo($html) : $instance->getInfoFromHtml($html);
    }

    protected function getInfoFromHtml($html)
    {
        $crawler = new Crawler();
        $crawler->addHtmlContent($html);

        return $this->parseInfo($crawler);
    }

    protected function parseInfo(Crawler $crawler)
    {
        try {
            $title = trim($crawler->filterXPath('//title')->text());
        } catch (\Exception $ex) {
            $title = '';
        }
        try {
            $meta_tags = $crawler->filterXPath('//meta');
            $metas = [];
            $meta_tags->each(function (Crawler $meta_tag) use (&$metas) {
                try {
                    $name = $meta_tag->attr('name') ?: $meta_tag->attr('property') ?: $meta_tag->attr('itemprop');
                    if ($name) {
                        if (isset($metas[mb_strtolower($name)])) {
                            if (is_array($metas[mb_strtolower($name)])) {
                                $metas[mb_strtolower($name)][] = trim($meta_tag->attr('content'));
                            } else {
                                $metas[mb_strtolower($name)] = [
                                    $metas[mb_strtolower($name)],
                                    trim($meta_tag->attr('content')),
                                ];
                            }
                        } else {
                            $metas[mb_strtolower($name)] = trim($meta_tag->attr('content'));
                        }
                    }
                } catch (\Exception $ex) {

                }
            });
        } catch (\Exception $ex) {
            $metas = [];
        }

        foreach ($this->title_metas as $meta_name) {
            if (isset($metas[$meta_name])) {
                if (is_array($metas[$meta_name])) {
                    continue;
                }
                $title = $metas[$meta_name];
                break;
            }
        }

        return [
            'title' => $title,
            'keywords' => $this->getKeywords($metas),
            'authors' => $this->getAuthors($metas),
            'publisher' => $this->getPublisher($metas),
            'institution' => $this->getInstitution($metas),
            'published_date' => $this->getPublicDate($metas),
            'doi' => $this->getDoi($metas),
            'issn' => $this->getISSN($metas),
            'license' => $this->getLicense($metas),
            'meta' => $metas,
        ];
    }

    protected function getKeywords($metas)
    {
        $crawled_keywords_arrays = [];
        foreach ($this->keywords_metas as $meta_name) {
            if (isset($metas[$meta_name])) {
                if (is_array($metas[$meta_name])) {
                    $crawled_keywords_arrays[] = $metas[$meta_name];
                } else {
                    $crawled_keywords_arrays[] = preg_split('/('.implode('|', $this->keyword_separators)."|\n)/", $metas[$meta_name]);
                }
            }
        }
        if (! empty($crawled_keywords_arrays)) {
            $crawled_keywords_arrays = [
                array_filter($crawled_keywords_arrays[0], function ($value) {
                    return $value != '';
                }),
            ];
        }

        $crawled_keywords = [];

        foreach ($crawled_keywords_arrays as $array) {
            if (count($array) > count($crawled_keywords)) {
                $crawled_keywords = $array;
            }
        }

        return array_map(function ($i) {
            return trim($i);
        }, $crawled_keywords);
    }

    protected function getPublicDate($metas): ?string
    {
        foreach ($this->published_date_metas as $meta_name) {
            if (! empty($metas[$meta_name])) {
                try {
                    $date_string = is_array($metas[$meta_name]) ? reset($metas[$meta_name]) : $metas[$meta_name];

                    return \Date::parse($date_string)->toDateString();
                } catch (\Exception) {
                }
            }
        }

        return null;
    }

    protected function getAuthors($metas)
    {
        $crawled_authors_arrays = [];
        foreach ($this->author_metas as $meta_name) {
            if (isset($metas[$meta_name])) {
                if (is_array($metas[$meta_name])) {
                    $crawled_authors_arrays[] = $metas[$meta_name];
                } else {
                    $crawled_authors_arrays[] = preg_split('/('.implode('|', $this->author_separators)."|\n)/", $metas[$meta_name]);
                }
            }
        }

        $crawled_authors = [];

        foreach ($crawled_authors_arrays as $array) {
            if (count($array) > count($crawled_authors)) {
                $crawled_authors = $array;
            }
        }

        return array_map(function ($i) {
            return trim($i);
        }, $crawled_authors);
    }

    protected function getInstitution($metas)
    {
        foreach ($this->institution_metas as $meta_name) {
            if (! empty($metas[$meta_name])) {
                try {
                    return is_array($metas[$meta_name]) ? reset($metas[$meta_name]) : $metas[$meta_name];
                } catch (\Exception) {
                }
            }
        }

        return null;
    }

    protected function getPublisher($metas)
    {
        foreach ($this->publisher_metas as $meta_name) {
            if (! empty($metas[$meta_name])) {
                try {
                    return is_array($metas[$meta_name]) ? reset($metas[$meta_name]) : $metas[$meta_name];
                } catch (\Exception) {
                }
            }
        }

        return null;
    }

    protected function getDoi($metas)
    {
        foreach ($this->doi_metas as $meta_name) {
            if (! empty($metas[$meta_name])) {
                try {
                    return is_array($metas[$meta_name]) ? reset($metas[$meta_name]) : $metas[$meta_name];
                } catch (\Exception) {
                }
            }
        }

        return null;
    }

    protected function getISSN($metas)
    {
        foreach ($this->issn_metas as $meta_name) {
            if (! empty($metas[$meta_name])) {
                try {
                    return is_array($metas[$meta_name]) ? reset($metas[$meta_name]) : $metas[$meta_name];
                } catch (\Exception) {
                }
            }
        }

        return null;
    }

    protected function getLicense($metas)
    {
        foreach ($this->license_metas as $meta_name) {
            if (! empty($metas[$meta_name])) {
                try {
                    return is_array($metas[$meta_name]) ? reset($metas[$meta_name]) : $metas[$meta_name];
                } catch (\Exception) {
                }
            }
        }

        return null;
    }
}
