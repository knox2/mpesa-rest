<?php

namespace Knox\MPESA;


use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\TransferException;
use InvalidArgumentException;
use GuzzleHttp\Client;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Exception;

class MPESA
{
    protected function getAccessToken()
    {
        if(session('mpesa_access_token_expiry')){
            $expiry = Carbon::parse(session('mpesa_access_token_expiry'));
            $now = Carbon::now();
            if($expiry->gt($now)){
                return session('mpesa_access_token');
            }
        }

        $url = config('mpesa.env') == 'live' ? config('mpesa.live_auth') : config('mpesa.sandbox_auth');

        $credentials = base64_encode(config('mpesa.consumer_key').':'.config('mpesa.consumer_secret'));

        $response = $this->makeRequest($url, [], 'GET', $credentials);

        session(['mpesa_access_token' => $response->access_token]);

        $expiry = Carbon::now()->addSeconds(((int) $response->expires_in - 60))->format('Y-m-d H:i:s');  // to renew a minute before expiry

        session(['mpesa_access_token_expiry' => $expiry]);

        return $response->access_token;

    }

    public function b2c($phone, $amount, $command, $remarks = 'No Remarks', $from = ''){

        $allowed = ['SalaryPayment', 'BusinessPayment', 'PromotionPayment'];

        if(!in_array($command, $allowed)){
            throw new InvalidArgumentException("Not a Valid Command, Valid Commands are ".join(", ", $allowed));
        }

        if(!$from){
            $from = config('mpesa.short_code');
        }

        $root = config('mpesa.env') == 'live' ? config('mpesa.live_root_url') : config('mpesa.sandbox_root_url');

        $url = $root.config('mpesa.b2c_url').config('mpesa.version').'/paymentrequest';

        $security_credentials = $this->encryptCredentials(config('mpesa.initiator_password'));

        $json = [
            'InitiatorName' => config('mpesa.initiator_name'),
            'SecurityCredential' => $security_credentials,
            'CommandID' => $command,
            'Amount' => $amount,
            'PartyA' => $from,
            'PartyB' => $phone,
            'Remarks' => $remarks,
            'QueueTimeOutURL' => config('mpesa.b2c_timeout_url'),
            'ResultURL' => config('mpesa.b2c_result_url'),
            'Occassion' => ''
        ];

        return $this->makeRequest($url, $json);

    }

    public function b2b($short_code, $amount, $command, $sender_identifier, $receiver_identifier, $remarks = 'No Remarks', $account = ''){

        $allowed = ['BusinessPayBill', 'BusinessBuyGoods', 'DisburseFundsToBusiness', 'BusinessToBusinessTransfer', 'BusinessTransferFromMMFToUtility', 'BusinessTransferFromUtilityToMMF', 'MerchantToMerchantTransfer', 'MerchantTransferFromMerchantToWorking','MerchantServicesMMFAccountTransfer','AgencyFloatAdvance'];

        if(!in_array($command, $allowed)){
            throw new InvalidArgumentException("Not a Valid Command, Valid Commands are ".join(", ", $allowed));
        }

        if(!in_array($sender_identifier, [1, 2, 4])){
            throw new InvalidArgumentException("Not a Valid Sender Identifier, Valid Commands are ".join(", ", ['1 for MSISDN', '2 for Till Number', '4 for ShortCode']));
        }

        if(!in_array($receiver_identifier, [1, 2, 4])){
            throw new InvalidArgumentException("Not a Valid Receiver Identifier, Valid Commands are ".join(", ", ['1 for MSISDN', '2 for Till Number', '4 for ShortCode']));
        }

        if($command == 'BusinessPayBill'){
            if($account == ''){
                throw new InvalidArgumentException('Account number is required for Paybill payments');
            }
        }

        $root = config('mpesa.env') == 'live' ? config('mpesa.live_root_url') : config('mpesa.sandbox_root_url');

        $url = $root.config('mpesa.b2b_url').config('mpesa.version').'/paymentrequest';

        $security_credentials = $this->encryptCredentials(config('mpesa.initiator_password'));

        $json = [
            'Initiator' => config('mpesa.initiator_name'),
            'SecurityCredential' => $security_credentials,
            'CommandID' => $command,
            'Amount' => $amount,
            'PartyA' => config('mpesa.short_code'),
            'PartyB' => $short_code,
            'SenderIdentifierType' => $sender_identifier,
            'RecieverIdentifierType' => $receiver_identifier,
            'Remarks' => $remarks,
            'AccountReference' => $account,
            'QueueTimeOutURL' => config('mpesa.b2b_timeout_url'),
            'ResultURL' => config('mpesa.b2b_result_url'),
        ];

        return $this->makeRequest($url, $json);

    }

