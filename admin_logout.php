<?php
session_start();

unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_id']);
unset($_SESSION['admin_user']);
unset($_SESSION['admin_nik']);
unset($_SESSION['login_time']);

session_regenerate_id(true);

header('Location: login.php?role=admin');
exit;