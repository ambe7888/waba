<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Service Account Credentials
    |--------------------------------------------------------------------------
    |
    | Path to the Firebase service account JSON file.
    | This file is used by the Firebase Admin SDK to generate Custom Tokens
    | for mobile app authentication.
    |
    */
    'credentials' => storage_path('firebase/firebase-service-account.json'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Project ID
    |--------------------------------------------------------------------------
    */
    'project_id' => env('FIREBASE_PROJECT_ID', 'whatsclick-d5204'),
];
