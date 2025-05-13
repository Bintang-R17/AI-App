<?php
// File: pages/dashboard.php
?>
<h2>Dashboard</h2>
<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Informasi Sistem</h5>
            </div>
            <div class="card-body">
                <p>Selamat datang di Sistem Informasi Kesehatan terintegrasi dengan AI.</p>
                <p>Sistem ini memungkinkan Anda untuk:</p>
                <ul>
                    <li>Mengelola data pasien</li>
                    <li>Menyimpan dan melacak hasil laboratorium</li>
                    <li>Mendapatkan analisis dan rekomendasi menggunakan AI Groq</li>
                    <li>Mendapatkan dukungan keputusan klinis</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Statistik</h5>
            </div>
            <div class="card-body">
                <?php
                // Menampilkan beberapa statistik dasar
                $patientCount = count($db->select("SELECT id FROM patients"));
                $labResultCount = count($db->select("SELECT id FROM lab_results"));
                ?>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="border rounded p-3 text-center">
                            <h3><?= $patientCount ?></h3>
                            <p class="mb-0">Total Pasien</p>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="border rounded p-3 text-center">
                            <h3><?= $labResultCount ?></h3>
                            <p class="mb-0">Total Hasil Lab</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>Aktivitas Terbaru</h5>
            </div>
            <div class="card-body">
                <?php
                $recentLabResults = $db->select("
                    SELECT lr.id, p.name as patient_name, lr.test_type, lr.test_date
                    FROM lab_results lr
                    JOIN patients p ON lr.patient_id = p.id
                    ORDER BY lr.created_at DESC
                    LIMIT 5
                ");
                
                if (!empty($recentLabResults)) {
                    echo '<table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Pasien</th>
                                <th>Jenis Tes</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>';
                    
                    foreach ($recentLabResults as $result) {
                        echo "<tr>
                        <?php
// Melanjutkan file: pages/dashboard.php
?>
                            <td>{$result['id']}</td>
                            <td>{$result['patient_name']}</td>
                            <td>{$result['test_type']}</td>
                            <td>{$result['test_date']}</td>
                        </tr>";
                    }
                    
                    echo '</tbody></table>';
                } else {
                    echo '<p class="text-muted">Belum ada hasil laboratorium terbaru.</p>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

