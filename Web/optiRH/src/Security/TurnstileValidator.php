<?php

namespace App\Security;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class TurnstileValidator
{
    private string $secret;
    private HttpClientInterface $httpClient;

    public function __construct(string $secret, HttpClientInterface $httpClient)
    {
        $this->secret = $secret;
        $this->httpClient = $httpClient;
    }

    public function isValid(string $token, ?string $remoteIp = null): bool
    {
        if (empty($token)) {
            return false;
        }
        $postData = [
            'secret' => $this->secret,
            'response' => $token,
        ];

        
        if ($remoteIp) {
            $postData['remoteip'] = $remoteIp;
        }
        
        try {
            $response = $this->httpClient->request('POST', 'https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'body' => $postData,
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ]);
            
            $data = $response->toArray(false);
            
            return $data['success'] ?? false;

        } catch (\Exception $e) {
            return false;
        }
    }
}
