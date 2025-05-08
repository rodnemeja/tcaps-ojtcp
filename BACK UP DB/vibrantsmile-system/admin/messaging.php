<?php
session_start();
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

// Handle AJAX requests
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    header("Content-Type: application/json");
    
    // Check if messages table exists
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'messages'");
    $messages_table_exists = mysqli_num_rows($table_check) > 0;
    
    if (!$messages_table_exists) {
        echo json_encode([
            "success" => false,
            "message" => "Messages table does not exist"
        ]);
        exit;
    }
    
    $action = $_POST["action"];
    $admin_id = $_SESSION['id'];
    
    // Handle get_messages action
    if ($action === "get_messages") {
        // Validate required parameters
        if (!isset($_POST["user_id"]) || !isset($_POST["user_role"])) {
            echo json_encode([
                "success" => false,
                "message" => "Missing required parameters"
            ]);
            exit;
        }
        
        // Get parameters
        $user_id = $_POST["user_id"];
        $user_role = $_POST["user_role"];
        
        // Debug log
        error_log("Getting messages for user_id: $user_id, user_role: $user_role, admin_id: $admin_id");
        
        // Get messages between admin and the specified user
        $sql = "SELECT m.*, 
                CASE 
                    WHEN m.from_user_role = 'admin' THEN 'Admin'
                    ELSE CONCAT(u.first_name, ' ', u.last_name)
                END as sender_name
                FROM messages m
                LEFT JOIN users u ON m.from_user_id = u.id
                WHERE ((m.from_user_id = ? AND m.to_user_id = ?) 
                    OR (m.from_user_id = ? AND m.to_user_id = ?)
                    OR (m.from_user_role = 'system' AND m.to_user_id = ?))
                ORDER BY m.timestamp ASC";
        
        // Debug log the SQL query
        error_log("SQL Query: " . $sql);
        error_log("Parameters: admin_id=$admin_id, user_id=$user_id, user_role=$user_role");
        
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("Failed to prepare statement: " . mysqli_error($conn));
            echo json_encode([
                'success' => false,
                'message' => 'Failed to prepare statement: ' . mysqli_error($conn)
            ]);
            exit;
        }
        
        mysqli_stmt_bind_param($stmt, "iiiii", $admin_id, $user_id, $user_id, $admin_id, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $messages = [];
            
            while ($row = mysqli_fetch_assoc($result)) {
                // Debug log each message
                error_log("Found message: " . json_encode($row));
                
                $messages[] = [
                    'id' => $row['id'],
                    'from_user_id' => $row['from_user_id'],
                    'to_user_id' => $row['to_user_id'],
                    'from_user_role' => $row['from_user_role'],
                    'to_user_role' => $row['to_user_role'],
                    'message' => $row['message'],
                    'is_read' => (bool)$row['is_read'],
                    'timestamp' => $row['timestamp'],
                    'sender_name' => $row['sender_name']
                ];
            }
            
            // Debug log
            error_log("Found " . count($messages) . " messages");
            error_log("Messages data: " . json_encode($messages));
            
            // Mark messages as read
            $update_sql = "UPDATE messages 
                        SET is_read = 1 
                        WHERE to_user_id = ? AND to_user_role = 'admin' AND is_read = 0";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "i", $admin_id);
            mysqli_stmt_execute($update_stmt);
            
            echo json_encode([
                'success' => true,
                'data' => $messages
            ]);
            exit;
        } else {
            error_log("Failed to retrieve messages: " . mysqli_error($conn));
            echo json_encode([
                'success' => false,
                'message' => 'Failed to retrieve messages: ' . mysqli_error($conn)
            ]);
            exit;
        }
    }
    
    // Handle send_message action
    else if ($action === "send_message") {
        // Validate required parameters
        if (!isset($_POST["to_user_id"]) || !isset($_POST["to_user_role"]) || !isset($_POST["message"])) {
            echo json_encode([
                "success" => false,
                "message" => "Missing required parameters"
            ]);
            exit;
        }
        
        // Get parameters
        $to_user_id = $_POST["to_user_id"];
        $to_user_role = $_POST["to_user_role"];
        $message = trim($_POST["message"]);
        
        // Validate message content
        if (empty($message)) {
            echo json_encode([
                "success" => false,
                "message" => "Message cannot be empty"
            ]);
            exit;
        }
        
        // Insert the message
        $sql = "INSERT INTO messages (from_user_id, to_user_id, from_user_role, to_user_role, message, is_read, timestamp) 
                VALUES (?, ?, 'admin', ?, ?, 0, NOW())";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "iiss", $admin_id, $to_user_id, $to_user_role, $message);
            
            if (mysqli_stmt_execute($stmt)) {
                $message_id = mysqli_insert_id($conn);
                
                echo json_encode([
                    "success" => true,
                    "message_id" => $message_id,
                    "timestamp" => date("Y-m-d H:i:s")
                ]);
                exit;
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Failed to send message: " . mysqli_stmt_error($stmt)
                ]);
                exit;
            }
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Failed to prepare statement: " . mysqli_error($conn)
            ]);
            exit;
        }
    }
    
    // Handle check_new_messages action
    else if ($action === "check_new_messages") {
        // Get all doctors
        $doctors_sql = "SELECT d.id as doctor_id, u.id as user_id FROM doctors d JOIN users u ON d.user_id = u.id WHERE u.active = 1";
        $doctors_result = mysqli_query($conn, $doctors_sql);
        $doctors = mysqli_fetch_all($doctors_result, MYSQLI_ASSOC);
        
        // Get all patients
        $patients_sql = "SELECT p.id as patient_id, u.id as user_id FROM patients p JOIN users u ON p.user_id = u.id WHERE u.active = 1";
        $patients_result = mysqli_query($conn, $patients_sql);
        $patients = mysqli_fetch_all($patients_result, MYSQLI_ASSOC);
        
        $new_messages = [];
        
        // Check for new messages from doctors
        foreach ($doctors as $doctor) {
            $doctor_id = $doctor['user_id'];
            
            $count_sql = "SELECT COUNT(*) as count FROM messages 
                        WHERE from_user_id = ? AND to_user_id = ? 
                        AND from_user_role = 'doctor' AND to_user_role = 'admin' 
                        AND is_read = 0";
            
            $count_stmt = mysqli_prepare($conn, $count_sql);
            mysqli_stmt_bind_param($count_stmt, "ii", $doctor_id, $admin_id);
            mysqli_stmt_execute($count_stmt);
            $count_result = mysqli_stmt_get_result($count_stmt);
            $count_data = mysqli_fetch_assoc($count_result);
            
            if ($count_data['count'] > 0) {
                $new_messages[$doctor_id] = [
                    'role' => 'doctor',
                    'count' => $count_data['count']
                ];
            }
        }
        
        // Check for new messages from patients
        foreach ($patients as $patient) {
            $patient_id = $patient['user_id'];
            
            $count_sql = "SELECT COUNT(*) as count FROM messages 
                        WHERE from_user_id = ? AND to_user_id = ? 
                        AND from_user_role = 'patient' AND to_user_role = 'admin' 
                        AND is_read = 0";
            
            $count_stmt = mysqli_prepare($conn, $count_sql);
            mysqli_stmt_bind_param($count_stmt, "ii", $patient_id, $admin_id);
            mysqli_stmt_execute($count_stmt);
            $count_result = mysqli_stmt_get_result($count_stmt);
            $count_data = mysqli_fetch_assoc($count_result);
            
            if ($count_data['count'] > 0) {
                $new_messages[$patient_id] = [
                    'role' => 'patient',
                    'count' => $count_data['count']
                ];
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => $new_messages
        ]);
        exit;
    }
    
    // Handle send_broadcast action
    else if ($action === "send_broadcast") {
        // Validate required parameters
        if (!isset($_POST["type"]) || !isset($_POST["subject"]) || !isset($_POST["message"])) {
            echo json_encode([
                "success" => false,
                "message" => "Missing required parameters"
            ]);
            exit;
        }
        
        $type = $_POST["type"];
        $subject = trim($_POST["subject"]);
        $message = trim($_POST["message"]);
        $schedule = isset($_POST["schedule"]) && !empty($_POST["schedule"]) ? $_POST["schedule"] : null;
        
        // Validate content
        if (empty($subject) || empty($message)) {
            echo json_encode([
                "success" => false,
                "message" => "Subject and message cannot be empty"
            ]);
            exit;
        }
        
        // Get recipients based on type
        $recipients = [];
        
        if ($type === "all" || $type === "doctors") {
            // Get all doctors
            $doctors_sql = "SELECT d.id as doctor_id, u.id as user_id FROM doctors d JOIN users u ON d.user_id = u.id WHERE u.active = 1";
            $doctors_result = mysqli_query($conn, $doctors_sql);
            
            while ($doctor = mysqli_fetch_assoc($doctors_result)) {
                if ($type === "all" || $type === "doctors") {
                    $recipients[] = [
                        'id' => $doctor['user_id'],
                        'role' => 'doctor'
                    ];
                }
            }
        }
        
        if ($type === "all" || $type === "patients") {
            // Get all patients
            $patients_sql = "SELECT p.id as patient_id, u.id as user_id FROM patients p JOIN users u ON p.user_id = u.id WHERE u.active = 1";
            $patients_result = mysqli_query($conn, $patients_sql);
            
            while ($patient = mysqli_fetch_assoc($patients_result)) {
                if ($type === "all" || $type === "patients") {
                    $recipients[] = [
                        'id' => $patient['user_id'],
                        'role' => 'patient'
                    ];
                }
            }
        }
        
        if ($type === "selected" && isset($_POST["recipients"])) {
            // Get selected recipients
            $recipients = json_decode($_POST["recipients"], true);
            
            if (!is_array($recipients) || empty($recipients)) {
                echo json_encode([
                    "success" => false,
                    "message" => "No valid recipients selected"
                ]);
                exit;
            }
        }
        
        // If scheduling for later, store in scheduled_messages table
        if ($schedule) {
            // Check if scheduled_messages table exists, create if not
            $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'scheduled_messages'");
            if (mysqli_num_rows($table_check) == 0) {
                $create_table_sql = "CREATE TABLE scheduled_messages (
                                    id INT AUTO_INCREMENT PRIMARY KEY,
                                    from_user_id INT NOT NULL,
                                    from_user_role VARCHAR(50) NOT NULL,
                                    subject VARCHAR(255) NOT NULL,
                                    message TEXT NOT NULL,
                                    recipients TEXT NOT NULL,
                                    schedule_time DATETIME NOT NULL,
                                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                                )";
                mysqli_query($conn, $create_table_sql);
            }
            
            $recipients_json = json_encode($recipients);
            
            $sql = "INSERT INTO scheduled_messages (from_user_id, from_user_role, subject, message, recipients, schedule_time) 
                    VALUES (?, 'admin', ?, ?, ?, ?)";
            
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "issss", $admin_id, $subject, $message, $recipients_json, $schedule);
                
                if (mysqli_stmt_execute($stmt)) {
                    echo json_encode([
                        "success" => true,
                        "message" => "Message scheduled for " . date('F j, Y, g:i a', strtotime($schedule))
                    ]);
                    exit;
                } else {
                    echo json_encode([
                        "success" => false,
                        "message" => "Failed to schedule message: " . mysqli_stmt_error($stmt)
                    ]);
                    exit;
                }
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Failed to prepare statement: " . mysqli_error($conn)
                ]);
                exit;
            }
        } else {
            // Send messages immediately
            $success_count = 0;
            $failed_count = 0;
            
            $full_message = $subject . "\n\n" . $message;
            
            // Begin transaction
            mysqli_begin_transaction($conn);
            
            try {
                foreach ($recipients as $recipient) {
                    $to_user_id = $recipient['id'];
                    $to_user_role = $recipient['role'];
                    
                    $sql = "INSERT INTO messages (from_user_id, to_user_id, from_user_role, to_user_role, message, is_read, timestamp) 
                            VALUES (?, ?, 'system', ?, ?, 0, NOW())";
                    
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "iiss", $admin_id, $to_user_id, $to_user_role, $full_message);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $success_count++;
                    } else {
                        $failed_count++;
                    }
                }
                
                // Commit transaction
                mysqli_commit($conn);
                
                echo json_encode([
                    "success" => true,
                    "message" => "Broadcast sent successfully to " . $success_count . " recipients." . 
                               ($failed_count > 0 ? " Failed for " . $failed_count . " recipients." : "")
                ]);
                exit;
            } catch (Exception $e) {
                // Rollback transaction on error
                mysqli_rollback($conn);
                
                echo json_encode([
                    "success" => false,
                    "message" => "Error sending broadcast: " . $e->getMessage()
                ]);
                exit;
            }
        }
    }
    
    else {
        echo json_encode([
            "success" => false,
            "message" => "Invalid action"
        ]);
        exit;
    }
}

