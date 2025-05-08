<?php
include('../Includes/conn_pdo.php');
include('../Includes/conn.php');

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['action'], $data['id'])) {
    $action = $data['action'];
    $id = $data['id'];
    $status = ($action === 'approve') ? 'Approved' : 'Disapproved';

    $query = "UPDATE upload SET status = :status WHERE upload_id = :id";
    $statement = $connect->prepare($query);
    $statement->execute([
        ':status' => $status,
        ':id' => $id
    ]);

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>
