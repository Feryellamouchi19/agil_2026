<?php

namespace App\Service;

use App\Entity\Ticket;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class SmsService
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private string $accountSid;
    private string $authToken;
    private string $fromNumber;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        string $twilioAccountSid = '',
        string $twilioAuthToken = '',
        string $twilioFromNumber = ''
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->accountSid = $_ENV['TWILIO_ACCOUNT_SID'] ?? $twilioAccountSid;
        $this->authToken = $_ENV['TWILIO_AUTH_TOKEN'] ?? $twilioAuthToken;
        $this->fromNumber = $_ENV['TWILIO_FROM_NUMBER'] ?? $twilioFromNumber;
    }

    public function sendTicketSms(Ticket $ticket): bool
    {
        $client = $ticket->getClient();
        if (!$client || !$client->getTelephone()) {
            $this->logger->warning('Impossible d\'envoyer le SMS : Le client n\'a pas de numéro de téléphone.');
            return false;
        }

        $to = $client->getTelephone();
        $numeroTicket = $ticket->getNumeroTicket();
        $tempsEstime = $ticket->getTempsEstime();

        $message = sprintf(
            "AGIL : Bonjour %s, votre ticket est le %s. Temps d'attente estimé : %d min. Merci de patienter.",
            $client->getPrenom(),
            $numeroTicket,
            $tempsEstime
        );

        return $this->sendSms($to, $message);
    }

    private function sendSms(string $to, string $message): bool
    {
        if (empty($this->accountSid) || empty($this->authToken) || empty($this->fromNumber)) {
            $this->logger->error('Impossible d\'envoyer le SMS : Les identifiants Twilio ne sont pas configurés.');
            return false;
        }

        // Twilio expects the 'To' number to be in E.164 format (e.g. +216XXXXXXXX). 
        // We assume it's correctly formatted or we can force add '+' if missing, but it's better to let user input handle it.

        $url = sprintf('https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json', $this->accountSid);

        try {
            $response = $this->httpClient->request('POST', $url, [
                'auth_basic' => [$this->accountSid, $this->authToken],
                'body' => [
                    'From' => $this->fromNumber,
                    'To' => $to,
                    'Body' => $message,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode >= 200 && $statusCode < 300) {
                $this->logger->info('SMS envoyé avec succès à ' . $to);
                return true;
            }

            $this->logger->error('Erreur API Twilio : ' . $response->getContent(false));
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Exception lors de l\'envoi du SMS : ' . $e->getMessage());
            return false;
        }
    }
}
