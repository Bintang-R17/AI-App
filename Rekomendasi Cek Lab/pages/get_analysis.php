<?php
// File: pages/get_analysis.php
?>
<h2>Analisis dan Rekomendasi</h2>

<?php
// Inisialisasi objek yang dibutuhkan
$patientModel = new Patient($db);
$labResultModel = new LabResult($db);
$groqAI = new GroqAI(GROQ_API_KEY, GROQ_API_URL);
$dss = new DecisionSupportSystem($groqAI);

// Ambil daftar pasien untuk dropdown
$patients = $db->select("SELECT id, name FROM patients ORDER BY name ASC");

// Periksa parameter dari POST atau GET
$selectedPatientId = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 
                    (isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0);

// Periksa hasil lab yang dipilih
$selectedLabResultIds = isset($_POST['lab_result_ids']) ? $_POST['lab_result_ids'] : [];

// Ambil hasil lab berdasarkan pasien jika ada
$patientLabResults = [];
if ($selectedPatientId > 0) {
    $patientLabResults = $labResultModel->getByPatientId($selectedPatientId);
}

// Variabel untuk menyimpan hasil analisis
$analysisResult = null;

if (isset($_POST['get_analysis']) && !empty($selectedLabResultIds) && $selectedPatientId > 0) {
    // Ambil data pasien
    $patientData = $patientModel->getById($selectedPatientId);
    
    if ($patientData) {
        // Ambil hasil lab yang dipilih
        $selectedLabs = [];
        foreach ($selectedLabResultIds as $labId) {
            $result = $labResultModel->getById($labId);
            if ($result) {
                $selectedLabs[] = $result;
            }
        }
        
        if (!empty($selectedLabs)) {
            try {
                // Generate rekomendasi menggunakan DSS dan Groq AI
                // Perhatikan bahwa kita menambahkan patient ID sebagai parameter pertama
                $analysisResult = $dss->generateRecommendation($selectedPatientId, $patientData, $selectedLabs);
                
                // Redirect ke halaman daftar rekomendasi jika berhasil
                if ($analysisResult['status'] === 'success') {
                    $_SESSION['success_message'] = "Analisis berhasil dibuat!";
                    header("Location: index.php?page=get_analysis");
                    exit;
                } else {
                    $error = "Gagal menghasilkan rekomendasi: " . $analysisResult['message'];
                }
            } catch (Exception $e) {
                $error = "Error: " . $e->getMessage();
                // Tambahkan log error
                error_log("Analysis error: " . $e->getMessage());
            }
        }
    }
}?>

<div class="card">
    <div class="card-body">
        <form method="post" action="index.php?page=get_analysis">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="patient_id" class="form-label">Pilih Pasien</label>
                    <select class="form-select" id="patient_id" name="patient_id" required>
                        <option value="">Pilih Pasien...</option>
                        <?php foreach ($patients as $patient): ?>
                            <option value="<?= $patient['id'] ?>" <?= ($selectedPatientId == $patient['id']) ? 'selected' : '' ?>>
                                <?= $patient['id'] ?> - <?= $patient['name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 align-self-end">
                    <button type="submit" name="select_patient" class="btn btn-secondary">
                        <i class="bi bi-person-check"></i> Pilih
                    </button>
                </div>
            </div>
        </form>
        
        <?php if ($selectedPatientId > 0 && !empty($patientLabResults)): ?>
            <form method="post" action="index.php?page=get_analysis">
            <input type="hidden" name="patient_id" value="<?= $selectedPatientId ?>">
            
            <h5 class="mt-4 mb-3">Pilih Hasil Lab untuk Dianalisis</h5>
            
            <div class="mb-3">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">Pilih</th>
                                <th>ID</th>
                                <th>Jenis Tes</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($patientLabResults as $result): ?>
                                <tr>
                                    <td>
                                        <input class="form-check-input" type="checkbox" name="lab_result_ids[]" value="<?= $result['id'] ?>">
                                    </td>
                                    <td><?= $result['id'] ?></td>
                                    <td><?= $result['test_type'] ?></td>
                                    <td><?= $result['test_date'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="text-end">
                <button type="submit" name="get_analysis" class="btn btn-primary">
                    <i class="bi bi-lightbulb"></i> Analisis Hasil Lab
                </button>
            </div>
        </form>
        
        <!-- Tampilkan hasil analisis jika ada -->
        <?php if ($analysisResult && $analysisResult['status'] === 'success'): ?>
            <div class="mt-4">
                <div class="card <?php 
                    if ($analysisResult['urgency_level'] === 'KRITIS') echo 'border-danger';
                    elseif ($analysisResult['urgency_level'] === 'PERHATIAN') echo 'border-warning';
                    else echo 'border-success';
                ?>">
                    <div class="card-header <?php 
                        if ($analysisResult['urgency_level'] === 'KRITIS') echo 'bg-danger text-white';
                        elseif ($analysisResult['urgency_level'] === 'PERHATIAN') echo 'bg-warning';
                        else echo 'bg-success text-white';
                    ?>">
                        <h5 class="mb-0">
                            <i class="bi <?php 
                                if ($analysisResult['urgency_level'] === 'KRITIS') echo 'bi-exclamation-triangle-fill';
                                elseif ($analysisResult['urgency_level'] === 'PERHATIAN') echo 'bi-exclamation-circle';
                                else echo 'bi-check-circle-fill';
                            ?>"></i>
                            Level Urgensi: <?= $analysisResult['urgency_level'] ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <h5>Hasil Analisis:</h5>
                        <div class="analysis-content">
                            <?php 
                            // Format the analysis content with proper markdown
                            $formattedAnalysis = nl2br(htmlspecialchars($analysisResult['analysis']));
                            echo $formattedAnalysis;
                            ?>
                        </div>
                        
                        <div class="text-end mt-3">
                            <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                                <i class="bi bi-printer"></i> Cetak Hasil
                            </button>
                        </div>
                    </div>
                    <div class="card-footer text-muted">
                        Dihasilkan pada: <?= $analysisResult['timestamp'] ?>
                    </div>
                </div>
            </div>
        <?php elseif (isset($error)): ?>
            <div class="alert alert-danger mt-4">
                <?= $error ?>
            </div>
        <?php endif; ?>
        
        <?php elseif ($selectedPatientId > 0): ?>
            <div class="alert alert-warning mt-3">
                Belum ada hasil laboratorium untuk pasien ini. Silakan tambahkan hasil lab terlebih dahulu.
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.analysis-content {
    white-space: pre-line;
    line-height: 1.6;
    background: #f9f9f9;
    padding: 15px;
    border-radius: 5px;
    border: 1px solid #e9e9e9;
}
</style>