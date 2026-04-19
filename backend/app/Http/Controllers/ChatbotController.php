<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    public function chat(Request $request) {
        $userMessage = $request->input('message');
        
        $faqPath = storage_path('app/faq.md');
        $faqContent = file_exists($faqPath) ? file_get_contents($faqPath) : 'No FAQ available.';
        
        // Gemini 2.5 Flash has a massive 1 Million Token context window.
        // We can safely send a large chunk of your FAQ!
        $faqContent = mb_substr($faqContent, 0, 500000); 

        $apiKey = env('GEMINI_API_KEY') ?: $_SERVER['GEMINI_API_KEY'] ?? $_ENV['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY');
        
        $apiKey = trim(str_replace('"', '', $apiKey)); // Remove any quotes or spaces
        
        if (!$apiKey || $apiKey === 'YOUR_GEMINI_KEY_HERE') {
            return response()->json([
                'reply' => 'Erreur: La variable d\'environnement GEMINI_API_KEY est introuvable sur ce serveur. (Debug: ' . json_encode(compact('apiKey')) . ')'
            ], 200);
        }

        if (!$userMessage) {
            return response()->json([
                'reply' => 'Veuillez poser une question.'
            ], 400);
        }

        try {
            $systemInstruction = "Tu es l'assistant virtuel officiel de la Banque Populaire Maroc. Utilises ces informations de la FAQ de Chaabi Net pour répondre aux questions des clients de manière très professionnelle, concise, et utile au format Markdown. Si l'information n'est pas dans la FAQ, indique avec politesse que tu ne connais pas la réponse. Voici la FAQ : \n" . $faqContent;

            // Using Google Gemini 2.5 Flash Endpoint
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey, [
                'system_instruction' => [
                    'parts' => [
                        ['text' => $systemInstruction]
                    ]
                ],
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $userMessage]
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Gemini response structure
                $replyText = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Désolé, je n\'ai pas de réponse claire.';
                
                return response()->json([
                    'reply' => $replyText
                ]);
            } else {
                Log::error('Gemini Error: ' . $response->body());
                return response()->json([
                    'reply' => 'Désolé, une erreur technique est survenue avec Google Gemini.'
                ], 200);
            }
        } catch (\Exception $e) {
            Log::error('Gemini Exception: ' . $e->getMessage());
            return response()->json([
                'reply' => 'Désolé, une exception s\'est produite lors de la connexion.'
            ], 200);
        }
    }
}
