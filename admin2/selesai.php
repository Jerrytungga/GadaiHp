<?php
include '../database.php';
session_start();

if (!isset($_SESSION['id'])) {
  echo "error";
  exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $id = $_POST['id'];

  // Ubah status menjadi selesai
  $stmt = $conn->prepare("UPDATE From_gadai SET status = 'selesai' WHERE id_form = ?");
  $stmt->bind_param("i", $id);

  if ($stmt->execute()) {
    echo "success";
  } else {
    echo "error";
  }

  $stmt->close();
}
?>