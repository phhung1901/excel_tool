<?php

namespace App\Services\SearxService;

use App\Services\SearxService\Data\NaturalResults;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise;
use GuzzleHttp\Utils;

class SearxClient
{
    protected string $host;

    protected string $token;

    protected Client $client;

    public static array $guzzle_options = [];

    /**
     * ClientAbstract constructor.
     *
     * @param string $host
     * @param string $token
     */
    public function __construct(string $host = '', string $token = '')
    {
        $this->host = $host ?: config('searx.host');
        $this->token = $token ?: config('searx.token');

        $guzzle_config = array_merge(
            [
                'base_uri' => $this->host,
                'headers' => [
                    'Accept' => 'application/json',
                    'content-type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->token,
                ],
                'verify' => false,
            ],
            static::$guzzle_options,
        );
        $this->client = new Client($guzzle_config);
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    public function search(string $engine = '', string $query = '', int $page = 1, int $per_page = 100) {
        $response = $this->client->request('GET', '/search', [
            'query' => [
                'engine' => $engine,
                'query' => $query,
                'page' => $page,
                'per_page' => $per_page
            ]
        ]);
        $response = Utils::jsonDecode($response->getBody()->getContents(), true);
        $natural_result = NaturalResults::from($response);
        if ($natural_result->search_metadata->status == "ERROR") {
            throw new SearxException($natural_result->search_metadata->message);
        }

        return $natural_result;
    }

    public function searchAsync(string $engine = '', string $query = '', int $page = 1, int $per_page = 12)
    {
        return $this->client->getAsync('/search', [
            'query' => [
                'engine' => $engine,
                'query' => $query,
                'page' => $page,
                'per_page' => $per_page
            ]
        ]);
    }

    public function searchWithRetry(string $engine = '', string $query = '', int $page = 1, int $per_page = 100, $retry = 5) {
        start:
        try {
            return $this->search($engine, $query, $page, $per_page);
        } catch (RequestException|SearxException $exception) {
            if ($retry) {
                $retry--;
                goto start;
            }

            throw $exception;
        }

    }

    public function searchAsyncWithRetry(string $engine = '', string $query = '', int $page = 1, int $per_page = 100, $retry = 5) {
        start:
        try {
            return $this->searchAsync($engine, $query, $page, $per_page);
        } catch (RequestException|SearxException $exception) {
            if ($retry) {
                $retry--;
                goto start;
            }

            throw $exception;
        }

    }

    public function searchMultiple(string $engine = '', array $keywords = [], int $page = 1, int $per_page = 12)
    {
        $promises = [];
        foreach ($keywords as $keyword){
            $promises[$keyword['id']] = $this->searchAsyncWithRetry($engine, $keyword['keyword'], $page, $per_page);
        }
        $responses = Promise\Utils::settle($promises)->wait();
        $results = [];
        foreach ($responses as $id => $response) {
            $response = Utils::jsonDecode($response['value']->getBody()->getContents(), true);
            $natural_result = NaturalResults::from($response);
            if ($natural_result->organic_results){
                $results[$id] = $natural_result->organic_results->toArray();
            }else{
                $results[$id] = [];
            }
        }

        return $results;
    }
}
