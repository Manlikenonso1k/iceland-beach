Here is a summary of the [Paystack API Introduction](https://paystack.com/docs/api/) that you can share directly with your developer:

### Paystack API Integration Summary

* **Base URL:** All API requests should be made to `[https://api.paystack.co](https://api.paystack.co)` over HTTPS.
* **Environments:** The API supports both Test and Live environments. The environment is determined entirely by the API key used.
* **Authentication:** Requests require your secret key to be passed in the header using the format: `Authorization: Bearer YOUR_SECRET_KEY`.
* **Data Format:** It is a RESTful API. Both request payloads and responses must be formatted as JSON (`Content-Type: application/json`).
* **Response Structure:** Standard responses include:
* `status` (boolean): Indicates success or failure.
* `message` (string): A summary of the response.
* `data` (object or array): The actual result of the request.
* *Note:* Responses might also include a `meta` object containing pagination details or error diagnostics.


* **Currency Handling (Crucial):** All monetary amounts must be sent in the **subunit** of the currency. This means multiplying the base amount by 100 (e.g., ₦50.00 is sent as `5000`). This applies to all supported currencies, even those without fractional subunits like XOF.

Do you need me to summarize any specific endpoints for your agent, such as how to initialize a transaction or create a customer?

[Paystack API Authentication](https://paystack.com/docs/api/authentication/) requires secure, key-based authorization over HTTPS for all server-side requests.

**Authentication & Header Requirements**

* **Header Format:** Pass the secret key in the request header using the Bearer token scheme: `Authorization: Bearer YOUR_SECRET_KEY`. Requests without valid headers return `401 Unauthorized`.
* **HTTPS & SSL:** All requests must use HTTPS. Keep SSL connection verification enabled on your server (`VERIFY_PEER` must be `TRUE`).

**API Key Classification**

* **Public Key (`pk_`):** Used exclusively in frontend integrations (Paystack Inline, Mobile SDKs) to initiate payments.
* **Secret Key (`sk_`):** Used strictly on the backend to execute API requests and control sensitive actions.

**Environments**

* **Test Mode (`pk_test_`, `sk_test_`):** Sandbox environment for development without actual money transfers.
* **Live Mode (`pk_live_`, `sk_live_`):** Production environment for processing real financial transactions.

**Security & Key Management Best Practices**

* **Key Storage:** Store secret keys strictly in environment variables or a secrets manager. Never expose secret keys in frontend code, mobile apps, or version control repositories.
* **IP Whitelisting:** Lock down server requests by specifying up to 10 public IPv4 addresses per environment in the Paystack Dashboard.
* **Key Rotation:** Periodically rotate API keys in your settings, or regenerate them immediately if exposure is suspected.
Here is a summary of the [Paystack API Rate Limits Documentation](https://paystack.com/docs/api/rate-limits/) for your developer:

### Paystack API Rate Limits Summary

* **Behavior & Status Code:** Rate limits are enforced on a **per-endpoint** basis per integration. Exceeding a limit returns an `HTTP 429 Rate limited` status code.
* **Response Headers for `429` Handling:**
* `x-ratelimit-limit`: Max allowed requests per time window.
* `x-ratelimit-remaining`: Remaining allowed requests in the current window.
* `x-ratelimit-reset`: Time (in seconds) remaining until the current window resets. Your agent must parse this value to pause requests until the window clears.


* **Rate Quotas (per 60 Seconds):**
* **Standard Limits:** Live mode allows **600 requests**, while Test mode allows **100 requests**.
* **Extended Limits (Live Mode Only):** High-traffic endpoints have increased capacity:
* **Verify Transaction:** 3,000 req/min
* **Resolve Account Number:** 1,500 req/min
* **Initialize Transaction / Charge Authorization / Create Charge / Check Pending:** 1,200 req/min
* **Initiate Transfer:** 900 req/min




* **Best Practices to Avoid `429` Errors:**
1. **Webhooks over Polling:** Use webhooks to receive transaction updates instead of calling `/transaction/verify/:reference` in a loop.
2. **Cache Stable Lookups:** Store account name lookup results (`/bank/resolve`) locally on your server to avoid redundant calls.
3. **Batch Reconciliation:** Fetch transaction lists using date range parameters (`/transaction`) rather than verifying individual references one by one.
4. **Use Bulk Endpoints:** Use bulk transfer and bulk charge APIs when processing multiple payments simultaneously.

**Core Payment Flow Endpoints**

* **[Initialize Transaction](https://paystack.com/docs/api/transaction/#initialize) (`POST /transaction/initialize`)**
* **Body Parameters:** `email` (string), `amount` (integer in subunits, e.g., `5000` for ₦50.00), `callback_url` (optional), `reference` (optional unique string), `channels` (optional array).
* **Response:** Returns `authorization_url` (redirect customer here to complete payment), `access_code`, and `reference`.


* **[Verify Transaction](https://paystack.com/docs/api/transaction/#verify) (`GET /transaction/verify/:reference`)**
* **Path Parameter:** `:reference`
* **Usage:** Must be executed on your backend post-checkout to verify `data.status == "success"` before fulfilling orders or granting access.
* **Data Returned:** Transaction status, fees, paid date, customer info, and card/bank `authorization_code`.



**Management & Analytics Endpoints**

* **[List Transactions](https://paystack.com/docs/api/transaction/#list) (`GET /transaction`)**
* **Query Parameters:** `perPage`, `page`, `status` (`success`, `failed`, `abandoned`), `from`, `to`, `customer`.
* **Usage:** Retrieves payment logs for backend reconciliation dashboards.


* **[Fetch Transaction](https://paystack.com/docs/api/transaction/#fetch) (`GET /transaction/:id`)**
* **Path Parameter:** `:id` (represented as an unsigned 64-bit integer).
* **Usage:** Pulls complete details and attempt history for a specific transaction record.



**Recurring & Advanced Payments**

* **[Charge Authorization](https://paystack.com/docs/api/transaction/#charge-authorization) (`POST /transaction/charge_authorization`)**
* **Body Parameters:** `email`, `amount`, `authorization_code` (obtained from previous successful payments where `reusable` is true).
* **Usage:** Enables automated recurring charges or subscription billing without customer interaction.


* **[Partial Debit](https://paystack.com/docs/api/transaction/#partial-debit) (`POST /transaction/partial_debit`)**
* **Body Parameters:** `authorization_code`, `currency` (`NGN` or `GHS`), `amount`, `email`, `at_least` (optional minimum).
* **Usage:** Recovers a portion of an amount owed if a full authorization charge fails due to insufficient funds.

Here is the final piece of the puzzle for your agent. This covers how to securely listen for payments behind the scenes and how to thoroughly test the integration without using real money.

### 1. Webhooks & Security Summary

Webhooks are crucial. If a customer's browser crashes or they lose internet right after paying, they will never be redirected to your site. Paystack solves this by immediately sending a server-to-server POST request to your webhook URL whenever a payment succeeds.

* **Endpoint Configuration:** You define your Webhook URL in the Paystack Dashboard.
* **The Request:** Paystack will send a `POST` request to this URL containing a JSON payload with the event details (e.g., `event: "charge.success"`).
* **Security & Signature Verification (Critical):** To prevent malicious users from sending fake payment successes to your server, your agent **must** verify the signature.
* Look for the `x-paystack-signature` in the request headers.
* Hash the incoming raw request body using **HMAC SHA512** with your Paystack **Secret Key**.
* Compare your generated hash to the `x-paystack-signature`. If they match, the request is authentically from Paystack.


* **Response:** Your server must acknowledge receipt by immediately returning a `200 OK` HTTP status. Do not run heavy database operations before returning the `200 OK`, or Paystack might assume the webhook failed and try sending it again.

---

### 2. Testing Credentials Summary

When your API keys are in Test Mode (using keys that start with `sk_test_` and `pk_test_`), Paystack will not process real money, and real credit cards will be declined.

Your agent must use Paystack's official Test Cards to simulate different scenarios. For all test cards, you can use **any future expiry date** (e.g., 12/26) and **any 3-digit CVV** (e.g., 123).

* **Test Successful Payment:**
* **Card Number:** `4084084084084081` (Visa)
* **PIN (if prompted):** `3110`
* **OTP (if prompted):** `123456`


* **Test Insufficient Funds (Fails):**
* **Card Number:** `4084084084084082`


* **Test Declined Card (Fails):**
* **Card Number:** `4084084084084083`



### The Final Integration Checklist for your Agent:

1. **Frontend:** Collect email and amount -> pass them to your backend.
2. **Backend (Initialize):** Call `POST /transaction/initialize` with your `sk_test_` key. Get the `authorization_url`.
3. **Frontend (Checkout):** Redirect the user to the `authorization_url`. The user enters the `408408...` test card.
4. **Backend (Webhook):** Paystack silently POSTs `charge.success` to your webhook URL. Your server verifies the `x-paystack-signature`, finds the user in your database, and marks their order as paid.
5. **Frontend (Callback):** The user is redirected back to your website's success page.

With this, your developer has everything they need to build, test, and deploy a fully secure payment system on your website! Do you have any other questions before they get started?


POST
/transaction/initialize
#!/bin/sh
url="https://api.paystack.co/transaction/initialize"
authorization="Authorization: Bearer YOUR_SECRET_KEY"
content_type="Content-Type: application/json"
data='{ 
  "email": "customer@email.com", 
  "amount": "20000"
}'

curl "$url" -H "$authorization" -H "$content_type" -d "$data" -X POST

Sample Response

200 Ok


{
  "status": true,
  "message": "Authorization URL created",
  "data": {
    "authorization_url": "https://checkout.paystack.com/3ni8kdavz62431k",
    "access_code": "3ni8kdavz62431k",
    "reference": "re4lyvq3s3"
  }
}

GET
/transaction/verify/:reference
#!/bin/sh
url="https://api.paystack.co/transaction/verify/{reference}"
authorization="Authorization: Bearer YOUR_SECRET_KEY"

curl "$url" -H "$authorization" -X GET

Sample Response

200 Ok
200 Ok
{
  "status": true,
  "message": "Verification successful",
  "data": {
    "id": 4099260516,
    "domain": "test",
    "status": "success",
    "reference": "re4lyvq3s3",
    "receipt_number": null,
    "amount": 40333,
    "message": null,
    "gateway_response": "Successful",
    "paid_at": "2024-08-22T09:15:02.000Z",
    "created_at": "2024-08-22T09:14:24.000Z",
    "channel": "card",
    "currency": "NGN",
    "ip_address": "197.210.54.33",
    "metadata": "",
    "log": {
      "start_time": 1724318098,
      "time_spent": 4,
      "attempts": 1,
      "errors": 0,
      "success": true,
      "mobile": false,
      "input": [],
      "history": [
        {
          "type": "action",
          "message": "Attempted to pay with card",
          "time": 3
        },
        {
          "type": "success",
          "message": "Successfully paid with card",
          "time": 4
        }
      ]
    },
    "fees": 10283,
    "fees_split": null,
    "authorization": {
      "authorization_code": "AUTH_uh8bcl3zbn",
      "bin": "408408",
      "last4": "4081",
      "exp_month": "12",
      "exp_year": "2030",
      "channel": "card",
      "card_type": "visa ",
      "bank": "TEST BANK",
      "country_code": "NG",
      "brand": "visa",
      "reusable": true,
      "signature": "SIG_yEXu7dLBeqG0kU7g95Ke",
      "account_name": null
    },
    "customer": {
      "id": 181873746,
      "first_name": null,
      "last_name": null,
      "email": "demo@test.com",
      "customer_code": "CUS_1rkzaqsv4rrhqo6",
      "phone": null,
      "metadata": null,
      "risk_action": "default",
      "international_format_phone": null
    },
    "plan": null,
    "split": {},
    "order_id": null,
    "paidAt": "2024-08-22T09:15:02.000Z",
    "createdAt": "2024-08-22T09:14:24.000Z",
    "requested_amount": 30050,
    "pos_transaction_data": null,
    "source": null,
    "fees_breakdown": null,
    "connect": null,
    "transaction_date": "2024-08-22T09:14:24.000Z",
    "plan_object": {},
    "subaccount": {}
  }
}
GET
/transaction

#!/bin/sh
url="https://api.paystack.co/transaction"
authorization="Authorization: Bearer YOUR_SECRET_KEY"

curl "$url" -H "$authorization" -X GET

{
  "status": true,
  "message": "Transactions retrieved",
  "data": [
    {
      "id": 4099260516,
      "domain": "test",
      "status": "success",
      "reference": "re4lyvq3s3",
      "amount": 40333,
      "message": null,
      "gateway_response": "Successful",
      "paid_at": "2024-08-22T09:15:02.000Z",
      "created_at": "2024-08-22T09:14:24.000Z",
      "channel": "card",
      "currency": "NGN",
      "ip_address": "197.210.54.33",
      "metadata": null,
      "log": {
        "start_time": 1724318098,
        "time_spent": 4,
        "attempts": 1,
        "errors": 0,
        "success": true,
        "mobile": false,
        "input": [],
        "history": [
          {
            "type": "action",
            "message": "Attempted to pay with card",
            "time": 3
          },
          {
            "type": "success",
            "message": "Successfully paid with card",
            "time": 4
          }
        ]
      },
      "fees": 10283,
      "fees_split": null,
      "customer": {
        "id": 181873746,
        "first_name": null,
        "last_name": null,
        "email": "demo@test.com",
        "phone": null,
        "metadata": {
          "custom_fields": [
            {
              "display_name": "Customer email",
              "variable_name": "customer_email",
              "value": "new@email.com"
            }
          ]
        },
        "customer_code": "CUS_1rkzaqsv4rrhqo6",
        "risk_action": "default"
      },
      "authorization": {
        "authorization_code": "AUTH_uh8bcl3zbn",
        "bin": "408408",
        "last4": "4081",
        "exp_month": "12",
        "exp_year": "2030",
        "channel": "card",
        "card_type": "visa ",
        "bank": "TEST BANK",
        "country_code": "NG",
        "brand": "visa",
        "reusable": true,
        "signature": "SIG_yEXu7dLBeqG0kU7g95Ke",
        "account_name": null
      },
      "plan": {},
      "split": {},
      "subaccount": {},
      "order_id": null,
      "paidAt": "2024-08-22T09:15:02.000Z",
      "createdAt": "2024-08-22T09:14:24.000Z",
      "requested_amount": 30050,
      "source": {
        "source": "merchant_api",
        "type": "api",
        "identifier": null,
        "entry_point": "transaction_initialize"
      },
      "connect": null,
      "pos_transaction_data": null
    }
  ],
  "meta": {
    "next": "dW5kZWZpbmVkOjQwMTM3MDk2MzU=",
    "previous": null,
    "perPage": 50
  }
}

#!/bin/sh
url="https://api.paystack.co/transaction/{id}"
authorization="Authorization: Bearer YOUR_SECRET_KEY"

curl "$url" -H "$authorization" -X GET

{
  "status": true,
  "message": "Transaction retrieved",
  "data": {
    "id": 4099260516,
    "domain": "test",
    "status": "success",
    "reference": "re4lyvq3s3",
    "receipt_number": null,
    "amount": 40333,
    "message": null,
    "gateway_response": "Successful",
    "helpdesk_link": null,
    "paid_at": "2024-08-22T09:15:02.000Z",
    "created_at": "2024-08-22T09:14:24.000Z",
    "channel": "card",
    "currency": "NGN",
    "ip_address": "197.210.54.33",
    "metadata": "",
    "log": {
      "start_time": 1724318098,
      "time_spent": 4,
      "attempts": 1,
      "errors": 0,
      "success": true,
      "mobile": false,
      "input": [],
      "history": [
        {
          "type": "action",
          "message": "Attempted to pay with card",
          "time": 3
        },
        {
          "type": "success",
          "message": "Successfully paid with card",
          "time": 4
        }
      ]
    },
    "fees": 10283,
    "fees_split": null,
    "authorization": {
      "authorization_code": "AUTH_uh8bcl3zbn",
      "bin": "408408",
      "last4": "4081",
      "exp_month": "12",
      "exp_year": "2030",
      "channel": "card",
      "card_type": "visa ",
      "bank": "TEST BANK",
      "country_code": "NG",
      "brand": "visa",
      "reusable": true,
      "signature": "SIG_yEXu7dLBeqG0kU7g95Ke",
      "account_name": null
    },
    "customer": {
      "id": 181873746,
      "first_name": null,
      "last_name": null,
      "email": "demo@test.com",
      "customer_code": "CUS_1rkzaqsv4rrhqo6",
      "phone": null,
      "metadata": {
        "custom_fields": [
          {
            "display_name": "Customer email",
            "variable_name": "customer_email",
            "value": "new@email.com"
          }
        ]
      },
      "risk_action": "default",
      "international_format_phone": null
    },
    "plan": {},
    "subaccount": {},
    "split": {},
    "order_id": null,
    "paidAt": "2024-08-22T09:15:02.000Z",
    "createdAt": "2024-08-22T09:14:24.000Z",
    "requested_amount": 30050,
    "pos_transaction_data": null,
    "source": {
      "type": "api",
      "source": "merchant_api",
      "identifier": null
    },
    "fees_breakdown": null,
    "connect": null
  }
}

#!/bin/sh
url="https://api.paystack.co/transaction/charge_authorization"
authorization="Authorization: Bearer YOUR_SECRET_KEY"
content_type="Content-Type: application/json"
data='{ 
  "email": "customer@email.com", 
  "amount": "20000", 
  "authorization_code": "AUTH_72btv547"
}'

curl "$url" -H "$authorization" -H "$content_type" -d "$data" -X POST

{
  "status": true,
  "message": "Charge attempted",
  "data": {
    "amount": 35247,
    "currency": "NGN",
    "transaction_date": "2024-08-22T10:53:49.000Z",
    "status": "success",
    "reference": "0m7frfnr47ezyxl",
    "domain": "test",
    "metadata": "",
    "gateway_response": "Approved",
    "message": null,
    "channel": "card",
    "ip_address": null,
    "log": null,
    "fees": 10247,
    "authorization": {
      "authorization_code": "AUTH_uh8bcl3zbn",
      "bin": "408408",
      "last4": "4081",
      "exp_month": "12",
      "exp_year": "2030",
      "channel": "card",
      "card_type": "visa ",
      "bank": "TEST BANK",
      "country_code": "NG",
      "brand": "visa",
      "reusable": true,
      "signature": "SIG_yEXu7dLBeqG0kU7g95Ke",
      "account_name": null
    },
    "customer": {
      "id": 181873746,
      "first_name": null,
      "last_name": null,
      "email": "demo@test.com",
      "customer_code": "CUS_1rkzaqsv4rrhqo6",
      "phone": null,
      "metadata": {
        "custom_fields": [
          {
            "display_name": "Customer email",
            "variable_name": "customer_email",
            "value": "new@email.com"
          }
        ]
      },
      "risk_action": "default",
      "international_format_phone": null
    },
    "plan": null,
    "id": 4099490251
  }
}

#!/bin/sh
url="https://api.paystack.co/transaction/timeline/{id_or_reference}"
authorization="Authorization: Bearer YOUR_SECRET_KEY"

curl "$url" -H "$authorization" -X GET

{
  "status": true,
  "message": "Timeline retrieved",
  "data": {
    "start_time": 1724318098,
    "time_spent": 4,
    "attempts": 1,
    "errors": 0,
    "success": true,
    "mobile": false,
    "input": [],
    "history": [
      {
        "type": "action",
        "message": "Attempted to pay with card",
        "time": 3
      },
      {
        "type": "success",
        "message": "Successfully paid with card",
        "time": 4
      }
    ]
  }
}

#!/bin/sh
url="https://api.paystack.co/transaction/totals"
authorization="Authorization: Bearer YOUR_SECRET_KEY"

curl "$url" -H "$authorization" -X GET

{
  "status": true,
  "message": "Transaction totals",
  "data": {
    "total_transactions": 42670,
    "total_volume": 6617829946,
    "total_volume_by_currency": [
      {
        "currency": "NGN",
        "amount": 6617829946
      },
      {
        "currency": "USD",
        "amount": 28000
      }
    ],
    "pending_transfers": 6617829946,
    "pending_transfers_by_currency": [
      {
        "currency": "NGN",
        "amount": 6617829946
      },
      {
        "currency": "USD",
        "amount": 28000
      }
    ]
  }
}

#!/bin/sh
url="https://api.paystack.co/transaction/export"
authorization="Authorization: Bearer YOUR_SECRET_KEY"

curl "$url" -H "$authorization" -X GET

{
  "status": true,
  "message": "Export successful",
  "data": {
    "path": "https://s3.eu-west-1.amazonaws.com/files.paystack.co/exports/463433/transactions/Integration_name_transactions_1724324423843.csv?X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=AKIAI7CL5IZL2DJHOPPA%2F20240822%2Feu-west-1%2Fs3%2Faws4_request&X-Amz-Date=20240822T110023Z&X-Amz-Expires=60&X-Amz-Signature=40525f4f361e07c09a445a1a6888d135758abd507ed988ee744c2d94ea14cf1e&X-Amz-SignedHeaders=host",
    "expiresAt": "2024-08-22 11:01:23"
  }
}

#!/bin/sh
url="https://api.paystack.co/transaction/partial_debit"
authorization="Authorization: Bearer YOUR_SECRET_KEY"
content_type="Content-Type: application/json"
data='{ 
  "authorization_code": "AUTH_72btv547", 
  "currency": "NGN", 
  "amount": "20000",
  "email": "customer@email.com"
}'

curl "$url" -H "$authorization" -H "$content_type" -d "$data" -X POST

{
  "status": true,
  "message": "Charge attempted",
  "data": {
    "amount": 50000,
    "currency": "NGN",
    "transaction_date": "2024-08-22T11:13:48.000Z",
    "status": "success",
    "reference": "ofuhmnzw05vny9j",
    "domain": "test",
    "metadata": "",
    "gateway_response": "Approved",
    "message": null,
    "channel": "card",
    "ip_address": null,
    "log": null,
    "fees": 10350,
    "authorization": {
      "authorization_code": "AUTH_uh8bcl3zbn",
      "bin": "408408",
      "last4": "4081",
      "exp_month": "12",
      "exp_year": "2030",
      "channel": "card",
      "card_type": "visa ",
      "bank": "TEST BANK",
      "country_code": "NG",
      "brand": "visa",
      "reusable": true,
      "signature": "SIG_yEXu7dLBeqG0kU7g95Ke",
      "account_name": null
    },
    "customer": {
      "id": 181873746,
      "first_name": null,
      "last_name": null,
      "email": "demo@test.com",
      "customer_code": "CUS_1rkzaqsv4rrhqo6",
      "phone": null,
      "metadata": {
        "custom_fields": [
          {
            "display_name": "Customer email",
            "variable_name": "customer_email",
            "value": "new@email.com"
          }
        ]
      },
      "risk_action": "default",
      "international_format_phone": null
    },
    "plan": 0,
    "requested_amount": 50000,
    "id": 4099546180
  }
}