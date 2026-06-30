<?php
// Firebase FCM HTTP v1 Notification Setup

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function get_fcm_access_token($json_key_path) {
    if (!file_exists($json_key_path)) return false;
    $key_content = file_get_contents($json_key_path);
    $key_data = json_decode($key_content, true);
    if (!$key_data || !isset($key_data['private_key'])) return false;

    $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
    $now = time();
    $claim = json_encode([
        'iss' => $key_data['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $now + 3600,
        'iat' => $now
    ]);

    $base64_header = base64url_encode($header);
    $base64_claim = base64url_encode($claim);
    
    $signature = '';
    openssl_sign($base64_header . '.' . $base64_claim, $signature, $key_data['private_key'], 'SHA256');
    $base64_signature = base64url_encode($signature);
    
    $jwt = $base64_header . '.' . $base64_claim . '.' . $base64_signature;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]));
    $response = curl_exec($ch);
    curl_close($ch);

    $token_data = json_decode($response, true);
    return isset($token_data['access_token']) ? $token_data['access_token'] : false;
}

function send_fcm_notification($device_token, $title, $body) {
    if (empty($device_token)) return false;
    
    $json_key_path = __DIR__ . '/../firebase-service-account.json';
    if (!file_exists($json_key_path)) return false;
    
    $key_content = file_get_contents($json_key_path);
    $key_data = json_decode($key_content, true);
    $project_id = $key_data['project_id'] ?? '';
    if (empty($project_id)) return false;

    $access_token = get_fcm_access_token($json_key_path);
    if (!$access_token) return false;
    
    $url = 'https://fcm.googleapis.com/v1/projects/' . $project_id . '/messages:send';
    
    $fields = [
        'message' => [
            'token' => $device_token,
            'notification' => [
                'title' => $title,
                'body' => $body
            ]
        ]
    ];
    
    $headers = [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result;
}
?>
