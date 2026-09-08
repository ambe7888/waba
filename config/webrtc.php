<?php

/**
 * coturn TURN/STUN relay used by WhatsApp voice calling (WebRTC), needed for
 * audio to actually connect when either side is behind a restrictive/
 * symmetric NAT that plain STUN can't traverse. Self-hosted on the app VPS.
 */
return [
    'turn_host' => env('TURN_HOST', '31.70.111.91'),
    'turn_port' => env('TURN_PORT', 3478),
    // Shared secret for coturn's use-auth-secret / time-limited credential
    // mechanism (must match static-auth-secret in /etc/turnserver.conf).
    'turn_secret' => env('TURN_SHARED_SECRET', '61d6da8997f07983d3b0752ead4508c6d19ade7a6e411f6bcda87f6967192f36'),
    // How long a generated username/credential pair stays valid.
    'turn_credential_ttl' => 3600,
];