    public function registerC2bUrl(){

        $root = config('mpesa.env') == 'live' ? config('mpesa.live_root_url') : config('mpesa.sandbox_root_url');

        $url = $root.config('mpesa.c2b_url').config('mpesa.version').'/registerurl';

        $json = [
            'ShortCode' => config('mpesa.short_code'),
            'ResponseType' => config('mpesa.response_type'),
            'ConfirmationURL' => config('mpesa.c2b_confirmation_url'),
            'ValidationURL' => config('mpesa.c2b_validation_url')
        ];

        return $this->makeRequest($url, $json);
    }

    public function c2bSimulate($phone, $amount, $command, $account = '', $short_code = ''){

        $allowed = ['CustomerPayBillOnline', 'CustomerBuyGoodsOnline'];

        if(!in_array($command, $allowed)){
            throw new InvalidArgumentException("Not a Valid Command, Valid Commands are ".join(", ", $allowed));
        }

        $root = config('mpesa.env') == 'live' ? config('mpesa.live_root_url') : config('mpesa.sandbox_root_url');

        $url = $root.config('mpesa.c2b_url').config('mpesa.version').'/simulate';

        if(!$short_code){
            $short_code = config('mpesa.short_code');
        }

        $json = [
            'ShortCode' => $short_code,
            'CommandID' => $command,
            'Amount' => $amount,
            'Msisdn' => $phone,
        ];

        if($command == 'CustomerPayBillOnline'){
            $json['BillRefNumber'] = $account;
        }

        return $this->makeRequest($url, $json);
    }

    public function getAccountBalance($remarks = 'No Remarks'){

        $root = config('mpesa.env') == 'live' ? config('mpesa.live_root_url') : config('mpesa.sandbox_root_url');

        $url = $root.'/accountbalance/'.config('mpesa.version').'/query';

        $security_credentials = $this->encryptCredentials(config('mpesa.initiator_password'));

        $json = [
            'Initiator' => config('mpesa.initiator_name'),
            'SecurityCredential' => $security_credentials,
            'CommandID' => 'AccountBalance',
            'PartyA' => config('mpesa.short_code'),
            'IdentifierType' => '4',
            'Remarks' => $remarks,
            'QueueTimeOutURL' => config('mpesa.account_balance_timeout_url'),
            'ResultURL' => config('mpesa.account_balance_result_url')
        ];

        return $this->makeRequest($url, $json);

    }

    public function doReversal($transaction, $amount, $receiver, $receiver_identifier, $remarks = 'No Remarks', $occassion = 'No Occassion'){

        if(!in_array($receiver_identifier, [1, 2, 4])){
            throw new InvalidArgumentException("Not a Valid Receiver Identifier, Valid Commands are ".join(", ", ['1 for MSISDN', '2 for Till Number', '4 for ShortCode']));
        }

        $root = config('mpesa.env') == 'live' ? config('mpesa.live_root_url') : config('mpesa.sandbox_root_url');

        $url = $root.'/reversal/'.config('mpesa.version').'/request';

        $security_credentials = $this->encryptCredentials(config('mpesa.initiator_password'));

        $json = [
            'Initiator' => config('mpesa.initiator_name'),
            'SecurityCredential' => $security_credentials,
            'CommandID' => 'TransactionReversal',
            'TransactionID' => $transaction,
            'Amount' => $amount,
            'ReceiverParty' => $receiver,
            'RecieverIdentifierType' => $receiver_identifier,
            'QueueTimeOutURL' => config('mpesa.reversal_timeout_url'),
            'ResultURL' => config('mpesa.reversal_result_url'),
            'Remarks' => $remarks,
            'Occasion' => $occassion
        ];

        return $this->makeRequest($url, $json);

    }

