<?php
// File: pages/view_recommendation.php

// Pastikan ID rekomendasi tersedia
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php?page=recommendation_list");
    exit;
}

$recommendationId = (int)$_GET['id'];

// Ambil data rekomendasi
$recommendation = $db->select("
    SELECT r.*, p.name as patient_name, p.date_of_birth, p.gender
    FROM recommendations r
    JOIN patients p ON r.patient_id = p.id
    WHERE r.id = ?
", [$recommendationId]);

if (empty($recommendation)) {
    header("Location: index.php?page=recommendation_list");
    exit;
}

$recommendation = $recommendation[0];

// Hitung usia pasien
$birthDate = new DateTime($recommendation['date_of_birth']);
$today = new DateTime();
$age = $birthDate->diff($today)->y;
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Detail Rekomendasi</h2>
    <a href="index.php?page=recommendation_list" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Data Pasien</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Nama:</strong> <?= $recommendation['patient_name'] ?></p>
                <p><strong>Umur:</strong> <?= $age ?> tahun</p>
            </div>
            <div class="col-md-6">
                <p><strong>Jenis Kelamin:</strong> <?= $recommendation['gender'] ?></p>
                <p><strong>Tanggal Analisis:</strong> <?= $recommendation['created_at'] ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card <?php 
    if ($recommendation['urgency_level'] === 'KRITIS') echo 'border-danger';
    elseif ($recommendation['urgency_level'] === 'PERHATIAN') echo 'border-warning';
    else echo 'border-success';
?>">
    <div class="card-header <?php 
        if ($recommendation['urgency_level'] === 'KRITIS') echo 'bg-danger text-white';
        elseif ($recommendation['urgency_level'] === 'PERHATIAN') echo 'bg-warning';
        else echo 'bg-success text-white';
    ?>">
        <h5 class="mb-0">
            <i class="bi <?php 
                if ($recommendation['urgency_level'] === 'KRITIS') echo 'bi-exclamation-triangle-fill';
                elseif ($recommendation['urgency_level'] === 'PERHATIAN') echo 'bi-exclamation-circle';
                else echo 'bi-check-circle-fill';
            ?>"></i>
            Level Urgensi: <?= $recommendation['urgency_level'] ?>
        </h5>
    </div>
    <div class="card-body">
        <h5>Hasil Analisis:</h5>
        <div class="analysis-content">
            <?= nl2br(htmlspecialchars($recommendation['analysis_text'])) ?>
        </div>
        
        <div class="text-end mt-3">
            <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                <i class="bi bi-printer"></i> Cetak Hasil
            </button>
        </div>
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