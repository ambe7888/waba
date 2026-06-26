<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirestoreService
{
    protected $projectId;
    protected $databaseUrl;

    public function __construct()
    {
        $this->projectId = config('firebase.project_id', 'whatsclick-d5204');
        $this->databaseUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents";
    }

    /**
     * Get OAuth2 Access Token using Google API Client.
     */
    protected function getAccessToken()
    {
        try {
            $credentialsPath = config('firebase.credentials');
            if (!file_exists($credentialsPath)) {
                return null;
            }

            $client = new GoogleClient();
            $client->setAuthConfig($credentialsPath);
            $client->addScope('https://www.googleapis.com/auth/datastore');
            
            $token = $client->fetchAccessTokenWithAssertion();
            return $token['access_token'] ?? null;
        } catch (\Exception $e) {
            Log::error('FirestoreService getAccessToken Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Convert PHP array to Firestore Document Format.
     */
    protected function formatDocument(array $data)
    {
        $fields = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $fields[$key] = ['stringValue' => $value];
            } elseif (is_bool($value)) {
                $fields[$key] = ['booleanValue' => $value];
            } elseif (is_int($value)) {
                $fields[$key] = ['integerValue' => (string) $value]; // Firestore expects string for int64
            } elseif (is_float($value)) {
                $fields[$key] = ['doubleValue' => $value];
            } elseif ($value === null) {
                $fields[$key] = ['nullValue' => null];
            } elseif (is_array($value)) {
                // Simplified: assuming string values in array, or simple map. Let's encode to JSON string for simplicity if nested.
                $fields[$key] = ['stringValue' => json_encode($value)];
            }
        }
        return ['fields' => $fields];
    }

    /**
     * Write or Update a document in Firestore.
     * 
     * @param string $collectionPath (e.g. 'chats/contactUid/messages')
     * @param string $documentId (e.g. 'msg123')
     * @param array $data (associative array of data)
     */
    public function setDocument(string $collectionPath, string $documentId, array $data)
    {
        $token = $this->getAccessToken();
        if (!$token) {
            Log::error("FirestoreService: Cannot get access token to write $collectionPath/$documentId");
            return false;
        }

        $url = "{$this->databaseUrl}/{$collectionPath}/{$documentId}";
        $payload = $this->formatDocument($data);

        $response = Http::withToken($token)->patch($url, $payload);

        if ($response->successful()) {
            return true;
        }

        Log::error("FirestoreService setDocument Error: " . $response->body());
        return false;
    }
}
