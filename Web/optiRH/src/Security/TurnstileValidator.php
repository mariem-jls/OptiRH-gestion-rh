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

    public function isValid(string $token, string $remoteIp = null): bool
    {
        $response = $this->httpClient->request('POST', 'https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'body' => [
                'secret' => $this->secret,
                'response' => $token,
                'remoteip' => $remoteIp,
            ],
        ]);

        $data = $response->toArray();
        return $data['success'] ?? false;
    }
}
