<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Assure-toi d'utiliser le bon namespace pour Calendar
use Google\Client as Google_Client;
use Google\Service\Calendar as Google_Service_Calendar;

$client = new Google_Client();
$client->setAuthConfig(__DIR__ . '/../config/google/credentials.json'); // adapte le chemin si besoin
$client->addScope(Google_Service_Calendar::CALENDAR_EVENTS);
$client->setAccessType('offline'); // pour obtenir un refresh token
$client->setPrompt('select_account consent');

$authUrl = $client->createAuthUrl();
printf("Ouvre ce lien dans ton navigateur :\n%s\n", $authUrl);
print("Colle ici le code de validation Google : ");
$authCode = trim(fgets(STDIN));

$accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

if (isset($accessToken['error'])) {
    throw new Exception("Erreur récupération token: " . $accessToken['error_description']);
}

file_put_contents(__DIR__ . '/../config/google/token.json', json_encode($accessToken));
echo "✅ Token enregistré dans config/google/token.json\n";
