<?php

namespace App\Libs\Money\Paypal;

use App\Models\Money\Data\DepositMetaData;
use App\Models\Money\Data\PaypalOrderMetaData;
use GuzzleHttp\Client;

class PaypalV2Api
{
    protected Client $client;

    public function __construct()
    {
        $this->client = $this->initClient(
            config('wallets.channels.paypal.base_uri'),
            config('wallets.channels.paypal.client_id'),
            config('wallets.channels.paypal.client_secret'),
        );
    }

    protected function initClient($base_uri, $client_id, $client_secret): Client
    {
        $path = '/v1/oauth2/token';
        $auth = base64_encode($client_id.':'.$client_secret);
        $response = (new Client([
            'base_uri' => $base_uri,
            'headers' => [
                'Authorization' => 'Basic '.$auth,
            ],
        ]))->post($path, [
            'body' => 'grant_type=client_credentials',
        ]);
        $response_data = json_decode($response->getBody()->getContents(), true);
        $access_token = $response_data['access_token'];

        return new Client([
            'base_uri' => config('wallets.channels.paypal.base_uri'),
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$access_token,
            ],
        ]);
    }

    public function createOrder(DepositMetaData $depositMetaData): PaypalOrderMetaData
    {
        $path = '/v2/checkout/orders';
        $response = $this->client->post($path, [
            'json' => [
                'intent' => 'CAPTURE',
                'purchase_units' => $depositMetaData->toPaypalPurchaseUnits(),
                'payment_source' => [
                    'paypal' => [
                        'experience_context' => [
                            'brand_name' => \Config::get('app.name'),
                            'shipping_preference' => 'NO_SHIPPING',
                            'return_url' => route('api.paypal.order_capture'),
                            'cancel_url' => route('v4.user.my_wallets'),
                        ],
                    ],
                ],
            ],
        ]);
        $response_data = json_decode($response->getBody()->getContents(), true);

        return PaypalOrderMetaData::from($response_data);
    }

    public function captureOrder($order_id): PaypalOrderMetaData
    {
        $path = '/v2/checkout/orders/'.$order_id.'/capture';
        $response = $this->client->post($path);
        $response_data = json_decode($response->getBody()->getContents(), true);

        return PaypalOrderMetaData::from($response_data);
    }

    public function orderInfo($order_id): PaypalOrderMetaData
    {
        $path = '/v2/checkout/orders/'.$order_id;
        $response = $this->client->get($path);
        $response_data = json_decode($response->getBody()->getContents(), true);

        return PaypalOrderMetaData::from($response_data);
    }

    public function cancelOrder($order_id): PaypalOrderMetaData
    {
        $path = '/v2/checkout/orders/'.$order_id;
        $response = $this->client->get($path);
        $response_data = json_decode($response->getBody()->getContents(), true);

        return PaypalOrderMetaData::from($response_data);
    }
}
