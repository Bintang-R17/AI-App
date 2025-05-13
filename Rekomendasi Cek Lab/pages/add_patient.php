<?php
// File: pages/add_patient.php
?>
<h2>Tambah Data Pasien Baru</h2>
<div class="card">
    <div class="card-body">
        <form method="post" action="index.php?page=add_patient">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="col-md-6">
                    <label for="dob" class="form-label">Tanggal Lahir</label>
                    <input type="date" class="form-control" id="dob" name="dob" required>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="gender" class="form-label">Jenis Kelamin</label>
                    <select class="form-select" id="gender" name="gender" required>
                        <option value="">Pilih...</option>
                        <option value="Laki-laki">Laki-laki</option>
                        <option value="Perempuan">Perempuan</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="phone" class="form-label">Nomor Telepon</label>
                    <input type="tel" class="form-control" id="phone" name="phone" required>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email">
                </div>
                <div class="col-md-6">
                    <label for="address" class="form-label">Alamat</label>
                    <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                </div>
            </div>
            
            <div class="text-end">
                <button type="submit" name="save_patient" class="btn btn-primary">Simpan Data Pasien</button>
            </div>
        </form>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5>Daftar Pasien</h5>
    </div>
    <div class="card-body">
        <?php
        $patients = $db->select("SELECT * FROM patients ORDER BY created_at DESC");
        
        if (!empty($patients)) {
            echo '<div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <th>Tanggal Lahir</th>
                            <th>Jenis Kelamin</th>
                            <th>Kontak</th>
                            <th>Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach ($patients as $patient) {
                $contactInfo = json_decode($patient['contact_info'], true);
                echo "<tr>
                        <td>{$patient['id']}</td>
                        <td>{$patient['name']}</td>
                        <td>{$patient['date_of_birth']}</td>
                        <td>{$patient['gender']}</td>
                        <td>{$contactInfo['phone']}</td>
                        <td>
                            <a href='index.php?page=add_lab_result&patient_id={$patient['id']}' class='btn btn-sm btn-primary'>Input Hasil Lab</a>
                        </td>
                    </tr>";
            }
            
            echo '</tbody></table></div>';
        } else {
            echo '<p class="text-muted">Belum ada data pasien.</p>';
        }
        ?>
    </div>
</div>
