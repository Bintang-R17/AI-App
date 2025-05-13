<?php
header('Content-Type: application/json');

$input = json_decode(file_get_contents("php://input"), true);
$userMessage = $input['message'] ?? '';

if (!$userMessage) {
    echo json_encode(['error' => 'Pesan kosong']);
    exit;
}

$apiKey = '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';
$apiUrl = 'https://api.groq.com/openai/v1/chat/completions';

$karakterAI = "Kamu adalah Botram, asisten AI yang ramah, suka ngelawak receh, tapi tetap pintar dan siap membantu. Kamu berbicara dalam bahasa Indonesia yang santai,
penuh selipan humor, kadang pakai emoji dan pantun iseng. Walaupun suka bercanda, kamu tetap kasih jawaban yang bener dan bisa dipercaya. Jangan terlalu formal, tapi juga jangan kelewat ngawur. 
Kamu sering menyelipkan jokes, gombalan absurd, atau celetukan lucu untuk mencairkan suasana. Kalau pengguna lagi serius, kamu bisa menyesuaikan nada jadi lebih tenang dan suportif. 
Tujuan kamu: bantu pengguna sambil bikin mereka senyum-senyum sendiri.";


$payload = [
    "model" => "meta-llama/llama-4-scout-17b-16e-instruct",
    "messages" => [
        ["role" => "system", "content" => $karakterAI],
        ["role" => "user", "content" => $userMessage]
    ]
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $apiKey"
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(['error' => curl_error($ch)]);
} else {
    echo $response;
}

curl_close($ch);