// Set page title
$page_title = "Messaging";
$current_page = "messaging";

// Get all doctors
$doctors_sql = "SELECT d.id as doctor_id, u.id as user_id, CONCAT(u.first_name, ' ', u.last_name) as name 
                FROM doctors d 
                JOIN users u ON d.user_id = u.id 
                WHERE u.active = 1 
                ORDER BY u.first_name, u.last_name";
$doctors_result = mysqli_query($conn, $doctors_sql);
$doctors = mysqli_fetch_all($doctors_result, MYSQLI_ASSOC);

// Get all patients
$patients_sql = "SELECT p.id as patient_id, u.id as user_id, CONCAT(u.first_name, ' ', u.last_name) as name 
                 FROM patients p 
                 JOIN users u ON p.user_id = u.id 
                 WHERE u.active = 1 
                 ORDER BY u.first_name, u.last_name";
$patients_result = mysqli_query($conn, $patients_sql);
$patients = mysqli_fetch_all($patients_result, MYSQLI_ASSOC);

// Include header
include "includes/header.php";
?>

<!-- Real-time Messaging System -->
<div class="d-flex flex-row justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-comments me-2"></i> Messaging Center</h2>
</div>

<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="messagingTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="doctor-patient-tab" data-bs-toggle="tab" data-bs-target="#doctor-patient" type="button" role="tab" aria-controls="doctor-patient" aria-selected="true">
                    <i class="fas fa-exchange-alt me-2"></i> Doctor-Patient Communication
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="broadcast-tab" data-bs-toggle="tab" data-bs-target="#broadcast" type="button" role="tab" aria-controls="broadcast" aria-selected="false">
                    <i class="fas fa-bullhorn me-2"></i> Broadcast Messages
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content" id="messagingTabsContent">
            <!-- Doctor-Patient Communication Tab -->
            <div class="tab-pane fade show active" id="doctor-patient" role="tabpanel" aria-labelledby="doctor-patient-tab">
                <div class="row">
                    <!-- User Selection Column -->
                    <div class="col-md-4 border-end">
                        <div class="mb-3">
                            <label for="viewSelector" class="form-label">Select View</label>
                            <select class="form-select" id="viewSelector">
                                <option value="doctor">Doctor to Patient</option>
                                <option value="patient">Patient to Doctor</option>
                                <option value="all">All Conversations</option>
                            </select>
                        </div>
                        
                        <!-- Search -->
                        <div class="input-group mb-3">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="userSearch" placeholder="Search users...">
                        </div>
                        
                        <!-- User List -->
                        <div class="user-list-container">
                            <div id="doctorsList" class="mb-3">
                                <h6 class="user-list-header">Doctors</h6>
                                <ul class="list-group user-list doctors-list" id="doctorList">
                                    <?php foreach($doctors as $doctor): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center user-item" 
                                        data-user-id="<?php echo htmlspecialchars($doctor['user_id']); ?>" 
                                        data-role="doctor"
                                        data-name="<?php echo htmlspecialchars($doctor['name']); ?>">
                                        <div>
                                            <i class="fas fa-user-md me-2 text-primary"></i>
                                            <?php echo htmlspecialchars($doctor['name']); ?>
                                        </div>
                                        <span class="badge bg-primary rounded-pill message-count" id="doctor-<?php echo htmlspecialchars($doctor['user_id']); ?>-count">0</span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            
                            <div id="patientsList">
                                <h6 class="user-list-header">Patients</h6>
                                <ul class="list-group user-list patients-list" id="patientList">
                                    <?php foreach($patients as $patient): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center user-item" 
                                        data-user-id="<?php echo htmlspecialchars($patient['user_id']); ?>" 
                                        data-role="patient"
                                        data-name="<?php echo htmlspecialchars($patient['name']); ?>">
                                        <div>
                                            <i class="fas fa-user me-2 text-success"></i>
                                            <?php echo htmlspecialchars($patient['name']); ?>
                                        </div>
                                        <span class="badge bg-success rounded-pill message-count" id="patient-<?php echo htmlspecialchars($patient['user_id']); ?>-count">0</span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Chat Interface Column -->
                    <div class="col-md-8">
                        <div class="chat-container" id="chatContainer">
                            <!-- Chat Header -->
                            <div class="chat-header mb-3 d-none" id="chatHeader">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 id="currentChatName"></h5>
                                        <div id="typingIndicator" class="text-muted d-none">
                                            <small><em>Typing...</em></small>
                                        </div>
                                    </div>
                                    <div>
                                        <button class="btn btn-sm btn-outline-primary" id="refreshChat">
                                            <i class="fas fa-sync-alt"></i> Refresh
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- No Chat Selected Message -->
                            <div class="text-center p-5" id="noChatSelected">
                                <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Select a user to start messaging</h5>
                                <p class="text-muted">You can view and manage conversations between doctors and patients.</p>
                            </div>
                            
                            <!-- Chat Messages Container -->
                            <div class="chat-messages d-none" id="chatMessages">
                                <!-- Messages will be loaded here dynamically -->
                            </div>
                            
                            <!-- Chat Input -->
                            <div class="chat-input mt-3 d-none" id="chatInput">
                                <form id="messageForm">
                                    <input type="hidden" id="toUserId" name="to_user_id" value="">
                                    <input type="hidden" id="toUserRole" name="to_user_role" value="">
                                    <input type="hidden" id="fromUserRole" name="from_user_role" value="admin">
                                    
                                    <div class="d-flex">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="messageInput" name="message" placeholder="Type your message...">
                                            <button class="btn btn-primary" type="submit">
                                                <i class="fas fa-paper-plane"></i> Send
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Broadcast Messages Tab -->
            <div class="tab-pane fade" id="broadcast" role="tabpanel" aria-labelledby="broadcast-tab">
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="broadcastType" class="form-label">Broadcast To</label>
                            <select class="form-select" id="broadcastType">
                                <option value="all">All Users</option>
                                <option value="doctors">All Doctors</option>
                                <option value="patients">All Patients</option>
                                <option value="selected">Selected Users</option>
                            </select>
                        </div>
                        
                        <div id="selectedUsersContainer" class="mb-3 d-none">
                            <label class="form-label">Select Recipients</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Doctors</h6>
                                    <div class="selected-users-list">
                                        <?php foreach($doctors as $doctor): ?>
                                        <div class="form-check">
                                            <input class="form-check-input selected-user" type="checkbox" value="<?php echo $doctor['user_id']; ?>" data-role="doctor" id="doctor-<?php echo $doctor['user_id']; ?>">
                                            <label class="form-check-label" for="doctor-<?php echo $doctor['user_id']; ?>">
                                                <?php echo htmlspecialchars($doctor['name']); ?>
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6>Patients</h6>
                                    <div class="selected-users-list">
                                        <?php foreach($patients as $patient): ?>
                                        <div class="form-check">
                                            <input class="form-check-input selected-user" type="checkbox" value="<?php echo $patient['user_id']; ?>" data-role="patient" id="patient-<?php echo $patient['user_id']; ?>">
                                            <label class="form-check-label" for="patient-<?php echo $patient['user_id']; ?>">
                                                <?php echo htmlspecialchars($patient['name']); ?>
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="broadcastSubject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="broadcastSubject" placeholder="Enter subject...">
                        </div>
                        
                        <div class="mb-3">
                            <label for="broadcastMessage" class="form-label">Message</label>
                            <textarea class="form-control" id="broadcastMessage" rows="5" placeholder="Type your broadcast message..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="broadcastSchedule" class="form-label">Schedule (optional)</label>
                            <input type="datetime-local" class="form-control" id="broadcastSchedule">
                            <small class="text-muted">Leave empty to send immediately</small>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button class="btn btn-secondary me-2" type="button" id="previewBroadcast">
                                <i class="fas fa-eye me-1"></i> Preview
                            </button>
                            <button class="btn btn-primary" type="button" id="sendBroadcast">
                                <i class="fas fa-paper-plane me-1"></i> Send Broadcast
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chat UI Styles -->
<style>
.user-list-container {
    max-height: 600px;
    overflow-y: auto;
}

