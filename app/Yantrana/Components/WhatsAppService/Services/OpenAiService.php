<?php

/**
 * WhatsJet
 *
 * This file is part of the WhatsJet software package developed and licensed by livelyworks.
 *
 * You must have a valid license to use this software.
 *
 * © 2024 - 2026 livelyworks. All rights reserved.
 * Redistribution or resale of this file, in whole or in part, is prohibited without prior written permission from the author.
 *
 * For support or inquiries, contact: contact@livelyworks.net
 *
 * @package     WhatsJet
 * @author      livelyworks <contact@livelyworks.net>
 * @copyright   Copyright (c) 2024 - 2026 livelyworks
 * @website     https://livelyworks.net
 */

/**
 * OpenAiService.php -
 *
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\WhatsAppService\Services;

// use OpenAI;
use Exception;
use OpenAI\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Yantrana\Base\BaseEngine;
use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;
use App\Yantrana\Components\WhatsAppService\Models\WhatsAppMessageLogModel;
use App\Yantrana\Components\Contact\Models\ContactModel;
use App\Yantrana\Components\Contact\Repositories\ContactRepository;

class OpenAiService extends BaseEngine
{
    protected function initConfiguration($vendorId = null, $accessKey = null, $orgKey = null)
    {
        if (!$vendorId) {
            $vendorId = getVendorId();
        }
        
        $vendor = \App\Yantrana\Components\Vendor\Models\VendorModel::find($vendorId);
        $totalCredits = ($vendor->plan_ai_credits ?? 0) + ($vendor->extra_ai_credits ?? 0);
        if ($totalCredits <= 0) {
            throw new Exception("Insufficient AI Credits");
        }

        $vendorKey = getVendorSettings('open_ai_access_key', null, null, $vendorId);
        if ($vendorKey && !Str::startsWith($vendorKey, 'sk-')) {
            $vendorKey = null; // Ignore invalid encrypted keys
        }
        
        $vendorGeminiKey = getVendorSettings('gemini_access_key', null, null, $vendorId);
        $apiKey = $accessKey ?: $vendorKey;

        $geminiApiKey = $vendorGeminiKey
                     ?: getAppSettings('gemini_api_key')
                     ?: env('GEMINI_API_KEY');

        if (!$apiKey) {
            $apiKey = getAppSettings('openai_api_key') ?: env('OPENAI_API_KEY');
            if (!$apiKey && !$geminiApiKey) {
                throw new \Exception(__tr("Veuillez configurer une clé API IA (Google Gemini ou OpenAI) dans les paramètres."));
            }
            if (!$apiKey) {
                $apiKey = 'gemini-mode-placeholder';
            }
        }
        
        $vendorOrg = getVendorSettings('open_ai_organization_id', null, null, $vendorId);
        if ($vendorOrg && !Str::startsWith($vendorOrg, 'org-')) {
            $vendorOrg = null;
        }

        $orgId = $orgKey 
            ?: $vendorOrg
            ?: getAppSettings('openai_organization_id') 
            ?: env('OPENAI_ORGANIZATION');

        config([
            'openai.api_key' => $apiKey,
            'openai.organization' => $orgId,
        ]);
    }
    /**
     * Generate embeddings for large data and store it in the database.
     */
    public function embedLargeData($largeData)
    {
        $this->initConfiguration(null);
        // Step 1: Split the large data into meaningful chunks
        $sections = $this->splitDataIntoChunks($largeData);

        // Step 2: Generate embeddings for each section
        $embeddings = [];
        foreach ($sections as $section) {
            $response = OpenAI::embeddings()->create([
                'model' => 'text-embedding-3-small',
                'input' => $section,
            ]);

            $embeddings[] = $response['data'][0]['embedding'];
        }

        // Step 3: Store the data and embeddings in the database
        return [
            'data' => $sections,
            'embedding' => $embeddings,
        ];
    }

    /**
     * Split the large dataset into smaller meaningful chunks.
     */
    private function splitDataIntoChunks($data, $maxChunkSize = 500)
    {
        $chunks = [];
        $currentChunk = '';
        $sentences = preg_split('/(?<=[.?!])\s+/', $data);  // Split by sentences

        foreach ($sentences as $sentence) {
            if (strlen($currentChunk . ' ' . $sentence) > $maxChunkSize) {
                $chunks[] = trim($currentChunk);
                $currentChunk = $sentence;
            } else {
                $currentChunk .= ' ' . $sentence;
            }
        }

        if (!empty($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }

        return $chunks;
    }

    /**
     * Embed the user's question.
     */
    private function embedQuestion($question)
    {
        $response = OpenAI::embeddings()->create([
            'model' => 'text-embedding-3-small',
            'input' => $question,
        ]);

        return $response['data'][0]['embedding'];
    }

    /**
     * Calculate cosine similarity between two vectors.
     */
    private function cosineSimilarity($vecA, $vecB)
    {
        $dotProduct = array_sum(array_map(function ($a, $b) {
            return $a * $b;
        }, $vecA, $vecB));

        $magnitudeA = sqrt(array_sum(array_map(function ($a) {
            return $a ** 2;
        }, $vecA)));

        $magnitudeB = sqrt(array_sum(array_map(function ($b) {
            return $b ** 2;
        }, $vecB)));

        return $dotProduct / ($magnitudeA * $magnitudeB);
    }

    /**
     * Find the most relevant section based on the user's question.
     */
    private function findRelevantSection($question, $vendorId)
    {
        $this->initConfiguration($vendorId);
        // Step 1: Embed the question
        $questionEmbedding = $this->embedQuestion($question);

        // Step 2: Fetch the large dataset and embeddings from the database
        // $largeDataRecord = LargeData::first();
        // $sections = preg_split('/\n\n+/', $largeDataRecord->data);
        // $storedEmbeddings = json_decode($largeDataRecord->embedding);
        $largeDataRecord = getVendorSettings('open_ai_embedded_training_data', null, null, $vendorId);
        $sections = $largeDataRecord['data']; //preg_split('/\n\n+/', $largeDataRecord['data']);  // Ensure you split the data in the same way
        $storedEmbeddings = ($largeDataRecord['embedding']);

        // Step 3: Compare the embeddings
        $similarities = [];
        foreach ($storedEmbeddings as $index => $sectionEmbedding) {
            $similarity = $this->cosineSimilarity($questionEmbedding, $sectionEmbedding);
            $similarities[] = [
                'section' => $sections[$index],
                'similarity' => $similarity,
            ];
        }

        // Step 4: Sort by similarity and return the top section
        usort($similarities, function ($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        return $similarities[0]['section'];
    }

    /**
     * Find the top N relevant sections for broader context.
     */
    private function findTopRelevantSections($question, $vendorId, $topN = 3)
    {
        $this->initConfiguration($vendorId);
        $questionEmbedding = $this->embedQuestion($question);
        // $largeDataRecord = LargeData::first();
        // $sections = preg_split('/\n\n+/', $largeDataRecord->data);
        // $storedEmbeddings = json_decode($largeDataRecord->embedding);
        $largeDataRecord = getVendorSettings('open_ai_embedded_training_data', null, null, $vendorId);
        if (empty($largeDataRecord) || !is_array($largeDataRecord) || empty($largeDataRecord['data'])) {
            return [];
        }
        $sections = $largeDataRecord['data']; //preg_split('/\n\n+/', $largeDataRecord['data']);  // Ensure you split the data in the same way
        $storedEmbeddings = ($largeDataRecord['embedding']);
        $similarities = [];
        foreach ($storedEmbeddings as $index => $sectionEmbedding) {
            $similarity = $this->cosineSimilarity($questionEmbedding, $sectionEmbedding);
            $similarities[] = [
                'section' => $sections[$index],
                'similarity' => $similarity,
            ];
        }

        usort($similarities, function ($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        return array_slice($similarities, 0, $topN);
    }

    /**
     * Generate an answer using the most relevant section.
     */
    public function generateAnswerFromSingleSection($question, $vendorId)
    {
        // Step 1: Find the most relevant section
        $relevantSection = $this->findRelevantSection($question, $vendorId);
        $botName  = getVendorSettings('open_ai_bot_name', null, null, $vendorId);
        // Step 2: Use OpenAI completion API to generate a refined answer
        $response = OpenAI::completions()->create([
            'model' => getVendorSettings('open_ai_model_key', null, null, $vendorId) ?: 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "You are a helpful assistant that generates well-formatted answers." . ($botName ? ' your name is ' . $botName : ''),
                ],
                [
                    'role' => 'user',
                    'content' => "Based on the following content, answer the question in a well-formatted, structured way with appropriate new lines and paragraphs:\n\nContent: {$relevantSection}\n\nQuestion: {$question}",
                ]
            ],
            'max_tokens' => getVendorSettings('open_ai_max_token', null, null, $vendorId),
        ]);

        $promptTokens = $response['usage']['prompt_tokens'] ?? 0;
        $completionTokens = $response['usage']['completion_tokens'] ?? 0;
        $model = getVendorSettings('open_ai_model_key', null, null, $vendorId) ?: 'gpt-3.5-turbo';
        $credits = $this->calculateCredits($model, $promptTokens, $completionTokens);
        $this->deductVendorCredit($vendorId, $credits);

        return trim($response['choices'][0]['text']);
    }

    /**
     * Generate an answer by combining multiple relevant sections for broader context.
     */
    public function generateAnswerFromMultipleSections($question, $contact, $vendorId)
    {
        $contactUid = $contact->_uid;
        $botName = getVendorSettings('open_ai_bot_name', null, null, $vendorId);
        $botDataSourceType = getVendorSettings('open_ai_bot_data_source_type', null, null, $vendorId);
        $useExistingChatHistory = getVendorSettings('use_existing_chat_history', null, null, $vendorId);

        $productContext = '';
        if (vendorPlanDetails('ecommerce_catalog', 1, $vendorId)['is_limit_available']) {
            $products = \App\Yantrana\Components\ECommerce\Models\ProductModel::where('vendors__id', $vendorId)->get();
            if ($products->isNotEmpty()) {
                $productContext = "\n\nHere is our product catalog. When a customer asks about a product, pricing, or images, describe the product warmly, present its price in CFA, and ALWAYS end your message with a order button in this format: [BUTTON: 🛍️ Commander]\n\nCatalog Products:\n";
                foreach ($products as $prod) {
                    $link = !empty($prod->direct_link) ? " | [URL_BUTTON: Voir le site: {$prod->direct_link}]" : "";
                    $productContext .= "- Name: {$prod->name}, Price: " . number_format($prod->price, 0, ',', ' ') . " CFA, Description: {$prod->description}{$link}\n";
                }
            }
        }

        $interactiveInstructions = "\n\n" .
            "IMPORTANT ORDERING & FORMATTING RULES:\n" .
            "1. Do NOT say 'I cannot send images' or 'I don't have images'. The system automatically attaches product cards and images to the message.\n" .
            "2. To offer interactive buttons to the customer, append them at the VERY END of your message on a separate line in this exact format: [BUTTON: 🛍️ Commander]\n" .
            "3. Keep button text under 20 characters (e.g., [BUTTON: 🛍️ Commander], [BUTTON: En savoir plus]). Never leave raw brackets in sentences.\n" .
            "4. WHEN COLLECTING ORDER/DELIVERY DETAILS: Ask ONLY for (1) Nom complet, (2) Adresse/Lieu de livraison, (3) Téléphone, et (4) Date de livraison souhaitée. DO NOT ASK FOR THE DELIVERY TIME (NE DEMANDE JAMAIS L'HEURE DE LIVRAISON). The date alone is sufficient to validate the order.";

        $assistantId = getVendorSettings('open_ai_assistant_id', null, null, $vendorId);
        if ($botDataSourceType == 'assistant' && (!$assistantId || !Str::startsWith($assistantId, 'asst_'))) {
            $botDataSourceType = 'text';
        }

        if ($botDataSourceType == 'assistant') {
            $this->initConfiguration($vendorId);

            $messages = [
                [
                    'role' => 'assistant',
                    'content' => "You are a helpful assistant " . ($botName ? ' your name is ' . $botName . ' and don"t include your name in reply.' : '') . " a well-formatted, structured way with appropriate new lines and paragraphs. Strictly do not answer out of given context, your answer should be based on the given context and content. You are talking with " . ($contact->full_name ?: '') . $productContext . $interactiveInstructions,
                ]
            ];

            // Check if use existing chat history to message smartly
            if ($useExistingChatHistory) {
                $existingHistoryData = array_filter(
                    $this->getExistingChatHistory($contactUid),
                    fn($msg) => !empty(trim($msg['content'] ?? ''))
                );
                $messages = array_merge($messages, array_slice($existingHistoryData, 0, 30));
            }

            $messages[] = [
                'role' => 'user',
                'content' => $question
            ];
            $threadRun = $response = OpenAI::threads()->createAndRun([
                'model' => getVendorSettings('open_ai_model_key', null, null, $vendorId) ?: 'gpt-3.5-turbo',
                'assistant_id' => getVendorSettings('open_ai_assistant_id', null, null, $vendorId),
                'thread' => [
                    'messages' => $messages,
                ],
            ]);
            while (in_array($threadRun->status, ['queued', 'in_progress'])) {
                $threadRun = OpenAI::threads()->runs()->retrieve(
                    threadId: $threadRun->threadId,
                    runId: $threadRun->id,
                );
            }
            if ($threadRun->status !== 'completed') {
                return getVendorSettings('open_ai_failed_message', null, null, $vendorId) ?: 'Request failed, please try again';
            }
            $messageList = OpenAI::threads()->messages()->list(
                threadId: $threadRun->threadId,
            );
            $promptTokens = 0;
            $completionTokens = 0;
            if (isset($threadRun->usage)) {
                if (is_array($threadRun->usage)) {
                    $promptTokens = $threadRun->usage['prompt_tokens'] ?? 0;
                    $completionTokens = $threadRun->usage['completion_tokens'] ?? 0;
                } else {
                    $promptTokens = $threadRun->usage->promptTokens ?? $threadRun->usage->prompt_tokens ?? 0;
                    $completionTokens = $threadRun->usage->completionTokens ?? $threadRun->usage->completion_tokens ?? 0;
                }
            }
            $model = getVendorSettings('open_ai_model_key', null, null, $vendorId) ?: 'gpt-3.5-turbo';
            $credits = $this->calculateCredits($model, $promptTokens, $completionTokens);
            $this->deductVendorCredit($vendorId, $credits);
            return $messageList->data[0]->content[0]->text->value;
        }
        // Initialize configuration (credit check + API key setup)
        $this->initConfiguration($vendorId);

        // Text Based Source type
        $rawTrainingData = getVendorSettings('open_ai_input_training_data', null, null, $vendorId);
        \Illuminate\Support\Facades\Log::info('[AI-BOT-DEBUG] vendorId=' . $vendorId . ', rawTrainingData=' . (empty($rawTrainingData) ? 'EMPTY' : 'SET (' . strlen($rawTrainingData) . ' chars)') . ', botDataSourceType=' . $botDataSourceType);
        
        if (!empty($rawTrainingData) && strlen($rawTrainingData) < 20000) {
            $contextText = $rawTrainingData;
        } else {
            if (empty($rawTrainingData)) {
                \Illuminate\Support\Facades\Log::warning('[AI-BOT-DEBUG] Training data is EMPTY for vendor ' . $vendorId . '. AI may not have context to answer.');
                $contextText = 'No training data available. Answer based on general knowledge.';
            } else {
                // Fallback to top relevant sections if too large
                $topSections = $this->findTopRelevantSections($question, $vendorId);
                $contextText = implode("\n\n", array_column($topSections, 'section'));
            }
        }

        $systemPrompt = "You are a helpful and smart AI assistant. "
            . ($botName ? "Your name is " . $botName . ". " : "")
            . "You are having a conversation with " . ($contact->full_name ?: 'a customer') . ". "
            . "Please answer their questions based on the following business information and guidelines. "
            . "If their question is a general greeting or unrelated to the business, answer politely and guide them back to the business. "
            . "Format your response in a well-structured way with appropriate new lines and paragraphs.\n\n"
            . "Business Information & Guidelines:\n" . $contextText
            . $productContext
            . $interactiveInstructions;

        $messages = [
            [
                'role' => 'system',
                'content' => $systemPrompt,
            ]
        ];

        // Check if use existing chat history to message smartly
        if ($useExistingChatHistory) {
            $existingHistoryData = $this->getExistingChatHistory($contactUid);
            $messages = array_merge($messages, $existingHistoryData);
        }

        $messages[] = [
            'role' => 'user',
            'content' => $question
        ];

        // Check if Google Gemini AI is requested or configured
        $aiProvider = getAppSettings('ai_provider', 'gemini');
        $geminiApiKey = getVendorSettings('gemini_access_key', null, null, $vendorId)
                     ?: getAppSettings('gemini_api_key')
                     ?: env('GEMINI_API_KEY');

        \Illuminate\Support\Facades\Log::info('[AI-BOT-DEBUG] aiProvider=' . $aiProvider . ', geminiApiKey=' . (empty($geminiApiKey) ? 'EMPTY' : 'SET (' . substr($geminiApiKey, 0, 10) . '...)') . ', openaiApiKey=' . (empty(getAppSettings('openai_api_key')) ? 'EMPTY' : 'SET'));

        if ($aiProvider === 'gemini' || (!empty($geminiApiKey) && empty(getAppSettings('openai_api_key')))) {
            \Illuminate\Support\Facades\Log::info('[AI-BOT-DEBUG] Calling Google Gemini for vendor ' . $vendorId);
            try {
                $geminiResult = $this->generateGeminiResponse($systemPrompt, $messages, $question, $vendorId, $geminiApiKey);
                \Illuminate\Support\Facades\Log::info('[AI-BOT-DEBUG] Gemini result: ' . json_encode($geminiResult ? array_keys($geminiResult) : 'NULL'));
                if (!empty($geminiResult['text'])) {
                    $promptTokens = $geminiResult['prompt_tokens'] ?? 0;
                    $completionTokens = $geminiResult['completion_tokens'] ?? 0;
                    $credits = $this->calculateCredits('gemini-1.5-flash', $promptTokens, $completionTokens);
                    $this->deductVendorCredit($vendorId, $credits);
                    \Illuminate\Support\Facades\Log::info('[AI-BOT-DEBUG] Gemini replied successfully: ' . substr($geminiResult['text'], 0, 100));
                    return $geminiResult['text'];
                } else {
                    \Illuminate\Support\Facades\Log::warning('[AI-BOT-DEBUG] Gemini returned empty result for vendor ' . $vendorId);
                }
            } catch (\Throwable $th) {
                \Illuminate\Support\Facades\Log::error('[AI-BOT-DEBUG] Google Gemini AI Error: ' . $th->getMessage() . ' | File: ' . $th->getFile() . ':' . $th->getLine());
            }
        } else {
            \Illuminate\Support\Facades\Log::info('[AI-BOT-DEBUG] Skipped Gemini, falling through to OpenAI for vendor ' . $vendorId);
        }

        // Step 2: Use OpenAI completion API as fallback or primary
        try {
            $response = OpenAI::chat()->create([
                'model' => getVendorSettings('open_ai_model_key', null, null, $vendorId) ?: 'gpt-3.5-turbo',
                'max_tokens' => getVendorSettings('open_ai_max_token', null, null, $vendorId),
                'temperature' => 0.7,
                'messages' => $messages
            ]);
            $promptTokens = $response['usage']['prompt_tokens'] ?? 0;
            $completionTokens = $response['usage']['completion_tokens'] ?? 0;
            $model = getVendorSettings('open_ai_model_key', null, null, $vendorId) ?: 'gpt-3.5-turbo';
            $credits = $this->calculateCredits($model, $promptTokens, $completionTokens);
            $this->deductVendorCredit($vendorId, $credits);
        } catch (\Throwable $th) {
            throw $th;
        }
        return trim($response['choices'][0]['message']['content']);
    }

    /**
     * Generate response via Google Gemini 1.5 Flash REST API with token usage tracking
     */
    public function generateGeminiResponse($systemPrompt, $messages, $question, $vendorId, $geminiApiKey = null)
    {
        $apiKey = $geminiApiKey 
            ?: getVendorSettings('gemini_access_key', null, null, $vendorId)
            ?: getAppSettings('gemini_api_key')
            ?: env('GEMINI_API_KEY');

        $apiKey = trim($apiKey);
        if (empty($apiKey)) {
            return null;
        }

        $models = [];
        try {
            $listUrl = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $apiKey;
            $listResponse = Http::withHeaders(['Content-Type' => 'application/json'])->get($listUrl);
            if ($listResponse->successful()) {
                $modelsData = $listResponse->json()['models'] ?? [];
                foreach ($modelsData as $m) {
                    if (in_array('generateContent', $m['supportedGenerationMethods'] ?? [])) {
                        $models[] = str_replace('models/', '', $m['name']);
                    }
                }
            }
        } catch (\Exception $e) {}

        if (empty($models)) {
            $models = ['gemini-2.0-flash', 'gemini-1.5-flash-latest', 'gemini-2.0-flash-exp', 'gemini-1.5-pro-latest', 'gemini-pro', 'gemini-1.5-flash'];
        }

        $contentsArr = [];
        foreach ($messages as $msg) {
            if (($msg['role'] ?? '') === 'system') {
                continue;
            }
            $role = ($msg['role'] ?? '') === 'assistant' ? 'model' : 'user';
            $content = $msg['content'] ?? '';
            if (!empty($content)) {
                $contentsArr[] = [
                    'role' => $role,
                    'parts' => [['text' => $content]]
                ];
            }
        }

        if (empty($contentsArr)) {
            $contentsArr[] = [
                'role' => 'user',
                'parts' => [['text' => $question]]
            ];
        }

        $payload = [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]]
            ],
            'contents' => $contentsArr,
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 1000,
            ]
        ];

        foreach ($models as $modelName) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key=" . $apiKey;
            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if (!empty($text)) {
                    $usage = $data['usageMetadata'] ?? [];
                    return [
                        'text' => trim($text),
                        'prompt_tokens' => $usage['promptTokenCount'] ?? 0,
                        'completion_tokens' => $usage['candidatesTokenCount'] ?? 0,
                    ];
                }
            }
        }

        \Illuminate\Support\Facades\Log::error('Google Gemini API Response Error', ['body' => $response->body()]);
        return null;
    }

    protected function getExistingChatHistory($contactUid)
    {
        $contact = ContactModel::where('_uid', $contactUid)->first();
        $vendorId = $contact->vendors__id;

        $defaultRecentMessageCount = 6;
        $pastSummary = data_get($contact, '__data.past_ai_summary');
        // check if existing API summary not exists
        if (__isEmpty($pastSummary)) {
            $defaultRecentMessageCount = 30;
        }

        $whatsAppMessageLogCollection = WhatsAppMessageLogModel::where('contacts__id', $contact->_id)
            ->whereNotNull('message')
            ->whereNull('is_system_message')
            ->take($defaultRecentMessageCount)
            ->latest()
            ->get();

        $recentMessages = [];
        // Check if existing chat history exists
        if (!__isEmpty($whatsAppMessageLogCollection)) {
            foreach ($whatsAppMessageLogCollection as $existingChat) {
                $recentMessages[] = [
                    'role' => $existingChat->is_incoming_message ? 'user' : 'assistant',
                    'content' => $existingChat->message
                ];
            }
            $recentMessages = array_reverse($recentMessages);
        }

        $messages = [
            [
                'role' => 'assistant',
                'content' => "You are a summarizer. Combine the existing summary below with the new conversation to form a short updated memory. Be concise and store important facts only."
            ]
        ];

        $existingSummary = [];
        if (!__isEmpty($pastSummary)) {
            $existingSummary = [
                'role' => 'user',
                'content' => "Existing summary: " . $pastSummary
            ];
            
            $messages[] = $existingSummary;
        }

        $messages[] = [
            'role' => 'user',
            'content' => "New conversation: " . json_encode($recentMessages)
        ];

        $messages = array_filter($messages, function ($msg) {
            return isset($msg['content']) && trim($msg['content']) !== '';
        });

        // Step 2: Use OpenAI completion API to generate a refined answer
        try {
            $response = OpenAI::chat()->create([
                'model' => getVendorSettings('open_ai_model_key', null, null, $vendorId) ?: 'gpt-3.5-turbo',
                'max_tokens' => getVendorSettings('open_ai_max_token', null, null, $vendorId),
                'temperature' => 0.7,
                'messages' => $messages
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
        
        $newNewSummary = trim($response['choices'][0]['message']['content']);

        $contactRepository = new ContactRepository();
        $contactRepository->updateIt($contact, [
            '__data' => [
                'past_ai_summary' => $newNewSummary
            ]
        ]);

        return (!__isEmpty($existingSummary)) ? array_merge([$existingSummary], $recentMessages) : $recentMessages;
    }

    protected function deductVendorCredit($vendorId, $credits = 1)
    {
        $vendor = \App\Yantrana\Components\Vendor\Models\VendorModel::find($vendorId);
        if ($vendor) {
            // If credits are unlimited (>= 99999999), do not deduct
            if ($vendor->plan_ai_credits >= 99999999) {
                return;
            }
            if ($vendor->plan_ai_credits >= $credits) {
                $vendor->plan_ai_credits -= $credits;
            } else {
                $remaining = $credits - $vendor->plan_ai_credits;
                $vendor->plan_ai_credits = 0;
                $vendor->extra_ai_credits = max(0, $vendor->extra_ai_credits - $remaining);
            }
            $vendor->save();
        }
    }

    protected function calculateCredits($model, $promptTokens, $completionTokens)
    {
        // Default base cost is 1 credit per request if usage is empty
        if ($promptTokens === 0 && $completionTokens === 0) {
            return 1;
        }

        // Define token multipliers based on model
        // Translate OpenAI cost in USD to Credits.
        // Assume 1000 credits = $1.00 USD.
        // Therefore, $0.001 USD = 1 credit.
        
        $inputCostPer1k = 0.5; // default gpt-3.5-turbo input cost: $0.0005 = 0.5 credits
        $outputCostPer1k = 1.5; // default gpt-3.5-turbo output cost: $0.0015 = 1.5 credits

        if (str_contains($model, 'gemini')) {
            $inputCostPer1k = 0.1; // Gemini 1.5 Flash input cost: ultra cheap
            $outputCostPer1k = 0.3; // Gemini 1.5 Flash output cost
        } elseif (str_contains($model, 'gpt-4o-mini')) {
            $inputCostPer1k = 0.15; // $0.00015
            $outputCostPer1k = 0.6; // $0.0006
        } elseif (str_contains($model, 'gpt-4o') || str_contains($model, 'gpt-4')) {
            $inputCostPer1k = 5.0; // $0.005
            $outputCostPer1k = 15.0; // $0.015
        }

        $inputCredits = ($promptTokens / 1000) * $inputCostPer1k;
        $outputCredits = ($completionTokens / 1000) * $outputCostPer1k;

        $totalCredits = $inputCredits + $outputCredits;

        // Minimum 1 credit, rounded up to the nearest integer
        return max(1, (int) ceil($totalCredits));
    }
}
