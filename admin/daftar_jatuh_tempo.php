<?php
include '../database.php';

$query = mysqli_query($conn, "SELECT * FROM From_gadai WHERE tanggal_jatuh_tempo <= CURDATE()");
$data = mysqli_fetch_all($query, MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Jatuh Tempo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  </head>
  <body>
    <div class="container mt-5">
      <h1 class="text-center">Daftar Jatuh Tempo</h1>
      <?php if (empty($data)): ?>
        <div class="alert alert-info mt-4">Tidak ada data jatuh tempo.</div>
      <?php else: ?>
        <table class="table table-bordered mt-4">
          <thead>
            <tr>
              <th>Nama</th>
              <th>Alamat</th>
              <th>No. Telepon</th>
              <th>Merek & Tipe HP</th>
              <th>Nomor IMEI</th>
              <th>Tanggal Jatuh Tempo</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($data as $row): ?>
              <tr>
                <td><?= htmlspecialchars($row['nama']) ?></td>
                <td><?= htmlspecialchars($row['alamat']) ?></td>
                <td><?= htmlspecialchars($row['no_hp']) ?></td>
                <td><?= htmlspecialchars($row['merek_tipe_hp']) ?></td>
                <td><?= htmlspecialchars($row['imei_hp']) ?></td>
                <td><?= htmlspecialchars($row['tanggal_jatuh_tempo']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </body>
</html>