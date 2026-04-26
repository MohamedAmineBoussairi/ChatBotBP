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
        
        // Ensure string is incredibly clean UTF-8 to avoid json_encode throwing exceptions.
        // It removes null bytes and strictly converts it.
        $faqContent = mb_convert_encoding($faqContent, 'UTF-8', 'auto');
        $faqContent = iconv('UTF-8', 'UTF-8//IGNORE', $faqContent);
        
        // Gemini 2.5 Flash has a massive 1 Million Token context window.
        // We might want to limit this more for GPT-4o which usually has 128k limit, 
        // 100k chars is well within safe limits.
        $faqContent = mb_substr($faqContent, 0, 100000, 'UTF-8'); 

        $apiKey = env('GITHUB_TOKEN') ?: $_SERVER['GITHUB_TOKEN'] ?? $_ENV['GITHUB_TOKEN'] ?? getenv('GITHUB_TOKEN');
        
        $apiKey = trim(str_replace('"', '', $apiKey)); // Remove any quotes or spaces
        
        if (!$apiKey || $apiKey === 'YOUR_GITHUB_TOKEN_HERE') {
            return response()->json([
                'reply' => 'Erreur: La variable d\'environnement GITHUB_TOKEN est introuvable sur ce serveur. (Debug: ' . json_encode(compact('apiKey')) . ')'
            ], 200);
        }

        if (!$userMessage) {
            return response()->json([
                'reply' => 'Veuillez poser une question.'
            ], 400);
        }

        try {
            $systemInstruction = "Tu es l'assistant virtuel intelligent et officiel de la Banque Populaire Maroc. Tu es très intelligent et capable de converser de manière fluide, naturelle et courtoise avec les utilisateurs (comme répondre aux salutations, etc.) comme un humain normal. Pour toute question concernant la banque ou Chaabi Net, tu dois te baser en priorité sur les informations de la FAQ ci-dessous pour répondre de manière très professionnelle et utile, au format Markdown. Si une question n'est pas dans la FAQ, utilise tes connaissances générales pour aider le client de la meilleure façon possible tout en gardant ton rôle d'assistant bancaire. Voici la FAQ :\n" . $faqContent;

            // Using GitHub Models API Endpoint for GPT-4o
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $apiKey,
            ])->timeout(60)->post('https://models.inference.ai.azure.com/chat/completions', [
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemInstruction
                    ],
                    [
                        'role' => 'user',
                        'content' => $userMessage
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 2000
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // OpenAI API response structure
                $replyText = $data['choices'][0]['message']['content'] ?? 'Désolé, je n\'ai pas de réponse claire.';
                
                return response()->json([
                    'reply' => $replyText
                ]);
            } else {
                Log::error('GitHub Models API Error: ' . $response->body());
                return response()->json([
                    'reply' => 'Désolé, une erreur technique est survenue avec le modèle IA.'
                ], 200);
            }
        } catch (\Exception $e) {
            Log::error('GitHub Models API Exception: ' . $e->getMessage());
            return response()->json([
                'reply' => 'Désolé, une exception s\'est produite lors de la connexion. DEBUG: ' . $e->getMessage()
            ], 200);
        }
    }
}
