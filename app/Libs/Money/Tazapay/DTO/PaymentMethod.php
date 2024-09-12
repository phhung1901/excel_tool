<?php

namespace App\Libs\Money\Tazapay\DTO;

use App\Services\MathService;
use Spatie\LaravelData\Data;

class PaymentMethod extends Data
{
    public function __construct(
        public int $amount,
        public array $banks,
        public string $currency,
        public string|float $exchange_rate,
        public string $experience_type,
        public string $family,
        public string $group,
        public array $logo_url,
        public string $name,
        public string $notification_type,
        public int|string $transaction_fee,
        public string $type,
    ) {
        $precision = config('money.currencies.'.$this->currency.'.precision');
        if ($precision < 2) {
            $this->amount = app(MathService::class)->div($this->amount, pow(10, (2 - $precision)), 0);
        } elseif ($precision > 2) {
            $this->amount = app(MathService::class)->pow($this->amount, pow(10, ($precision - 2)), 0);
        }
    }
}
