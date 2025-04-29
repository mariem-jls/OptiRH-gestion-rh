<?php

namespace App\Service;

use Twilio\Rest\Client;

class TwilioService
{
    private Client $twilioClient;
    private string $twilioPhoneNumber;

    public function __construct(
        string $accountSid,
        string $authToken,
        string $phoneNumber,
        string $sslCaPath
    ) {
        $httpClient = new \Twilio\Http\CurlClient([
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CAINFO => $sslCaPath
        ]);
    
        $this->twilioClient = new Client($accountSid, $authToken, null, null, $httpClient);
        $this->twilioPhoneNumber = $phoneNumber;
    }
    public function sendSms(string $to, string $message): string
    {
        $message = $this->twilioClient->messages->create(
            $to, 
            [
                'from' => $this->twilioPhoneNumber,
                'body' => $message
            ]
        );

        return $message->sid;
    }

}