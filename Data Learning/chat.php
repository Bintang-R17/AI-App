<?php
header('Content-Type: application/json');

// Ambil input dari front-end (JS)
$input = json_decode(file_get_contents("php://input"), true);
$userMessage = $input['message'] ?? '';

if (!$userMessage) {
    echo json_encode(['error' => 'Pesan kosong']);
    exit;
}

// Mulai atau lanjutkan sesi untuk riwayat percakapan
session_start();
if (!isset($_SESSION['conversation'])) {
    $_SESSION['conversation'] = [];
}

// === API Groq Config ===
$apiKey = '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';
$apiUrl = 'https://api.groq.com/openai/v1/chat/completions';

// Baca CSV dan ubah jadi string
$csvFile = fopen('produk.csv', 'r');
$csvData = [];
while (($row = fgetcsv($csvFile, 1000, ',')) !== false) {
    $csvData[] = $row;
}
fclose($csvFile);

// Ubah jadi teks yang bisa dibaca AI
$headers = $csvData[0];
$rows = array_slice($csvData, 1);

$data = "Berikut adalah daftar produk:\n";
foreach ($rows as $row) {
    $produk = array_combine($headers, $row);
    $data .= "- {$produk['Nama']}, harga Rp{$produk['Harga']}, kategori: {$produk['Kategori']}\n";
}

// Buat array pesan dengan riwayat percakapan
$messages = [];

// Pertama, tambahkan pesan sistem dengan instruksi karakter
$messages[] = ["role" => "system", "content" => $karakterAI . "\n\n" . $data];

// Kemudian tambahkan riwayat percakapan
foreach ($_SESSION['conversation'] as $message) {
    $messages[] = $message;
}

// Tambahkan pesan pengguna saat ini
$messages[] = ["role" => "user", "content" => $userMessage];

$payload = [
    "model" => "meta-llama/llama-4-scout-17b-16e-instruct",
    "messages" => $messages
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
$responseData = json_decode($response, true);

if (curl_errno($ch)) {
    echo json_encode(['error' => curl_error($ch)]);
} else {
    // Jika kita mendapatkan respons yang berhasil
    if (isset($responseData['choices'][0]['message']['content'])) {
        // Simpan pertukaran dalam riwayat percakapan
        $_SESSION['conversation'][] = ["role" => "user", "content" => $userMessage];
        $_SESSION['conversation'][] = [
            "role" => "assistant", 
            "content" => $responseData['choices'][0]['message']['content']
        ];
        
        // Batasi riwayat percakapan untuk mencegah token terlalu banyak (opsional)
        if (count($_SESSION['conversation']) > 20) {
            // Simpan hanya 10 pertukaran terakhir (20 pesan)
            $_SESSION['conversation'] = array_slice($_SESSION['conversation'], -20);
        }
    }
    
    echo $response;
}

curl_close($ch);
?>