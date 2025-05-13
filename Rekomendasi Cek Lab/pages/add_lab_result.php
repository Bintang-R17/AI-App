
<?php
// File: pages/add_lab_result.php
?>
<h2>Input Hasil Laboratorium</h2>

<?php
// Jika ada parameter pasien_id, ambil data pasiennya
$selectedPatientId = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
$selectedPatient = null;

if ($selectedPatientId > 0) {
    $selectedPatient = $patientModel->getById($selectedPatientId);
}

// Ambil daftar pasien untuk dropdown
$patients = $db->select("SELECT id, name FROM patients ORDER BY name ASC");
?>

<div class="card">
    <div class="card-body">
        <form method="post" action="index.php?page=add_lab_result">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="patient_id" class="form-label">Pasien</label>
                    <select class="form-select" id="patient_id" name="patient_id" required>
                        <option value="">Pilih Pasien...</option>
                        <?php foreach ($patients as $patient): ?>
                            <option value="<?= $patient['id'] ?>" <?= ($selectedPatientId == $patient['id']) ? 'selected' : '' ?>>
                                <?= $patient['id'] ?> - <?= $patient['name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="test_type" class="form-label">Jenis Tes</label>
                    <input type="text" class="form-control" id="test_type" name="test_type" required>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="test_date" class="form-label">Tanggal Tes</label>
                    <input type="date" class="form-control" id="test_date" name="test_date" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>
            
            <h5 class="mt-4 mb-3">Parameter Hasil Lab</h5>
            
            <div class="row mb-2">
                <div class="col-md-3"><strong>Nama Parameter</strong></div>
                <div class="col-md-3"><strong>Nilai</strong></div>
                <div class="col-md-3"><strong>Satuan</strong></div>
                <div class="col-md-3"><strong>Rentang Normal</strong></div>
            </div>
            
            <div id="lab-parameters">
                <!-- Default parameter rows -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="param_name_0" placeholder="Nama Parameter" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="param_value_0" placeholder="Nilai" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="param_unit_0" placeholder="Satuan" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="param_range_0" placeholder="Rentang Normal" required>
                    </div>
                </div>
            </div>
            
            <input type="hidden" id="param_count" name="param_count" value="1">
            
            <div class="mb-3">
                <button type="button" class="btn btn-secondary" onclick="addLabParameter()">+ Tambah Parameter</button>
            </div>
            
            <div class="text-end">
                <button type="submit" name="save_lab_result" class="btn btn-primary">Simpan Hasil Lab</button>
            </div>
        </form>
    </div>
</div>

<?php
// Tampilkan hasil lab sebelumnya jika ada pasien yang dipilih
if ($selectedPatient) {
    $previousResults = $labResultModel->getByPatientId($selectedPatientId);
    
    if (!empty($previousResults)) {
        echo '<div class="card mt-4">
            <div class="card-header">
                <h5>Hasil Lab Sebelumnya - ' . $selectedPatient['name'] . '</h5>
            </div>
            <div class="card-body">';
        
        foreach ($previousResults as $result) {
            $resultData = json_decode($result['result_data'], true);
            
            echo '<div class="mb-4 p-3 border rounded">
                <h6>' . $result['test_type'] . ' - ' . $result['test_date'] . '</h6>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Parameter</th>
                            <th>Nilai</th>
                            <th>Satuan</th>
                            <th>Rentang Normal</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach ($resultData as $paramName => $paramData) {
                $valueClass = '';
                $value = $paramData['value'];
                $range = $paramData['normal_range'];
                
                // Cek apakah nilai di luar rentang normal
                if (preg_match('/(\d+(?:\.\d+)?)-(\d+(?:\.\d+)?)/', $range, $matches)) {
                    $min = floatval($matches[1]);
                    $max = floatval($matches[2]);
                    $valueFloat = floatval($value);
                    
                    if ($valueFloat < $min || $valueFloat > $max) {
                        $valueClass = 'text-danger fw-bold';
                    }
                }
                
                echo '<tr>
                    <td>' . $paramName . '</td>
                    <td class="' . $valueClass . '">' . $value . '</td>
                    <td>' . $paramData['unit'] . '</td>
                    <td>' . $range . '</td>
                </tr>';
            }
            
            echo '</tbody></table>
                <div class="text-end">
                    <a href="index.php?page=get_analysis&patient_id=' . $selectedPatientId . '&lab_result_id=' . $result['id'] . '" class="btn btn-sm btn-info">
                        Analisis Hasil Ini
                    </a>
                </div>
            </div>';
        }
        
        echo '</div></div>';
    }
}
?>
