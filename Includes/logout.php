<?php
session_start();
unset($_SESSION['student_id']);
unset($_SESSION['id']);
unset($_SESSION['name']);
unset($_SESSION['dean_id']);
unset($_SESSION['dean_name']);
session_unset();
session_destroy();
echo "<script>window.location.href='../index1';</script>";

?>