    public function stkPush($phone, $amount, $account = '', $description = 'No Description'){

        $root = config('mpesa.env') == 'live' ? config('mpesa.live_root_url') : config('mpesa.sandbox_root_url');

        $url = $root.'/stkpush/'.config('mpesa.version').'/processrequest';

        $timestamp = date('YmdHis');

        $password = base64_encode(config('mpesa.short_code').config('mpesa.passkey').$timestamp);

        $json = [
            'BusinessShortCode' => config('mpesa.short_code'),
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'PartyA' => $phone,
            'PartyB' => config('mpesa.short_code'),
            'PhoneNumber' => $phone,
            'CallBackURL' => config('mpesa.stk_callback_url'),
            'AccountReference' => $account,
            'TransactionDesc' => $description
        ];


        return $this->makeRequest($url, $json);
    }

    public function stkQuery($checkout_request_id){

        $root = config('mpesa.env') == 'live' ? config('mpesa.live_root_url') : config('mpesa.sandbox_root_url');

        $url = $root.'/stkpushquery/'.config('mpesa.version').'/query';

        $timestamp = date('YmdHis');

        $password = base64_encode(config('mpesa.short_code').config('mpesa.passkey').$timestamp);

        $json = [
            'BusinessShortCode' => config('mpesa.short_code'),
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkout_request_id
        ];

        return $this->makeRequest($url, $json);
    }

    public function getTransactionStatus($transaction, $party = '', $identifier_type, $remarks = 'No Remarks', $occassion = ''){

        if(!in_array($identifier_type, [1, 2, 4])){
            throw new InvalidArgumentException("Not a Valid Receiver Identifier, Valid Commands are ".join(", ", ['1 for MSISDN', '2 for Till Number', '4 for ShortCode']));
        }

        $root = config('mpesa.env') == 'live' ? config('mpesa.live_root_url') : config('mpesa.sandbox_root_url');

        $url = $root.'/transactionstatus/'.config('mpesa.version').'/query';

        $security_credentials = $this->encryptCredentials(config('mpesa.initiator_password'));

        if(!$party){
            $party = config('mpesa.short_code');
        }

        $json = [
            'Initiator' => config('mpesa.initiator_name'),
            'SecurityCredential' => $security_credentials,
            'CommandID' => 'TransactionStatusQuery',
            'TransactionID' => $transaction,
            'PartyA' => $party,
            'IdentifierType' => $identifier_type,
            'ResultURL' => config('mpesa.transaction_status_timeout_url'),
            'QueueTimeOutURL' => config('mpesa.transaction_status_result_url'),
            'Remarks' => $remarks,
            'Occasion' => $occassion
        ];

        return $this->makeRequest($url, $json);
    }

    public function checkIdentity($phone, $description = 'No Description'){

        $root = config('mpesa.env') == 'live' ? config('mpesa.live_root_url') : config('mpesa.sandbox_root_url');

        $url = $root.'/checkidentity/'.config('mpesa.version').'/query';

        $json = [
            'Initiator' => config('mpesa.initiator_name'),
            'BusinessShortCode' => config('mpesa.short_code'),
            'Password' => config('mpesa.passkey'),
            'Timestamp' => date('YmdHis'),
            'TransactionType' => 'CheckIdentity',
            'PhoneNumber' => $phone,
            'CallBackURL' => config('mpesa.identity_callback_url'),
            'TransactionDesc' => $description
        ];

        return $this->makeRequest($url, $json);

    }


    public function CreditScoreOnboarding($partner, $credit_score_type){

        $allowed = ['Customer', 'Merchant'];

        if(!in_array($credit_score_type, $allowed)){
            throw new InvalidArgumentException("Not a Valid Credit Score Type, Valid Types are ".join(", ", $allowed));
        }

        $root = config('mpesa.env') == 'live' ? config('mpesa.live_root_url') : config('mpesa.sandbox_root_url');

        $url = $root.'/creditScore/'.config('mpesa.version').'/subscribe';

        $json = [
            'PartnerName' => $partner,
            'CreditScoreType' => $credit_score_type
        ];

        return $this->makeRequest($url, $json);
    }

