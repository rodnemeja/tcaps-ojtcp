<?php
session_start();
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

// Check if invoice_id is provided
if(!isset($_POST['invoice_id']) || !is_numeric($_POST['invoice_id'])) {
    $_SESSION['error'] = "Invalid invoice ID";
    header("location: invoices.php");
    exit;
}

$invoice_id = $_POST['invoice_id'];

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Delete related records first
    // Delete invoice items
    $sql = "DELETE FROM invoice_items WHERE invoice_id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $invoice_id);
        mysqli_stmt_execute($stmt);
    }

    // Delete payments
    $sql = "DELETE FROM payments WHERE invoice_id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $invoice_id);
        mysqli_stmt_execute($stmt);
    }

    // Finally, delete the invoice
    $sql = "DELETE FROM invoices WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $invoice_id);
        mysqli_stmt_execute($stmt);
    }

    // Commit transaction
    mysqli_commit($conn);
    $_SESSION['success'] = "Invoice has been deleted successfully";

} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    $_SESSION['error'] = "Error deleting invoice: " . $e->getMessage();
}

// Redirect back to invoices list
header("location: invoices.php");
exit;
?> 