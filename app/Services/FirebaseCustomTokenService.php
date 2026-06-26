<?php

namespace App\Services;

use Firebase\JWT\JWT;

/**
 * Service to generate Firebase Custom Tokens.
 *
 * Uses the Firebase service account credentials to create a JWT
 * that can be used by the mobile app to sign in via
 * FirebaseAuth.signInWithCustomToken().
 *
 * @see https://firebase.google.com/docs/auth/admin/create-custom-tokens
 */
class FirebaseCustomTokenService
{
    /**
     * Generate a Firebase Custom Token for the given user.
     *
     * @param string $uid  A unique identifier for the user (e.g., the Laravel user ID or UID).
     * @param array  $claims  Optional additional claims to embed in the token.
     * @return string|null  The signed JWT custom token, or null on failure.
     */
    public static function createCustomToken(string $uid, array $claims = []): ?string
    {
        try {
            $credentialsPath = config('firebase.credentials');

            if (!file_exists($credentialsPath)) {
                \Log::error('Firebase service account file not found: ' . $credentialsPath);
                return null;
            }

            $serviceAccount = json_decode(file_get_contents($credentialsPath), true);

            if (!$serviceAccount || empty($serviceAccount['private_key']) || empty($serviceAccount['client_email'])) {
                \Log::error('Invalid Firebase service account file');
                return null;
            }

            $now = time();

            $payload = [
                'iss' => $serviceAccount['client_email'],
                'sub' => $serviceAccount['client_email'],
                'aud' => 'https://identitytoolkit.googleapis.com/google.identity.identitytoolkit.v1.IdentityToolkit',
                'iat' => $now,
                'exp' => $now + 3600, // Token expires in 1 hour
                'uid' => $uid,
            ];

            // Add custom claims if provided
            if (!empty($claims)) {
                $payload['claims'] = $claims;
            }

            return JWT::encode($payload, $serviceAccount['private_key'], 'RS256');
        } catch (\Exception $e) {
            \Log::error('Firebase Custom Token generation failed: ' . $e->getMessage());
            return null;
        }
    }
}
