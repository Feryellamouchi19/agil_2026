<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ChatController extends AbstractController
{
    private HttpClientInterface $client;
    private string $geminiApiKey;

    public function __construct(HttpClientInterface $client, string $geminiApiKey = '')
    {
        $this->client = $client;
        $this->geminiApiKey = $_ENV['AGIL_GEMINI_API_KEY'] ?? $geminiApiKey;
    }

    #[Route('/api/chat', name: 'api_chat', methods: ['POST'])]
    public function chat(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $message = $data['message'] ?? '';

        if (empty($message)) {
            return new JsonResponse(['reply' => 'Veuillez poser une question.'], 400);
        }

        if (empty($this->geminiApiKey)) {
            return new JsonResponse(['reply' => 'La clé API Gemini n\'est pas configurée.'], 500);
        }

        $systemPrompt = "Tu es l'assistant virtuel d'AGIL, une société tunisienne de distribution de carburants.
        Tu aides les clients avec la file d'attente, les services, les points de fidélité.
        AGIL propose les services suivants : Carte pétrolière, Bon de valeur, Carte Bons.
        Règle de fidélité : Chaque 1 DT de bon valeur traité = 1 point. Atteignez 1 000 points pour gagner un bon de 50 DT. Les points restants sont conservés après chaque récompense. Tes réponses doivent être concises, courtoises et en français. Si tu ne connais pas la réponse ou si ce n'est pas lié à AGIL, dis poliment que tu ne peux pas aider sur ce sujet.";

        try {
            $response = $this->client->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent?key=' . $this->geminiApiKey, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'system_instruction' => [
                        'parts' => [
                            ['text' => $systemPrompt]
                        ]
                    ],
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $message]
                            ]
                        ]
                    ]
                ]
            ]);

            $content = $response->toArray();
            
            if (isset($content['candidates'][0]['content']['parts'][0]['text'])) {
                $reply = $content['candidates'][0]['content']['parts'][0]['text'];
            } else {
                $reply = "Désolé, je n'ai pas compris. Pourriez-vous reformuler ?";
            }

            return new JsonResponse(['reply' => trim($reply)]);

        } catch (\Exception $e) {
            return new JsonResponse(['reply' => "Désolé, une erreur technique est survenue lors de la communication avec l'IA : " . $e->getMessage()], 500);
        }
    }
}