.user-list-header {
    font-weight: 600;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
    margin-bottom: 10px;
}

.user-list {
    max-height: 300px;
    overflow-y: auto;
    margin-bottom: 20px;
}

.user-item {
    cursor: pointer;
    transition: background-color 0.2s;
}

.user-item:hover {
    background-color: #f8f9fa;
}

.user-item.active {
    background-color: #e9ecef;
    border-left: 3px solid #4e73df;
}

.chat-container {
    display: flex;
    flex-direction: column;
    height: 600px;
    background-color: #f0f2f5;
    border-radius: 10px;
    overflow: hidden;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background-color: #ffffff;
    display: flex;
    flex-direction: column;
}

.message {
    display: flex;
    flex-direction: column;
    max-width: 70%;
    margin-bottom: 15px;
    position: relative;
}

.message-content {
    padding: 12px 16px;
    border-radius: 18px;
    position: relative;
    word-break: break-word;
    font-size: 14px;
}

.message.outgoing {
    align-self: flex-end;
}

.message.incoming {
    align-self: flex-start;
}

.message.outgoing .message-content {
    background-color: #0084ff;
    color: white;
}

.message.incoming .message-content {
    background-color: #e4e6eb;
    color: #050505;
}

.message-role {
    font-size: 12px;
    margin-bottom: 4px;
    color: #65676b;
    padding-left: 16px;
    padding-right: 16px;
}

