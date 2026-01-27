<?php

function send_whatsapp_notification($name, $email, $message) {
    $configPath = __DIR__ . '/../config/whatsapp.php';
    if (!file_exists($configPath)) {
        return false;
    }

    $config = require $configPath;
    $phone = $config['callmebot_phone'] ?? '';
    $apikey = $config['callmebot_key'] ?? '';

    if ($phone === '' || $apikey === '') {
        return false;
    }

    $text = "New Contact Form\n" .
            "Name: {$name}\n" .
            "Email: {$email}\n" .
            "Message: {$message}";

    $query = http_build_query([
        'phone' => $phone,
        'text' => $text,
        'apikey' => $apikey,
    ]);

    $url = "https://api.callmebot.com/whatsapp.php?{$query}";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);

    curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $status === 200;
}
