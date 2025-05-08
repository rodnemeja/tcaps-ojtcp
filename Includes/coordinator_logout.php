<?php
session_start();;
unset($_SESSION['dean_id']);
unset($_SESSION['dean_name']);
session_unset();
session_destroy();
echo "<script>window.location.href='../signin_student';</script>";

?>