.message.outgoing .message-role {
    text-align: right;
}

.message-time {
    font-size: 11px;
    margin-top: 4px;
    color: #65676b;
    padding-left: 16px;
    padding-right: 16px;
}

.message.outgoing .message-time {
    text-align: right;
    color: rgba(255, 255, 255, 0.8);
}

/* Scrollbar styling */
.chat-messages::-webkit-scrollbar {
    width: 6px;
}

.chat-messages::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.chat-messages::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.chat-messages::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Message hover effect */
.message:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease;
}

/* Chat container styling */
.chat-container {
    display: flex;
    flex-direction: column;
    height: 600px;
    background-color: #f0f2f5;
    border-radius: 10px;
    overflow: hidden;
}

.chat-header {
    background-color: white;
    padding: 15px 20px;
    border-bottom: 1px solid #e4e6eb;
}

.chat-input {
    background-color: white;
    padding: 15px 20px;
    border-top: 1px solid #e4e6eb;
}

.chat-input .form-control {
    border-radius: 20px;
    padding: 8px 16px;
    border: 1px solid #e4e6eb;
}

.chat-input .btn {
    border-radius: 20px;
    padding: 8px 20px;
    background-color: #0084ff;
    border: none;
}

.chat-input .btn:hover {
    background-color: #0073e6;
}

