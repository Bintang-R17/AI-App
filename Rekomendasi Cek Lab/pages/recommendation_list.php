<?php
// File: pages/recommendation_list.php
?>
<h2>Daftar Rekomendasi</h2>

<?php
// Ambil semua rekomendasi
$recommendations = $db->select("
    SELECT r.*, p.name as patient_name 
    FROM recommendations r
    JOIN patients p ON r.patient_id = p.id
    ORDER BY r.created_at DESC
");

// Tampilkan pesan sukses jika ada
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}
?>

<div class="card">
    <div class="card-body">
        <?php if (empty($recommendations)): ?>
            <div class="alert alert-warning">
                Tidak ada data rekomendasi yang tersedia.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Pasien</th>
                            <th>Level Urgensi</th>
                            <th>Tanggal Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recommendations as $rec): ?>
                            <tr>
                                <td><?= $rec['id'] ?></td>
                                <td><?= $rec['patient_name'] ?></td>
                                <td>
                                    <span class="badge <?= 
                                        $rec['urgency_level'] === 'KRITIS' ? 'bg-danger' : 
                                        ($rec['urgency_level'] === 'PERHATIAN' ? 'bg-warning' : 'bg-success') 
                                    ?>">
                                        <?= $rec['urgency_level'] ?>
                                    </span>
                                </td>
                                <td><?= $rec['created_at'] ?></td>
                                <td>
                                    <a href="index.php?page=view_recommendation&id=<?= $rec['id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i> Lihat
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <div class="mt-3">
            <a href="index.php?page=get_analysis" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Buat Analisis Baru
            </a>
        </div>
    </div>
</div>