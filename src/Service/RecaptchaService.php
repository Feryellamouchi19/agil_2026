<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class RecaptchaService
{
    private string $secretKey;
    private HttpClientInterface $httpClient;

    public function __construct(string $secretKey, HttpClientInterface $httpClient)
    {
        $this->secretKey = $secretKey;
        $this->httpClient = $httpClient;
    }

    /**
     * Vérifie la réponse reCAPTCHA v2 auprès de Google.
     */
    public function verify(?string $recaptchaResponse): bool
    {
        if (empty($recaptchaResponse)) {
            return false;
        }

        $response = $this->httpClient->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret' => $this->secretKey,
                'response' => $recaptchaResponse,
            ],
        ]);

        $data = $response->toArray(false);

        return isset($data['success']) && $data['success'] === true;
    }
}
