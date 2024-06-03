<?php
namespace App\Services\Tokenizer;

use GuzzleHttp\Client;

class TokenizerClient
{
    public Client $client;
    public function __construct(){
        $this->client = new Client([
            'base_uri' => config('tokenizer.host'),
        ]);
    }

    public function tokenize(string $keyword)
    {
        $response = $this->client->request('GET', "tokenize?text=$keyword", []);
        return json_decode($response->getBody()->getContents(), true);
    }
}
