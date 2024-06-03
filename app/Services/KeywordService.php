<?php

namespace App\Services;

use App\Models\Enum\KeywordStatus;
use App\Models\Keyword;
use App\Services\SearxService\SearxClient;
use Illuminate\Database\Eloquent\Collection;
use ONGR\ElasticsearchDSL\Query\Specialized\MoreLikeThisQuery;

class KeywordService
{
    public static function POS()
    {

    }

    public static function remove_stopwords(array $pos): string
    {

        $stopwords = config('stopwords');
        $pos_str = "";
        foreach ($pos as $key => $val) {
            if (in_array($val, $stopwords)) {
                unset($pos[$key]);
            } else {
                $pos_str .= str_replace(' ', '_', $val) . " ";
            }
        }
        return trim($pos_str);
    }

    public static function checkIntent(Keyword $keyword)
    {
        $query = new MoreLikeThisQuery($keyword->pos,
            [
                'fields' => ['pos'],
                'min_term_freq' => 1,
            ]);
        $keywords = Keyword::search($query)
            ->where('status', KeywordStatus::SEARCH_SUCCESS)
            ->get();

        $keyword_intent = [
            'origin' => [],
            'duplicate' => [],
        ];

        foreach ($keywords as $keyword_same) {
            $keyword_same_intent = [
                'origin' => [],
                'duplicate' => [],
            ];
            if ($keyword->id == $keyword_same->id) {
                continue;
            }
            $intent_url = array_intersect(
                array_column(array_slice($keyword_same->search_results, 0, 10), 'url'),
                array_column(array_slice($keyword->search_results, 0, 10), 'url')
            );

            if (count($intent_url) >= 4) {
                if ($keyword->raw->volume < $keyword_same->raw->volume) {
                    list($keyword, $keyword_same) = array($keyword_same, $keyword);
                }
                $keyword_same->status = KeywordStatus::DUPLICATE_KEYWORD;
                array_push($keyword_same_intent['origin'], [
                    'id' => $keyword->id,
                    'keyword' => $keyword->keyword,
                ]);
                $keyword_same->keyword_intent = $keyword_same_intent;
                $keyword_same->save();
                $keyword->status = KeywordStatus::ORIGIN_KEYWORD;
                array_push($keyword_intent['duplicate'], [
                    'id' => $keyword_same->id,
                    'keyword' => $keyword_same->keyword,
                ]);
                $keyword->keyword_intent = $keyword_intent;
                $keyword->save();
            } else {
                $keyword->status = KeywordStatus::ORIGIN_KEYWORD;
                if (!$keyword->keyword_intent) {
                    $keyword->keyword_intent = $keyword_intent;
                }
                $keyword->save();
            }
        }
    }
}
