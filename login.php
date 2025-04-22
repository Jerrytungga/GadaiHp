<?php
include 'database.php';
session_start();
if (isset($_POST['login'])) {
    $ktp = htmlspecialchars($_POST['ktp']);
    $sql = "SELECT * FROM admin WHERE nik ='$ktp'";
    $result = mysqli_query($conn, $sql);
    if ($result->num_rows > 0) {
      $row = mysqli_fetch_assoc($result);
      $_SESSION['id'] = $row['id'];
      if ($result) {
        header("Location: AdminGadai/index.php");
      }
    }
}
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  </head>
  <style>
    .container {
      margin-top: 80px;
    }
    body {
      background-color: #f8f9fa;
    }
    .logo {
      display: block;
      margin-left: auto;
      margin-right: auto;
      width: 40%;
    }
  </style>
  <body>
    <div class="container">

        <div class="row">
            <div class="col-md-6 offset-md-3">
                <img src="image/GC.png" class="logo" alt="Logo">
          <form action="" method="post">
            <div class="mb-3">
              <label for="ktp" class="form-label">INPUT YOUR KEY</label>
              <input type="text" class="form-control" name="ktp">
            </div>
            <button type="submit" name="login" class="btn btn-primary">Login</button>
          </form>
        </div>
      </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  </body>
</html>