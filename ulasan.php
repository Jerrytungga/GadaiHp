<?php
// ulasan.php - halaman input ulasan/masukan
include 'database.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = htmlspecialchars(trim($_POST['nama'] ?? ''));
    $ulasan = htmlspecialchars(trim($_POST['ulasan'] ?? ''));
    $rating = intval($_POST['rating'] ?? 5);
    $success = false;
    if ($nama && $ulasan && $rating >= 1 && $rating <= 5) {
        // Simpan ke database
        $tanggal = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO ulasan (Nama, Ulasan, rating, tanggal) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $nama, $ulasan, $rating, $tanggal);
        if ($stmt->execute()) {
            $success = true;
                // Redirect ke halaman index.php setelah submit sukses
                header('Location: index.php');
                exit;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Kirim Ulasan / Masukan</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <style>
            #spinner {
                display: none;
                margin: 0 auto;
            }
        </style>
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card p-4 shadow-sm">
                <h3 class="mb-3">Kirim Ulasan atau Masukan Anda</h3>
                <div id="successMsg" class="alert alert-success" style="display:none;">Terima kasih atas ulasan/masukan Anda!</div>
                <form id="ulasanForm" action="ulasan.php" method="post">
                                        <div class="mb-3">
                                                <label for="namaUlasan" class="form-label">Nama</label>
                                                <input type="text" class="form-control" id="namaUlasan" name="nama" required>
                                        </div>
                                        <div class="mb-3">
                                                <label for="isiUlasan" class="form-label">Ulasan / Masukan</label>
                                                <textarea class="form-control" id="isiUlasan" name="ulasan" rows="3" required></textarea>
                                        </div>
                                        <div class="mb-3">
                                                <label for="rating" class="form-label">Rating</label>
                                                <select class="form-select" id="rating" name="rating" required>
                                                        <option value="5" selected>5 - Sangat Puas ⭐⭐⭐⭐⭐</option>
                                                        <option value="4">4 - Puas ⭐⭐⭐⭐</option>
                                                        <option value="3">3 - Cukup ⭐⭐⭐</option>
                                                        <option value="2">2 - Kurang ⭐⭐</option>
                                                        <option value="1">1 - Tidak Puas ⭐</option>
                                                </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Kirim</button>
                                        <div id="spinner" class="mt-3 text-center">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
$(function(){
    $('#ulasanForm').on('submit', function(e){
        e.preventDefault();
        $('#spinner').show();
        var form = $(this);
        $.post('ulasan.php', form.serialize(), function(data){
            $('#spinner').hide();
            $('#successMsg').fadeIn();
            form[0].reset();
            setTimeout(function(){ $('#successMsg').fadeOut(); }, 3000);
            // Redirect ke halaman index.php setelah submit sukses
            window.location.href = 'index.php';
        });
    });
});
</script>
</body>
</html>
