<?php

namespace App\Libs;

use GuzzleHttp\Client;
use GuzzleHttp\Utils;

class RewriteSentenceClient
{
    protected string $host;

    protected string $token;

    protected Client $client;

    public static array $guzzle_options = [];

    /**
     * ClientAbstract constructor.
     */
    public function __construct(?string $host = null, ?string $token = null)
    {
        $this->host = $host ?: config('doc_services.text_rewrite.host');
        $this->token = $token ?: config('doc_services.text_rewrite.token');

        $guzzle_config = array_merge(
            [
                'base_uri' => $this->host,
                'headers' => [
                    'Accept' => 'application/json',
                    'content-type' => 'application/json',
                    'Authorization' => 'Bearer '.$this->token,
                ],
                'verify' => false,
            ],
            static::$guzzle_options,
        );
        $this->client = new Client($guzzle_config);
    }

    public function rewrite_text(string $text)
    {
        $response = $this->client->post('/rewrite_text', [
            'form_params' => [
                'text' => $text,
            ],
        ]);
        $response = Utils::jsonDecode($response->getBody()->getContents(), true);

        if ($response['success']) {
            return $response['data'];
        } else {
            throw new \Exception('Error rewrite: '.$response['message']);
        }
    }
}
