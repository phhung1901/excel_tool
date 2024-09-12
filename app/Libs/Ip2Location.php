<?php

namespace App\Libs;

use GuzzleHttp\Client;
use GuzzleHttp\Utils;

class Ip2Location
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
        $this->host = $host ?: config('doc_services.ip2location.host');
        $this->token = $token ?: config('doc_services.ip2location.token');

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

    public function fromIp(string $ip)
    {
        $response = $this->client->get('/', [
            'query' => [
                'ip' => $ip,
            ],
        ]);

        return Utils::jsonDecode($response->getBody()->getContents(), true);

    }
}
