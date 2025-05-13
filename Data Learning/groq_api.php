<?php
include_once 'config.php';

function kirimKeGroq($userMessage) {
    $url = 'https://api.groq.com/openai/v1/chat/completions';

    $data = [
        "model" => "meta-llama/llama-4-scout-17b-16e-instruct",
        "messages" => [
            ["role" => "user", "content" => $userMessage]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . GROQ_API_KEY
    ]);

    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}
?>