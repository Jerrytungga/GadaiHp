<?php

// mengaktifkan session php
session_start();
session_unset();
// menghapus semua session
session_destroy();

// mengalihkan halaman ke halaman login
header("location:index.php");