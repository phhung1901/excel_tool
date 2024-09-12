<?php

namespace App\Libs\Money\Tazapay\DTO;

use Spatie\LaravelData\Data;

class BillingDetailsData extends Data
{
    public function __construct(
        public array $address,
        public string $label,
        public string $name,
        public array $phone,
    ) {
    }
}

/*
 "address": {
      "city": "Singapore",
      "country": "SG",
      "line1": "1st Street",
      "line2": "2nd Avenue",
      "postal_code": "43004",
      "state": "Singapore"
    },
    "label": "Home",
    "name": "Andrea Lark",
    "phone": {
      "calling_code": "65",
      "number": "87654321"
    }
 * */
