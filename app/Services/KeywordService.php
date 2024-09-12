<?php

namespace App\Services;

use App\Ai\Gemini\Sessions\GeminiSession;
use App\Models\Enum\KeywordStatus;
use App\Models\Keyword;
use Crwlr\Crawler\Logger\CliLogger;
use ONGR\ElasticsearchDSL\Query\Specialized\MoreLikeThisQuery;

class KeywordService
{
    public CliLogger $cli;

    public function __construct(){
        $this->cli = new CliLogger();
    }
    public static function remove_stopwords(array $pos, $country = 'vn'): string
    {
        $stopwords = match ($country){
            'vn' => config('stopwords_vn'),
            'es' => config('stopwords_es'),
            'fr' => config('stopwords_fr'),
        };
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
                'min_doc_freq' => 1,
            ]);
        $keywords = Keyword::search($query)
            ->where('status', KeywordStatus::SEARCH_SUCCESS)
            ->get();

//        dd(array_column($keywords->toArray(), 'keyword'));
//        dd($keywords);

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
                $origin = array_push($keyword_same_intent['origin'], [
                    'id' => $keyword->id,
                    'keyword' => $keyword->keyword,
                ]);
                $keyword_same->keyword_intent = $keyword_same_intent;
                $keyword_same->save();
                $keyword->status = KeywordStatus::ORIGIN_KEYWORD;
                $duplicate = array_push($keyword_intent['duplicate'], [
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

    public function keywordFilter(Keyword $keyword) : bool
    {
        if ($this->keywordTypeCheck($keyword)){
            if ($keyword->raw->volume >= 10){
                $this->cli->warning("[Volume]: {$keyword->raw->volume}");
                if ($this->keywordMeanCheck($keyword)){
                    return true;
                }
            }else{
                $this->cli->error("[Volume]: {$keyword->raw->volume}");
            }
        }
        return false;
    }

    protected function keywordTypeCheck(Keyword $keyword) : bool
    {
        $lang = $keyword->file->language;
        $start_with = config("keyword.start_with.$lang");
        if (str_starts_with($keyword->keyword, $start_with)){
            $this->cli->warning("[Type->start with '$start_with']: TRUE");
            return true;
        }
        $this->cli->error("[Type->start with '$start_with']: FALSE");
        return false;
    }

    protected function keywordMeanCheck(Keyword $keyword) : bool
    {
        $retry = 2;
        retry:
        $gemini = new GeminiSession(config("services.ai.gemini.token"));
        $prompt = 'I have a list of keywords, the problem here is that this list is mixed with a lot of keywords that are not my purpose.
                [Purpose is] I only want to get PRODUCT ONLY keywords, other keywords related to questions, recipes, locations, platforms, ... need to be removed.
                [Request] Please answer "Yes" or "No" to each keyword I give if it is or is not suitable for "Purpose"
                Keyword: ' . $keyword->keyword;
        $this->cli->warning("[Ask gemini]: $prompt");
        try {
            $check = $gemini->chat($prompt);
            if (strtolower($check) == 'yes'){
                $this->cli->warning("[Gemini]: $check -> TRUE");
                return true;
            }else{
                $this->cli->error("[Gemini]: $check -> Keywords are not just about a product !");
            }
        }catch (\Exception $e){
            if ($retry >= 0){
                $retry--;
                goto retry;
            }
        }
        return false;
    }
}
