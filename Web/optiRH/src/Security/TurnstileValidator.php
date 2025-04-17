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
        return true;
    }
    
}
