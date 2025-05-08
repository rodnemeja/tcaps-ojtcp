<?php
include('../Includes/conn_pdo.php');
include('../Includes/conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['upload_id']) && isset($data['action'])) {
        $upload_id = $data['upload_id'];
        $action = $data['action'];
        $status = ($action === 'approve') ? 'Approved' : 'Disapproved';

        $sql = "UPDATE upload SET status = :status WHERE upload_id = :upload_id";
        $stmt = $connect->prepare($sql);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':upload_id', $upload_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
