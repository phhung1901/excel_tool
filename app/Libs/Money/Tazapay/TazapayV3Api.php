<?php

namespace App\Libs\Money\Tazapay;

use App\Enum\TransactionServiceEnum;
use App\Libs\Money\Tazapay\DTO\PaymentMethod;
use App\Libs\Money\Tazapay\DTO\TazapayV3OrderData;
use App\Models\Money\Data\DepositMetaData;
use App\Models\Money\Data\TransactionMetaData;
use App\Models\Money\Wallet;
use App\Models\User;
use App\Services\Money\WalletService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

class TazapayV3Api
{
    protected Client $client;

    public function __construct()
    {
        $this->client = $this->initClient(
            config('wallets.channels.tazapay.baseApiUrl'),
            config('wallets.channels.tazapay.apiKey'),
            config('wallets.channels.tazapay.secretKey'),
        );
    }

    protected function initClient($base_uri, $client_id, $client_secret): Client
    {
        return new Client([
            'base_uri' => $base_uri,
            'headers' => [
                'Authorization' => 'Basic '.base64_encode($client_id.':'.$client_secret),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function getPaymentMethods($amount_cents, $invoice_currency = 'usd', $customer_country = 'us', $no_cache = false)
    {
        $key = 'tazapay_methods_'.$amount_cents.$invoice_currency.'_'.$customer_country;
        if (! $no_cache && $methods = \Cache::get($key)) {
            if (! empty($methods)) {
                return PaymentMethod::collection($methods);
            }
        }
        $response = $this->client->get('v3/metadata/collect', [
            'query' => [
                'amount' => $amount_cents,
                'invoice_currency' => strtoupper($invoice_currency),
                'customer_country' => strtoupper($customer_country),
            ],
        ]);
        $response = json_decode($response->getBody()->getContents(), true);
        if ($response['status'] != 'success') {
            throw new TazapayException('Error '.$response['status'].': '.$response['message']);
        }
        if (! $no_cache) {
            \Cache::set($key, $response['data']['payment_methods']);
        }

        return PaymentMethod::collection($response['data']['payment_methods']);
    }

    public function fetchCheckout($tazapay_order_id): ?TazapayV3OrderData
    {
        $response = $this->client->get('v3/checkout/'.$tazapay_order_id);
        $response = json_decode($response->getBody()->getContents(), true);
        if ($response['status'] != 'success') {
            throw new TazapayException('Error '.$response['status'].': '.$response['message']);
        }

        return TazapayV3OrderData::from($response['data']);
    }

    public function createCheckout(
        DepositMetaData $deposit_data, User $user, Wallet $wallet, array $payment_methods, $country = 'ID',
    ): ?TazapayV3OrderData {
        $transaction_meta = new TransactionMetaData(depositMetaData: $deposit_data);

        // make transaction
        $transaction = WalletService::deposit(
            $wallet,
            $deposit_data->amount,
            TransactionServiceEnum::TAZAPAY,
            '',
            $transaction_meta,
            false
        );

        // make tazapay order
        $uri = 'v3/checkout';
        $request_data = [
            'amount' => $deposit_data->amountInt(),
            'invoice_currency' => strtoupper($deposit_data->currency),
            'customer_details' => [
                'name' => $user->name,
                'email' => $user->email,
                'country' => $country,
            ],
            'transaction_description' => $deposit_data->description,
            'expires_at' => now()->addHour()->utc()->format('Y-m-d\TH:i:s\Z'),
            'payment_methods' => $payment_methods,
            'reference_id' => 'transactions_'.$transaction->id,
            //            'customer' => "users_" . $user->id,
            //                'customer_fee_percentage' => 0,
        ];

        $request_data['success_url'] = route('api.tazapay.order_success', ['transaction' => $transaction->id]);
        $request_data['cancel_url'] = route('api.tazapay.order_cancel', ['transaction' => $transaction->id]);
        $request_data['webhook_url'] = route('api.tazapay.order_webhook', ['transaction' => $transaction->id]);

        try {
            $response = retry(2, fn () => $this->client->post($uri, [
                'json' => $request_data,
            ]), 3000);
            $response = json_decode($response->getBody()->getContents(), true);
            if ($response['status'] != 'success') {
                throw new TazapayException('Error '.$response['status'].': '.$response['message']);
            }
            $tazapay_order_data = TazapayV3OrderData::from($response['data']);

            $transaction->service_order_id = $tazapay_order_data->id;
            $transaction_meta->service_data['tazapay_order'] = $tazapay_order_data;
            $transaction->meta = $transaction_meta;
            $transaction->save();

            return $tazapay_order_data;
        } catch (ClientException|ServerException $ex) {
            $transaction->delete();
            \Log::alert('Tazapay create order error : '.$ex->getMessage(), $request_data);

            return null;
        }
    }
}
