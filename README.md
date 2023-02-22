# MPESA REST API for Laravel
Laravel Package for the MPESA REST API

## Installation

### Add this package using Composer

From the command line inside your project directory, simply type:

`composer require knox/mpesa-rest`

### Update your config

<b>NB: from laravel 5.5 due to autodiscovery the below can be skipped</b>

Add the service provider to the providers array in config/app.php:

`Knox\MPESA\MpesaServiceProvider::class`

Add the facade to the aliases array in config/app.php:

`'MPESA' => Knox\MPESA\Facades\MPESA::class` 

### Publish the package configuration

Publish the configuration file and migrations by running the provided console command:

`php artisan vendor:publish --provider="Knox\MPESA\MpesaServiceProvider"`

## Setup
 
### Environmental Variables
MPESA\_ENV=`'live' for production and 'test' for sandbox`<br/>
MPESA\_CONSUMER\_KEY=`consumer key`<br/>
MPESA\_CONSUMER\_SECRET=`consumer secret`<br/>
MPESA\_IDENTIFIER=shortcode `shortcode or till`<br/>
MPESA\_SHORT\_CODE=`shortcode`<br/>
MPESA\_PASSKEY=`passkey`<br/>

MPESA\_INITIATOR\_NAME=`initiator username`<br/>
MPESA\_INITIATOR\_PASSWORD=`initiator password`<br/>

MPESA\_B2C\_TIMEOUT\_URL=`url in your site`<br/>
MPESA\_B2C\_RESULT\_URL=`url in your site`<br/>
MPESA\_B2B\_TIMEOUT\_URL=`url in your site`<br/>
MPESA\_B2B\_RESULT\_URL=`url in your site`<br/>

MPESA\_STK\_CALLBACK\_URL=`url in your site`<br/>
MPESA\_C2B\_VALIDATION\_URL=`url in your site`<br/>
MPESA\_C2B\_CONFIRMATION\_URL=`url in your site`<br/>

MPESA\_ACCOUNT\_BALANCE\_TIMEOUT\_URL=`url in your site`<br/>
MPESA\_ACCOUNT\_BALANCE\_CONFIRMATION\_URL=`url in your site`<br/>

MPESA\_REVERSAL\_TIMEOUT\_URL=`url in your site`<br/>
MPESA\_REVERSAL\_CONFIRMATION\_URL=`url in your site`<br/>

MPESA\_TRANSACTION\_STATUS\_TIMEOUT\_URL=`url in your site`<br/>
MPESA\_TRANSACTION\_STATUS\_CONFIRMATION\_URL=`url in your site`<br/>

MPESA\_IDENTITY\_CALLBACK\_URL=`url in your site`<br/>
   


## Usage
At the top of your controller include the facade<br/>
`use MPESA;`

### Registration of C2B Urls
<b>If you haven't registered the mpesa url callbacks then use the below</b><br/>

```php
use MPESA;
use Illuminate\Http\Request;

class PaymentsController extends Controller
{
    public function registerURL(){
        $mpesa = MPESA::registerC2bUrl();
    }
}
```
#### Posible Operations

```
<!-- @param(phone_number, amount, payment_type, @remarks) -->
mpesa = MPESA::b2c('254700123456',10,'PromotionPayment', 'No Remarks');

<!-- @param(short_code, amount, command, sender_identifier, receiver_identifier, @remarks, @account = '') -->
mpesa = MPESA::b2b('600000',100,'BusinessPayBill',4,4, 'No Remarks',123456);

<!-- @param(short_code, amount, command, sender_identifier, receiver_identifier, @remarks, @account = '') -->
mpesa = MPESA::b2b('600000',100,'BusinessBuyGoods',4,4, 'No Remarks');

<!-- @param() -->
mpesa = MPESA::registerC2bUrl();

<!-- @param(phone, amount, command, account, @short_code) -->
mpesa = MPESA::c2bSimulate('254700123456',100,'CustomerPayBillOnline','123456');
mpesa = MPESA::c2bSimulate('254700123456',1000,'CustomerBuyGoodsOnline');

<!-- @param(@remarks) -->
mpesa = MPESA::getAccountBalance();

<!-- @param(transaction, amount, receiver = null, receiver_identifier = 11, @remarks, @occassion) -->
mpesa = MPESA::doReversal('ND893KKHX1', 100, 602984, 4);

<!-- @param(phone, amount, account, @description) -->
mpesa = MPESA::stkPush('254700123456', 1000, 'Account 123');

<!-- @param(checkout_request_id) -->
mpesa = MPESA::stkQuery('ws_CO_14092017184227664');

<!-- @param(transaction, party = '', identifier_type = '4', @remarks, @occassion) -->
mpesa = MPESA::getTransactionStatus('ND893KKHX1', null,4);
```

#### Example response handler
```
public function c2bConfirmation(Request $request)
{
    $response = json_decode($request->getContent(), true);
    $mpesa_transaction_id = $response['TransID'];
    $date_time = Carbon::parse($response['TransTime']);
    $amount = $response['TransAmount'];
    $account = strtoupper(preg_replace('/\s+/', '', $response['BillRefNumber']));
    $merchant_transaction_id = $response['ThirdPartyTransID'];
    $phone = $response['MSISDN'];
    $payer = preg_replace('!\s+!', ' ', ucwords(strtolower($response['FirstName'] . ' ' . $response['MiddleName'] . ' ' . $response['LastName'])));
}
```
 
#### All Done
Feel free to report any issues

