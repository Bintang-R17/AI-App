<?php
// Konfigurasi Database dan API
define('DB_HOST', 'localhost');
define('DB_NAME', 'health_information_system');
define('DB_USER', 'root');
define('DB_PASS', ''); // Ganti dengan password database Anda
define('GROQ_API_KEY', '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'); // Ganti dengan API key Anda
define('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions');

// Kelas Database untuk mengelola koneksi dan operasi database
class Database {
    private $connection;

    public function __construct() {
        try {
            $this->connection = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
                DB_USER,
                DB_PASS,
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
            );
        } catch (PDOException $e) {
            die("Koneksi database gagal: " . $e->getMessage());
        }
    }

    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function select($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insert($table, $data) {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $this->query($sql, array_values($data));
        return $this->connection->lastInsertId();
    }
}

// Kelas LabResult untuk mengelola hasil laboratorium
class LabResult {
    private $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function create($patientId, $testType, $resultData, $date) {
        return $this->db->insert('lab_results', [
            'patient_id' => $patientId,
            'test_type' => $testType,
            'result_data' => json_encode($resultData),
            'test_date' => $date,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function getByPatientId($patientId) {
        return $this->db->select(
            "SELECT * FROM lab_results WHERE patient_id = ? ORDER BY test_date DESC",
            [$patientId]
        );
    }

    public function getById($id) {
        $results = $this->db->select("SELECT * FROM lab_results WHERE id = ?", [$id]);
        return !empty($results) ? $results[0] : null;
    }
}

// Kelas Patient untuk mengelola data pasien
class Patient {
    private $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function create($name, $dob, $gender, $contactInfo) {
        return $this->db->insert('patients', [
            'name' => $name,
            'date_of_birth' => $dob,
            'gender' => $gender,
            'contact_info' => json_encode($contactInfo),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function getById($id) {
        $results = $this->db->select("SELECT * FROM patients WHERE id = ?", [$id]);
        return !empty($results) ? $results[0] : null;
    }
}

// Kelas GroqAI untuk integrasi dengan API Groq
class GroqAI {
    private $apiKey;
    private $apiUrl;

    public function __construct($apiKey, $apiUrl) {
        $this->apiKey = $apiKey;
        $this->apiUrl = $apiUrl;
    }

    public function analyzeLabResults($patientData, $labResults) {
        // Menyiapkan data untuk dikirim ke Groq
        $prompt = $this->preparePrompt($patientData, $labResults);
        
        // Mengirim permintaan ke Groq API
        $response = $this->sendRequest([
            'model' => 'llama3-70b-8192',
            'messages' => [
                ['role' => 'system', 'content' => 'Anda adalah asisten kesehatan yang menganalisis hasil laboratorium dan memberikan rekomendasi medis. Berikan analisis yang komprehensif dan rekomendasi berdasarkan hasil lab dan riwayat pasien.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.3,
            'max_tokens' => 2000
        ]);
        
        return $response;
    }

    private function preparePrompt($patientData, $labResults) {
        // Format data pasien dan hasil lab untuk prompt AI
        $age = date_diff(date_create($patientData['date_of_birth']), date_create('today'))->y;
        
        $prompt = "Analisis hasil laboratorium berikut:\n\n";
        $prompt .= "Data Pasien:\n";
        $prompt .= "- Nama: {$patientData['name']}\n";
        $prompt .= "- Usia: {$age} tahun\n";
        $prompt .= "- Jenis Kelamin: {$patientData['gender']}\n\n";
        
        $prompt .= "Hasil Laboratorium:\n";
        foreach ($labResults as $result) {
            $resultData = json_decode($result['result_data'], true);
            $prompt .= "Tanggal Tes: {$result['test_date']}\n";
            $prompt .= "Jenis Tes: {$result['test_type']}\n";
            
            foreach ($resultData as $key => $value) {
                $prompt .= "- {$key}: {$value['value']} {$value['unit']} (Rentang normal: {$value['normal_range']})\n";
            }
            $prompt .= "\n";
        }
        
        $prompt .= "Berdasarkan hasil di atas, berikan:\n";
        $prompt .= "1. Analisis komprehensif dari hasil tes laboratorium\n";
        $prompt .= "2. Kemungkinan kondisi medis yang perlu diperhatikan\n";
        $prompt .= "3. Rekomendasi tindak lanjut dan penanganan\n";
        $prompt .= "4. Rekomendasi gaya hidup atau perubahan pola makan jika diperlukan\n";
        
        return $prompt;
    }

    private function sendRequest($data) {
        $ch = curl_init($this->apiUrl);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Error saat menghubungi Groq API: {$error}");
        }
        
        return json_decode($response, true);
    }
}

// Kelas DecisionSupportSystem untuk sistem pendukung keputusan
class DecisionSupportSystem {
    private $groqAI;
    
    public function __construct(GroqAI $groqAI) {
        $this->groqAI = $groqAI;
    }
    
    public function generateRecommendation($patientId, $patientData, $labResults) {
        // Mendapatkan analisis dari Groq AI
        $analysis = $this->groqAI->analyzeLabResults($patientData, $labResults);
        
        // Memproses respons untuk diambil rekomendasi
        if (isset($analysis['choices']) && !empty($analysis['choices'])) {
            $recommendation = $analysis['choices'][0]['message']['content'];
            
            // Kategorikan tingkat urgensi
            $urgencyLevel = 'NORMAL';
            
            // Simpan rekomendasi ke database
            $recommendationId = $this->saveRecommendation($patientId, $recommendation, $urgencyLevel);
            
            return [
                'status' => 'success',
                'id' => $recommendationId,
                'analysis' => $recommendation,
                'urgency_level' => $urgencyLevel,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
        
        return [
            'status' => 'error',
            'message' => 'Gagal menghasilkan rekomendasi',
            'raw_response' => $analysis
        ];
    }
    
    // Fungsi untuk menyimpan rekomendasi ke database
    private function saveRecommendation($patientId, $analysisText, $urgencyLevel) {
        // Ganti dari global $db ke properti class
        $db = new Database(); // Atau simpan db sebagai properti class
        
        return $db->insert('recommendations', [
            'patient_id' => $patientId,
            'analysis_text' => $analysisText,
            'urgency_level' => $urgencyLevel,
            'created_by' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function categorizeUrgency($recommendation) {
        // Implementasi algoritma untuk menentukan tingkat urgensi
        // Contoh sederhana: mencari kata kunci dalam hasil analisis
        $urgencyLevel = 'NORMAL';
        
        $criticalKeywords = ['darurat', 'segera', 'kritis', 'berbahaya', 'emergency'];
        $warningKeywords = ['perhatian', 'waspada', 'monitor', 'periksa ulang'];
        
        $recommendationLower = strtolower($recommendation['analysis']);
        
        foreach ($criticalKeywords as $keyword) {
            if (strpos($recommendationLower, $keyword) !== false) {
                $urgencyLevel = 'KRITIS';
                break;
            }
        }
        
        if ($urgencyLevel == 'NORMAL') {
            foreach ($warningKeywords as $keyword) {
                if (strpos($recommendationLower, $keyword) !== false) {
                    $urgencyLevel = 'PERHATIAN';
                    break;
                }
            }
        }
        
        return array_merge($recommendation, ['urgency_level' => $urgencyLevel]);
    }
}

// Contoh penggunaan API endpoint untuk mendapatkan rekomendasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'get_recommendation') {
    header('Content-Type: application/json');
    
    try {
        // Parse input data
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['patient_id']) || !isset($data['lab_result_ids'])) {
            throw new Exception('Data pasien atau hasil lab tidak lengkap');
        }
        
        // Inisialisasi objek yang dibutuhkan
        $db = new Database();
        $patientModel = new Patient($db);
        $labResultModel = new LabResult($db);
        $groqAI = new GroqAI(GROQ_API_KEY, GROQ_API_URL);
        $dss = new DecisionSupportSystem($groqAI);
        
        // Ambil data pasien
        $patientData = $patientModel->getById($data['patient_id']);
        if (!$patientData) {
            throw new Exception('Pasien tidak ditemukan');
        }
        
        // Ambil hasil lab
        $labResults = [];
        foreach ($data['lab_result_ids'] as $labResultId) {
            $result = $labResultModel->getById($labResultId);
            if ($result) {
                $labResults[] = $result;
            }
        }
        
        if (empty($labResults)) {
            throw new Exception('Tidak ada hasil lab yang valid');
        }
        
        // Generate dan kategorikan rekomendasi
        $recommendation = $dss->generateRecommendation($data['patient_id'], $patientData, $labResults);
        $categorizedRecommendation = $dss->categorizeUrgency($recommendation);
        
        echo json_encode([
            'success' => true,
            'recommendation' => $categorizedRecommendation
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    
    exit;
}

// Contoh untuk menambahkan data pasien
function addSamplePatient() {
    $db = new Database();
    $patientModel = new Patient($db);
    
    $patientId = $patientModel->create(
        'Ahmad Setiawan',
        '1985-07-15',
        'Laki-laki',
        [
            'phone' => '08123456789',
            'email' => 'ahmad@example.com',
            'address' => 'Jl. Merdeka No. 123, Jakarta'
        ]
    );
    
    echo "Patient berhasil dibuat dengan ID: {$patientId}";
    return $patientId;
}

// Contoh untuk menambahkan hasil lab
function addSampleLabResult($patientId) {
    $db = new Database();
    $labResultModel = new LabResult($db);
    
    // Contoh hasil lab darah lengkap
    $completeBloodCount = [
        'Hemoglobin' => [
            'value' => 14.5,
            'unit' => 'g/dL',
            'normal_range' => '13.5-17.5'
        ],
        'RBC' => [
            'value' => 5.1,
            'unit' => 'juta/μL',
            'normal_range' => '4.5-5.9'
        ],
        'WBC' => [
            'value' => 7800,
            'unit' => '/μL',
            'normal_range' => '4000-10000'
        ],
        'Platelet' => [
            'value' => 250000,
            'unit' => '/μL',
            'normal_range' => '150000-450000'
        ],
        'Hematocrit' => [
            'value' => 42,
            'unit' => '%',
            'normal_range' => '41-53'
        ]
    ];
    
    $labResultId = $labResultModel->create(
        $patientId,
        'Complete Blood Count',
        $completeBloodCount,
        date('Y-m-d')
    );
    
    echo "Lab result berhasil dibuat dengan ID: {$labResultId}";
    return $labResultId;
}

// Contoh untuk mendapatkan rekomendasi
function getSampleRecommendation($patientId, $labResultId) {
    $db = new Database();
    $patientModel = new Patient($db);
    $labResultModel = new LabResult($db);
    $groqAI = new GroqAI(GROQ_API_KEY, GROQ_API_URL);
    $dss = new DecisionSupportSystem($groqAI);
    
    $patientData = $patientModel->getById($patientId);
    $labResults = [$labResultModel->getById($labResultId)];
    
    $recommendation = $dss->generateRecommendation($patientId, $patientData, $labResults);
    $categorizedRecommendation = $dss->categorizeUrgency($recommendation);
    
    echo "<pre>";
    print_r($categorizedRecommendation);
    echo "</pre>";
}

// Uncomment baris-baris berikut untuk menjalankan contoh
// $patientId = addSamplePatient();
// $labResultId = addSampleLabResult($patientId);
// getSampleRecommendation($patientId, $labResultId);
?>