<?php

namespace App\Libs\Money\Tazapay\DTO;

use Spatie\LaravelData\Data;

class TazapayV3OrderData extends Data
{
    public function __construct(
        public ?string $amount = '',
        public ?string $amount_paid = '',
        public ?array $billing_details = [],
        public ?string $cancel_url = '',
        public ?string $created_at = '',
        public ?string $customer = '',
        public ?array $customer_details = [],
        public ?string $customer_fee_percentage = '',
        public ?string $expires_at = '',
        public ?string $id = '',
        public ?string $invoice_currency = '',
        public ?string $latest_payment_attempt = '',
        public ?string $metadata = '',
        public ?string $object = '',
        public ?string $paid_in_excess = '',
        public ?string $partially_paid = '',
        public ?string $payin = '',
        public ?array $payment_attempts = [],
        public ?array $payment_methods = [],
        public ?string $payment_status = '',
        public ?string $payment_status_description = '',
        public ?string $reference_id = '',
        public ?array $remove_payment_methods = [],
        public ?array $shipping_details = [],
        public ?string $status = '',
        public ?string $success_url = '',
        public ?string $token = '',
        public ?string $transaction_description = '',
        public ?array $transaction_documents = [],
        public ?string $url = '',
        public ?string $webhook_url = '',
    ) {
    }
}

/*{
    "amount": 100000,
    "amount_paid": 0,
    "billing_details": {
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
    },
    "cancel_url": "https://mystore.com/try_again",
    "created_at": "2023-10-04T02:31:58.711664573Z",
    "customer": "cus_ckakah13fsk04j60lsh0",
    "customer_details": {
      "country": "SG",
      "email": "andrea@example.com",
      "name": "Andrea Lark",
      "phone": {
        "calling_code": "65",
        "number": "87654321"
      }
    },
    "customer_fee_percentage": 0,
    "expires_at": "2024-07-21T14:01:04.576356Z",
    "id": "chk_ckect7jqoa8krebhaovg",
    "invoice_currency": "USD",
    "latest_payment_attempt": "",
    "metadata": null,
    "object": "checkout",
    "paid_in_excess": false,
    "partially_paid": false,
    "payin": "chk_ckect7jqoa8krebhaovg",
    "payment_attempts": [],
    "payment_methods": [
      "paynow_sgd",
      "card"
    ],
    "payment_status": "unpaid",
    "payment_status_description": "",
    "reference_id": "mystore_order_00001",
    "remove_payment_methods": [],
    "shipping_details": {
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
    },
    "status": "active",
    "success_url": "https://mystore.com/success_page",
    "token": "P5BIYmd4Z4zZ-sHtnnwMPrJtamBNYN3n2wLONmnVwdI=",
    "transaction_description": "1 x T-shirt",
    "transaction_documents": [],
    "url": "https://checkout-sandbox.tazapay.com/transaction/P5BIYmd4Z4zZ-sHtnnwMPrJtamBNYN3n2wLONmnVwdI=",
    "webhook_url": "https://mystore.com/internal/webhook"
  }*/