    public function CreditScoreSubscribe($phone, $request_type, $document_type, $document_number){

        $allowed = ['National ID', 'Passport', 'Alien ID', 'Military ID'];

        if(!in_array($document_type, $allowed)){
            throw new InvalidArgumentException("Not a Valid Document Type, Valid Types are ".join(", ", $allowed));
        }

        $allowed = ['NEW', 'DELETE'];

        if(!in_array($request_type, $allowed)){
            throw new InvalidArgumentException("Not a Valid Request Type, Valid Types are ".join(", ", $allowed));
        }

        $root = config('mpesa.env') == 'live' ? config('mpesa.live_root_url') : config('mpesa.sandbox_root_url');

        $url = $root.'/creditScore/'.config('mpesa.version').'/subscribe';

        $json = [
            'RequestType' => $request_type,
            'MSISDN' => $phone,
            'PartnerID' => config('mpesa.partner_id'),
            'PartnerPassword' => config('mpesa.partner_password'),
            'DocumentType' => $document_type,
            'DocumentNumber' => $document_number,
            'DateOfSubscription' => date('YmdHis')
        ];

        return $this->makeRequest($url, $json);
    }

    public function getCustomerCreditScore($phone){

        $root = config('mpesa.env') == 'live' ? config('mpesa.live_root_url') : config('mpesa.sandbox_root_url');

        $url = $root.'/creditScore/'.config('mpesa.version').'/customer';

        $json = [
            'MSISDN' => $phone,
            'ProviderID' => config('mpesa.partner_id'),
            'ProviderPassword' => config('mpesa.partner_password')
        ];

        return $this->makeRequest($url, $json);

    }

    public function getMerchantCreditScore($merchant){

        $root = config('mpesa.env') == 'live' ? config('mpesa.live_root_url') : config('mpesa.sandbox_root_url');

        $url = $root.'/creditScore/'.config('mpesa.version').'/merchant';

        $json = [
            'MerchantCode' => $merchant,
            'ProviderID' => config('mpesa.partner_id'),
            'ProviderPassword' => config('mpesa.partner_password')
        ];

        return $this->makeRequest($url, $json);

    }


    protected function makeRequest($url, $json, $method = 'POST', $credentials = ''){

        if(!$credentials){
            $expiry = Carbon::parse(session('mpesa_access_token_expiry'));
            $now = Carbon::now();
            if($now->gte($expiry)){
                $credentials = $this->getAccessToken();
            }
            else{
                $credentials = session('mpesa_access_token');
            }
        }

        $client = new Client();

        $auth_type = 'Bearer ';

        if($method == 'GET'){
            $auth_type = 'Basic ';
        }

        $data = [
            'headers' => [
                'Authorization' => $auth_type.$credentials
            ]
        ];

        if(sizeof($json) > 0){
            $data['json'] = $json;
        }

        $response = null;

        try {

            $response = $client->request(
                $method,
                $url,
                $data
            );
        }
        catch(ClientException $e){
            //throw new HttpException($e->getCode(), $e->getResponse()->getBody());
            return json_decode($e->getResponse()->getBody());
        }
        catch(ServerException $e){
            //throw new HttpException($e->getCode(), $e->getResponse()->getBody());
            return json_decode($e->getResponse()->getBody());
        }
        catch(TransferException $e){
            //throw new Exception($e->getResponse()->getBody());
            return json_decode($e->getResponse()->getBody());
        }

        $this->checkErrorCode($response->getStatusCode());

        //$headers = $response->getHeaders();

        $body = json_decode($response->getBody()->getContents());

        return $body;

    }

    protected function encryptCredentials($source){

        $fp = fopen(config('mpesa.cert_path'),"r");

        $cert_data = fread($fp,8192);

        fclose($fp);

        $cert = openssl_x509_read($cert_data);

        $pub_key = openssl_get_publickey($cert);

        openssl_public_encrypt($source,$crypt_text, $pub_key,OPENSSL_PKCS1_PADDING );

        return base64_encode($crypt_text);
    }

    protected function checkErrorCode($code){

        if($code == 200){
            return;
        }

        $errors = [
            '400' => 'Bad Request',
            '401' => 'Unauthorized',
            '403' => 'Forbidden',
            '404' => 'Not Found',
            '405' => 'Method Not Allowed',
            '406' => 'Not Acceptable – You requested a format that isn’t json',
            '429' => 'Too Many Requests – You’re requesting too many kittens! Slow down!',
            '500' => 'Internal Server Error – We had a problem with our server. Try again later.',
            '503' => 'Service Unavailable – We’re temporarily offline for maintenance. Please try again later.'
        ];

        if(!array_key_exists($code, $errors)){

        }
        else{
            throw new HttpException($code, $errors[$code]);
        }
    }

}