<?php
// Include file utama sistem
require_once 'sistem_kesehatan.php';

// Inisialisasi koneksi database
$db = new Database();
$patientModel = new Patient($db);
$labResultModel = new LabResult($db);
$groqAI = new GroqAI(GROQ_API_KEY, GROQ_API_URL);
$dss = new DecisionSupportSystem($groqAI);

// Fungsi untuk menampilkan alert
function showAlert($message, $type = 'success') {
    echo "<div class='alert alert-{$type}' role='alert'>{$message}</div>";
}

// Proses form jika ada submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simpan pasien baru
    if (isset($_POST['save_patient'])) {
        try {
            $patientId = $patientModel->create(
                $_POST['name'],
                $_POST['dob'],
                $_POST['gender'],
                [
                    'phone' => $_POST['phone'],
                    'email' => $_POST['email'],
                    'address' => $_POST['address']
                ]
            );
            
            showAlert("Data pasien berhasil disimpan dengan ID: {$patientId}");
        } catch (Exception $e) {
            showAlert("Error: " . $e->getMessage(), 'danger');
        }
    }
    
    // Simpan hasil lab
    if (isset($_POST['save_lab_result'])) {
        try {
            // Parse data hasil lab dari form
            $resultData = [];
            $paramCount = intval($_POST['param_count']);
            
            for ($i = 0; $i < $paramCount; $i++) {
                $paramName = $_POST["param_name_{$i}"];
                $paramValue = $_POST["param_value_{$i}"];
                $paramUnit = $_POST["param_unit_{$i}"];
                $paramRange = $_POST["param_range_{$i}"];
                
                $resultData[$paramName] = [
                    'value' => $paramValue,
                    'unit' => $paramUnit,
                    'normal_range' => $paramRange
                ];
            }
            
            $labResultId = $labResultModel->create(
                $_POST['patient_id'],
                $_POST['test_type'],
                $resultData,
                $_POST['test_date']
            );
            
            showAlert("Hasil laboratorium berhasil disimpan dengan ID: {$labResultId}");
        } catch (Exception $e) {
            showAlert("Error: " . $e->getMessage(), 'danger');
        }
    }
    
    // Proses permintaan analisis
    if (isset($_POST['get_analysis'])) {
        try {
            $patientId = $_POST['patient_id'];
            $labResultIds = isset($_POST['lab_result_ids']) ? $_POST['lab_result_ids'] : [];
            
            $patientData = $patientModel->getById($patientId);
            if (!$patientData) {
                throw new Exception('Pasien tidak ditemukan');
            }
            
            $labResults = [];
            foreach ($labResultIds as $labResultId) {
                $result = $labResultModel->getById($labResultId);
                if ($result) {
                    $labResults[] = $result;
                }
            }
            
            if (empty($labResults)) {
                throw new Exception('Tidak ada hasil lab yang valid dipilih');
            }
            
            $recommendation = $dss->generateRecommendation($patientId, $patientData, $labResults);
            $categorizedRecommendation = $dss->categorizeUrgency($recommendation);
            
            $_SESSION['recommendation'] = $categorizedRecommendation;
            header('Location: index.php?page=view_recommendation');
            exit;
        } catch (Exception $e) {
            showAlert("Error: " . $e->getMessage(), 'danger');
        }
    }
}

// Handle page routing
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi Kesehatan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 20px;
            padding-bottom: 20px;
        }
        .navbar {
            margin-bottom: 20px;
        }
        .urgency-normal {
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .urgency-warning {
            background-color: #fff3cd;
            border-color: #ffeeba;
        }
        .urgency-critical {
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
    </style>
</head>
<body>

<div class="container">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">SI-Kesehatan</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link <?= $page == 'dashboard' ? 'active' : '' ?>" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $page == 'add_patient' ? 'active' : '' ?>" href="index.php?page=add_patient">Tambah Pasien</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $page == 'add_lab_result' ? 'active' : '' ?>" href="index.php?page=add_lab_result">Input Hasil Lab</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $page == 'get_analysis' ? 'active' : '' ?>" href="index.php?page=get_analysis">Analisis Hasil</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="content mt-4">
        <?php
        // Handle different pages
        switch ($page) {
            case 'dashboard':
                include('pages/dashboard.php');
                break;
            case 'add_patient':
                include('pages/add_patient.php');
                break;
            case 'add_lab_result':
                include('pages/add_lab_result.php');
                break;
            case 'get_analysis':
                include('pages/get_analysis.php');
                break;
            case 'view_recommendation':
                include('pages/view_recommendation.php');
                break;
            case 'recommendation_list':
                include('pages/recommendation_list.php');
                break;
            default:
                include('pages/dashboard.php');
        }
        ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Script untuk menambahkan parameter hasil lab secara dinamis
function addLabParameter() {
    const container = document.getElementById('lab-parameters');
    const paramCount = document.getElementById('param_count');
    const index = parseInt(paramCount.value);
    
    const paramRow = document.createElement('div');
    paramRow.className = 'row mb-3';
    paramRow.innerHTML = `
        <div class="col-md-3">
            <input type="text" class="form-control" name="param_name_${index}" placeholder="Nama Parameter" required>
        </div>
        <div class="col-md-3">
            <input type="text" class="form-control" name="param_value_${index}" placeholder="Nilai" required>
        </div>
        <div class="col-md-3">
            <input type="text" class="form-control" name="param_unit_${index}" placeholder="Satuan" required>
        </div>
        <div class="col-md-3">
            <input type="text" class="form-control" name="param_range_${index}" placeholder="Rentang Normal" required>
        </div>
    `;
    
    container.appendChild(paramRow);
    paramCount.value = index + 1;
}
</script>

</body>
</html>