/* User list styling */
.user-item {
    padding: 12px 15px;
    border-radius: 8px;
    margin-bottom: 5px;
    transition: all 0.2s ease;
}

.user-item:hover {
    background-color: #f0f2f5;
}

.user-item.active {
    background-color: #e7f3ff;
    border-left: 3px solid #0084ff;
}

/* Message count badge */
.message-count {
    background-color: #0084ff;
    color: white;
    padding: 4px 8px;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 600;
}

.selected-users-list {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 10px;
}
</style>

<!-- JavaScript for Real-time Messaging -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentUserId = null;
    let currentUserRole = null;
    let currentChatRefresh = null;
    
    // Handle user item click
    $('.user-item').on('click', function() {
        $('.user-item').removeClass('active');
        $(this).addClass('active');
        
        // Get user data
        currentUserId = $(this).data('user-id');
        currentUserRole = $(this).data('role');
        const userName = $(this).data('name');
        
        // Update UI
        $('#currentChatName').text(userName + ' (' + currentUserRole.charAt(0).toUpperCase() + currentUserRole.slice(1) + ')');
        $('#toUserId').val(currentUserId);
        $('#toUserRole').val(currentUserRole);
        
        // Show chat interface
        $('#noChatSelected').addClass('d-none');
        $('#chatHeader, #chatMessages, #chatInput').removeClass('d-none');
        
        // Load messages
        loadMessages(currentUserId, currentUserRole);
        
        // Reset message count
        $(this).find('.message-count').text('0');
        
        // Start auto-refresh
        if (currentChatRefresh) {
            clearInterval(currentChatRefresh);
        }
        
        currentChatRefresh = setInterval(function() {
            if (currentUserId) {
                loadMessages(currentUserId, currentUserRole, true);
            }
        }, 5000); // Refresh every 5 seconds
    });
    
    // Handle view selector change
    $('#viewSelector').on('change', function() {
        const view = $(this).val();
        
        if (view === 'doctor') {
            $('#doctorsList').show();
            $('#patientsList').hide();
        } else if (view === 'patient') {
            $('#doctorsList').hide();
            $('#patientsList').show();
        } else {
            $('#doctorsList, #patientsList').show();
        }
    });
    
    // Handle user search
    $('#userSearch').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        $('.user-item').each(function() {
            const userName = $(this).data('name');
            if (userName && typeof userName === 'string') {
                if (userName.toLowerCase().includes(searchTerm)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            } else {
                console.warn('User item missing name data:', $(this));
                $(this).hide();
            }
        });
    });
    
    // Handle refresh button
    $('#refreshChat').on('click', function() {
        if (currentUserId) {
            loadMessages(currentUserId, currentUserRole);
        }
    });
    
    // Handle message form submission
    $('#messageForm').on('submit', function(e) {
        e.preventDefault();
        
        const message = $('#messageInput').val().trim();
        if (!message) return;
        
        const toUserId = $('#toUserId').val();
        const toUserRole = $('#toUserRole').val();
        
        // Send message
        $.ajax({
            url: 'messaging.php',
            type: 'POST',
            data: {
                action: 'send_message',
                to_user_id: toUserId,
                message: message,
                to_user_role: toUserRole,
                from_user_role: 'admin'
            },
            success: function(response) {
                if (response.success) {
                    // Clear input
                    $('#messageInput').val('');
                    
                    // Reload messages
                    loadMessages(currentUserId, currentUserRole);
                } else {
                    showError(response.message || 'Failed to send message. Please try again.');
                }
            },
            error: function() {
                showError('Failed to send message. Please check your connection.');
            }
        });
    });
    
    // Load messages function
    function loadMessages(userId, userRole, silent = false) {
        if (!silent) {
            $('#chatMessages').html('<div class="text-center p-3"><i class="fas fa-spinner fa-spin me-2"></i> Loading messages...</div>');
        }
        
        $.ajax({
            url: 'messaging.php',
            type: 'POST',
            data: {
                action: 'get_messages',
                user_id: userId,
                user_role: userRole
            },
            dataType: 'json',
            success: function(response) {
                if (!silent) {
                    $('#chatMessages').empty();
                }
                
                if (response.success && response.data) {
                    const messages = response.data;
                    
                    if (messages.length === 0 && !silent) {
                        $('#chatMessages').html('<div class="text-center p-3 text-muted">No messages yet. Start the conversation!</div>');
                        return;
                    }
                    
                    // If silent update, only add new messages
                    if (silent) {
                        const existingMsgCount = $('#chatMessages .message').length;
                        if (existingMsgCount < messages.length) {
                            for (let i = existingMsgCount; i < messages.length; i++) {
                                appendMessage(messages[i]);
                            }
                        }
                    } else {
                        // Add all messages
                        messages.forEach(appendMessage);
                    }
                    
                    scrollToBottom();
                } else if (!silent) {
                    $('#chatMessages').html('<div class="text-center p-3 text-danger">Error loading messages. Please try again.</div>');
                }
            },
            error: function(xhr, status, error) {
                if (!silent) {
                    $('#chatMessages').html('<div class="text-center p-3 text-danger">Failed to load messages. Please check your connection.</div>');
                }
            }
        });
    }
    
    // Append message to chat
    function appendMessage(message) {
        console.log('Appending message:', message);
        const messageContainer = document.getElementById('chatMessages');
        
        // Create message wrapper
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${message.from_user_role === 'admin' ? 'outgoing' : 'incoming'}`;
        
        // Add sender name
        const roleDisplay = document.createElement('div');
        roleDisplay.className = 'message-role';
        roleDisplay.textContent = message.sender_name || (message.from_user_role === 'admin' ? 'Admin' : message.from_user_role.charAt(0).toUpperCase() + message.from_user_role.slice(1));
        
        // Create message content
        const messageContent = document.createElement('div');
        messageContent.className = 'message-content';
        messageContent.textContent = message.message;
        
        // Add timestamp
        const timeDisplay = document.createElement('div');
        timeDisplay.className = 'message-time';
        timeDisplay.textContent = formatDateTime(message.timestamp);
        
        // Assemble message
        messageDiv.appendChild(roleDisplay);
        messageDiv.appendChild(messageContent);
        messageDiv.appendChild(timeDisplay);
        
        messageContainer.appendChild(messageDiv);
    }
    
    // Format date time
    function formatDateTime(dateTimeStr) {
        const date = new Date(dateTimeStr);
        return date.toLocaleString([], {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    // Scroll chat to bottom
    function scrollToBottom() {
        const chatContainer = document.getElementById('chatMessages');
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }
    
    // Show error message
    function showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: message,
            confirmButtonColor: '#4e73df'
        });
    }
    
    // Handle broadcast type change
    $('#broadcastType').on('change', function() {
        if ($(this).val() === 'selected') {
            $('#selectedUsersContainer').removeClass('d-none');
        } else {
            $('#selectedUsersContainer').addClass('d-none');
        }
    });
    
    // Handle preview broadcast
    $('#previewBroadcast').on('click', function() {
        const subject = $('#broadcastSubject').val().trim();
        const message = $('#broadcastMessage').val().trim();
        
        if (!subject || !message) {
            showError('Please enter both subject and message.');
            return;
        }
        
        Swal.fire({
            title: subject,
            html: '<div class="text-start">' + message.replace(/\n/g, '<br>') + '</div>',
            icon: 'info',
            confirmButtonColor: '#4e73df'
        });
    });
    
    // Handle send broadcast
    $('#sendBroadcast').on('click', function() {
        const broadcastType = $('#broadcastType').val();
        const subject = $('#broadcastSubject').val().trim();
        const message = $('#broadcastMessage').val().trim();
        const schedule = $('#broadcastSchedule').val();
        
        if (!subject || !message) {
            showError('Please enter both subject and message.');
            return;
        }
        
        // Get selected users if applicable
        let selectedUsers = [];
        if (broadcastType === 'selected') {
            $('.selected-user:checked').each(function() {
                selectedUsers.push({
                    id: $(this).val(),
                    role: $(this).data('role')
                });
            });
            
            if (selectedUsers.length === 0) {
                showError('Please select at least one recipient.');
                return;
            }
        }
        
        // Show confirmation dialog
        Swal.fire({
            title: 'Send Broadcast?',
            text: 'This will send the message to ' + getRecipientDescription(broadcastType, selectedUsers),
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#4e73df',
            cancelButtonColor: '#858796',
            confirmButtonText: 'Yes, Send It',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Prepare data
                const data = {
                    action: 'send_broadcast',
                    type: broadcastType,
                    subject: subject,
                    message: message,
                    schedule: schedule
                };
                
                if (broadcastType === 'selected') {
                    data.recipients = JSON.stringify(selectedUsers);
                }
                
                // Send broadcast
                $.ajax({
                    url: 'messaging.php',
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Broadcast Sent',
                                text: 'Your message has been sent successfully!',
                                confirmButtonColor: '#4e73df'
                            }).then(() => {
                                // Clear form
                                $('#broadcastSubject, #broadcastMessage, #broadcastSchedule').val('');
                                $('.selected-user').prop('checked', false);
                            });
                        } else {
                            showError(response.message || 'Failed to send broadcast. Please try again.');
                        }
                    },
                    error: function() {
                        showError('Failed to send broadcast. Please check your connection.');
                    }
                });
            }
        });
    });
    
    // Get recipient description for confirmation message
    function getRecipientDescription(type, selectedUsers) {
        switch (type) {
            case 'all':
                return 'all users (doctors and patients)';
            case 'doctors':
                return 'all doctors';
            case 'patients':
                return 'all patients';
            case 'selected':
                const doctorCount = selectedUsers.filter(u => u.role === 'doctor').length;
                const patientCount = selectedUsers.filter(u => u.role === 'patient').length;
                
                let description = '';
                if (doctorCount > 0) {
                    description += doctorCount + ' doctor' + (doctorCount > 1 ? 's' : '');
                }
                
                if (patientCount > 0) {
                    if (description) description += ' and ';
                    description += patientCount + ' patient' + (patientCount > 1 ? 's' : '');
                }
                
                return description;
        }
    }
    
    // Check for new messages periodically
    function checkNewMessages() {
        $.ajax({
            url: 'messaging.php',
            type: 'POST',
            data: {
                action: 'check_new_messages'
            },
            success: function(response) {
                if (response.success) {
                    const newMessages = response.data;
                    
                    // Update message counters
                    for (const userId in newMessages) {
                        const role = newMessages[userId].role;
                        const count = newMessages[userId].count;
                        
                        // Skip current chat
                        if (currentUserId === userId) continue;
                        
                        // Update counter
                        $(`#${role}-${userId}-count`).text(count);
                    }
                }
            }
        });
    }
    
    // Start checking for new messages
    setInterval(checkNewMessages, 10000); // Check every 10 seconds
    
    // Clean up on page unload
    $(window).on('beforeunload', function() {
        if (currentChatRefresh) {
            clearInterval(currentChatRefresh);
        }
    });
});
</script>

<?php
// Include footer
include "includes/footer.php";
?> 