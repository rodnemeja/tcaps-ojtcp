<?php
session_start();
require_once "config/database.php";

// Check if user is logged in and is a doctor
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "doctor"){
    header("location: index.php");
    exit;
}

$user_id = $_SESSION["id"];
$role = $_SESSION["role"];

// Get doctor info
$doctor_sql = "SELECT d.*, u.first_name, u.last_name, u.email, u.phone 
               FROM doctors d 
               JOIN users u ON d.user_id = u.id 
               WHERE d.user_id = ?";
$stmt = mysqli_prepare($conn, $doctor_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$doctor_result = mysqli_stmt_get_result($stmt);
$doctor = mysqli_fetch_assoc($doctor_result);

// Handle AJAX requests for messaging
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
    
    // Handle get_messages action
    if ($action === "get_messages") {
        // Get recipient info from POST data
        $recipient_role = $_POST["recipient_role"] ?? 'admin';
        $recipient_id = $_POST["recipient_id"] ?? 1;
        
        // Get messages between the doctor and the selected recipient
        $sql = "SELECT m.*, 
                CASE 
                    WHEN m.from_user_role = 'patient' THEN CONCAT(pu.first_name, ' ', pu.last_name)
                    WHEN m.from_user_role = 'admin' THEN 'Admin'
                    WHEN m.from_user_role = 'doctor' THEN CONCAT('Dr. ', du.first_name, ' ', du.last_name)
                    WHEN m.from_user_role = 'system' THEN 'System'
                END as sender_name
                FROM messages m
                LEFT JOIN users pu ON m.from_user_id = pu.id AND m.from_user_role = 'patient'
                LEFT JOIN doctors d ON d.user_id = ?
                LEFT JOIN users du ON d.user_id = du.id
                WHERE (
                    (m.from_user_id = ? AND m.from_user_role = 'doctor' AND m.to_user_id = ? AND m.to_user_role = ?)
                    OR 
                    (m.to_user_id = d.id AND m.to_user_role = 'doctor' AND m.from_user_id = ? AND m.from_user_role = ?)
                )
                ORDER BY m.timestamp ASC";
        
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to prepare statement: ' . mysqli_error($conn)
            ]);
            exit;
        }
        
        // For patient messages, we need to use the patient's user_id
        if ($recipient_role === 'patient') {
            // Get the patient's user_id from the patients table
            $patient_sql = "SELECT user_id FROM patients WHERE id = ?";
            $patient_stmt = mysqli_prepare($conn, $patient_sql);
            mysqli_stmt_bind_param($patient_stmt, "i", $recipient_id);
            mysqli_stmt_execute($patient_stmt);
            $patient_result = mysqli_stmt_get_result($patient_stmt);
            $patient = mysqli_fetch_assoc($patient_result);
            
            if ($patient) {
                $recipient_id = $patient['user_id'];
            }
        }
        
        mysqli_stmt_bind_param($stmt, "iiisis", 
            $user_id, // For doctor's user_id in JOIN
            $user_id, // For messages sent by doctor
            $recipient_id, $recipient_role,  // For recipient
            $recipient_id, $recipient_role   // For messages received by doctor
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $messages = [];
            
            while ($row = mysqli_fetch_assoc($result)) {
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
            
            // Mark messages as read
            $update_sql = "UPDATE messages 
                       SET is_read = 1 
                       WHERE to_user_id = ? AND to_user_role = 'doctor' AND is_read = 0";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "i", $user_id);
            mysqli_stmt_execute($update_stmt);
            
            echo json_encode([
                'success' => true,
                'data' => $messages
            ]);
            exit;
        } else {
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
        if (!isset($_POST["to_user_role"]) || !isset($_POST["message"])) {
            echo json_encode([
                "success" => false,
                "message" => "Missing required parameters"
            ]);
            exit;
        }
        
        // Get parameters
        $to_user_role = $_POST["to_user_role"];
        $to_user_id = isset($_POST["to_user_id"]) ? intval($_POST["to_user_id"]) : 0;
        $message = trim($_POST["message"]);
        
        // Validate message content
        if (empty($message)) {
            echo json_encode([
                "success" => false,
                "message" => "Message cannot be empty"
            ]);
            exit;
        }
        
        // If sending to a patient, validate to_user_id
        if ($to_user_role === "patient" && $to_user_id === 0) {
            echo json_encode([
                "success" => false,
                "message" => "Patient must be selected"
            ]);
            exit;
        }
        
        // If sending to admin, use ID 1 (main admin)
        if ($to_user_role === "admin") {
             $to_user_id = 1;
        }
        
        // Get doctor's ID from doctors table
        $doctor_sql = "SELECT id FROM doctors WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $doctor_sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $doctor_result = mysqli_stmt_get_result($stmt);
        $doctor = mysqli_fetch_assoc($doctor_result);
        $doctor_id = $doctor['id'];
        
        // Insert the message
        $sql = "INSERT INTO messages (from_user_id, to_user_id, from_user_role, to_user_role, message, is_read) 
                VALUES (?, ?, 'doctor', ?, ?, 0)";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "iiss", $user_id, $to_user_id, $to_user_role, $message);
            
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
    
    // Invalid action
    echo json_encode([
        "success" => false,
        "message" => "Invalid action"
    ]);
    exit;
}

// Get the list of patients for sending messages
$patients_sql = "SELECT p.id as patient_id, p.user_id, CONCAT(u.first_name, ' ', u.last_name) as patient_name
                 FROM patients p
                 JOIN users u ON p.user_id = u.id
                 WHERE u.active = 1
                 ORDER BY patient_name";

// Use prepared statement for better security
$stmt = mysqli_prepare($conn, $patients_sql);
if (!$stmt) {
    error_log("Error preparing patients query: " . mysqli_error($conn));
    $patients = [];
} else {
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $patients = mysqli_fetch_all($result, MYSQLI_ASSOC);
    } else {
        error_log("Error executing patients query: " . mysqli_stmt_error($stmt));
        $patients = [];
    }
    mysqli_stmt_close($stmt);
}

// Page title for header
$page_title = "Messages";
?>

<?php include "includes/header.php"; ?>

<style>
.chat-container {
    height: calc(100vh - 80px);
    display: flex;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    margin: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.chat-list {
    width: 280px;
    border-right: 1px solid #ebeef2;
    background: #fff;
    overflow-y: auto;
    padding: 0;
}

.chat-list-header {
    padding: 20px;
    border-bottom: 1px solid #ebeef2;
}

.chat-list-search {
    position: relative;
    margin-bottom: 15px;
}

.chat-list-search input {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid #ebeef2;
    border-radius: 8px;
    font-size: 14px;
}

.chat-list-search i {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #8e9297;
}

.chat-item {
    padding: 12px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    transition: background-color 0.2s;
    border-bottom: 1px solid #ebeef2;
}

.chat-item:hover {
    background: #f8f9fa;
}

.chat-item.active {
    background: #6c5ce7;
    color: white;
}

.chat-item-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #ebeef2;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 500;
}

.chat-item-info {
    flex: 1;
}

.chat-item-name {
    font-weight: 500;
    font-size: 14px;
    margin-bottom: 4px;
}

.chat-item-preview {
    font-size: 12px;
    color: #8e9297;
}

.chat-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #fff;
}

.chat-header {
    padding: 20px;
    background: #fff;
    border-bottom: 1px solid #ebeef2;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.chat-header-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.chat-header-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #ebeef2;
    display: flex;
    align-items: center;
    justify-content: center;
}

.chat-header-name {
    font-weight: 600;
    font-size: 16px;
}

.chat-header-status {
    font-size: 12px;
    color: #43b581;
}

.chat-header-actions {
    display: flex;
    gap: 15px;
}

.chat-header-actions button {
    background: none;
    border: none;
    color: #8e9297;
    cursor: pointer;
    padding: 8px;
    border-radius: 4px;
}

.chat-header-actions button:hover {
    background: #f8f9fa;
}

.messages-container {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background: #fff;
}

.message {
    margin-bottom: 20px;
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

.message.sent {
    flex-direction: row-reverse;
}

.message-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #ebeef2;
    flex-shrink: 0;
}

.message-content {
    max-width: 70%;
    padding: 12px 16px;
    border-radius: 12px;
    font-size: 14px;
    line-height: 1.4;
}

.message.sent .message-content {
    background: #6c5ce7;
    color: white;
}

.message.received .message-content {
    background: #f8f9fa;
    color: #2f3136;
}

.message-time {
    font-size: 11px;
    color: #8e9297;
    margin-top: 4px;
}

.message-input-container {
    padding: 20px;
    background: #fff;
    border-top: 1px solid #ebeef2;
}

.message-form {
    display: flex;
    gap: 12px;
    align-items: center;
}

.message-input {
    flex: 1;
    padding: 12px 16px;
    border: 1px solid #ebeef2;
    border-radius: 8px;
    resize: none;
    font-size: 14px;
    line-height: 1.4;
    max-height: 120px;
    min-height: 44px;
}

.message-input:focus {
    outline: none;
    border-color: #6c5ce7;
}

.send-button {
    background: #6c5ce7;
    color: white;
    border: none;
    border-radius: 8px;
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.2s;
}

.send-button:hover {
    background: #5b4cc4;
}

.send-button:disabled {
    background: #ebeef2;
    cursor: not-allowed;
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: transparent;
}

::-webkit-scrollbar-thumb {
    background: #ebeef2;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: #d4d7dc;
}
</style>

<div class="container-fluid p-0">
    <div class="chat-container">
        <!-- Chat List -->
        <div class="chat-list">
            <div class="chat-list-header">
                <h5 class="mb-3">Messages</h5>
                <div class="chat-list-search">
                    <input type="text" placeholder="Search..." />
                    <i class="fas fa-search"></i>
                </div>
            </div>
            <div class="chat-item active" data-role="admin" data-id="1">
                <div class="chat-item-avatar">A</div>
                <div class="chat-item-info">
                    <div class="chat-item-name">Admin</div>
                    <div class="chat-item-preview">Click to view messages</div>
                </div>
            </div>
            <?php foreach($patients as $patient): ?>
            <div class="chat-item" data-role="patient" data-id="<?php echo $patient['user_id']; ?>">
                <div class="chat-item-avatar"><?php echo substr($patient['patient_name'], 0, 1); ?></div>
                <div class="chat-item-info">
                    <div class="chat-item-name"><?php echo htmlspecialchars($patient['patient_name']); ?></div>
                    <div class="chat-item-preview">Click to view messages</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Chat Main -->
        <div class="chat-main">
            <!-- Chat Header -->
            <div class="chat-header">
                <div class="chat-header-info">
                    <div class="chat-header-avatar">A</div>
                    <div>
                        <div class="chat-header-name">Admin</div>
                        <div class="chat-header-status">Online</div>
                    </div>
                </div>
                <div class="chat-header-actions">
                    <button><i class="fas fa-phone"></i></button>
                    <button><i class="fas fa-video"></i></button>
                    <button><i class="fas fa-ellipsis-v"></i></button>
                </div>
            </div>
            
            <!-- Messages container -->
            <div id="messagesContainer" class="messages-container">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading messages...</p>
                </div>
            </div>
            
            <!-- Message input -->
            <div class="message-input-container">
                <form id="messageForm" class="message-form">
                    <textarea class="message-input" id="messageText" name="message" placeholder="Type a message..." required></textarea>
                    <button type="submit" class="send-button">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const messagesContainer = document.getElementById('messagesContainer');
    const messageForm = document.getElementById('messageForm');
    const chatItems = document.querySelectorAll('.chat-item');
    const chatHeader = document.querySelector('.chat-header');
    const messageText = document.getElementById('messageText');
    let currentRecipient = { role: 'admin', id: 1 };
    let displayedMessageIds = new Set();
    
    // Load messages initially
    loadMessages();
    
    // Refresh messages every 2 seconds
    setInterval(loadMessages, 2000);
    
    // Handle chat item selection
    chatItems.forEach(item => {
        item.addEventListener('click', function() {
            // Update active state
            chatItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            
            // Update current recipient
            currentRecipient = {
                role: this.dataset.role,
                id: parseInt(this.dataset.id)
            };
            
            // Reset message tracking
            displayedMessageIds.clear();
            messagesContainer.innerHTML = '';
            
            // Update header
            const name = this.querySelector('.chat-item-name').textContent;
            const role = this.dataset.role === 'admin' ? 'Administrator' : 'Patient';
            chatHeader.innerHTML = `
                <div class="chat-header-info">
                    <div class="chat-header-avatar">${name.charAt(0)}</div>
                    <div>
                        <div class="chat-header-name">${name}</div>
                        <div class="chat-header-status">${role}</div>
                    </div>
                </div>
                <div class="chat-header-actions">
                    <button><i class="fas fa-phone"></i></button>
                    <button><i class="fas fa-video"></i></button>
                    <button><i class="fas fa-ellipsis-v"></i></button>
                </div>
            `;
            
            // Load messages for selected recipient
            loadMessages();
        });
    });
    
    // Handle form submission
    messageForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const message = messageText.value.trim();
        
        if (!message) {
            alert('Please enter a message');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'send_message');
        formData.append('to_user_role', currentRecipient.role);
        formData.append('to_user_id', currentRecipient.id);
        formData.append('message', message);
        
        // Disable submit button to prevent double submission
        const submitButton = this.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageText.value = '';
                messageText.style.height = '44px';
                loadMessages(); // Immediately load messages after sending
            } else {
                alert(data.message || 'Failed to send message');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to send message');
        })
        .finally(() => {
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fas fa-paper-plane"></i>';
        });
    });
    
    function loadMessages() {
        const formData = new FormData();
        formData.append('action', 'get_messages');
        formData.append('recipient_role', currentRecipient.role);
        formData.append('recipient_id', currentRecipient.id);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                // Filter out already displayed messages
                const newMessages = data.data.filter(msg => !displayedMessageIds.has(msg.id));
                if (newMessages.length > 0) {
                    displayMessages(newMessages);
                }
            } else {
                console.error('Failed to load messages:', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
    
    function displayMessages(messages) {
        // If this is the first load, clear the container
        if (displayedMessageIds.size === 0) {
            messagesContainer.innerHTML = '';
        }
        
        messages.forEach(message => {
            // Skip if already displayed
            if (displayedMessageIds.has(message.id)) return;
            
            // Add to tracking set
            displayedMessageIds.add(message.id);
            
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${message.from_user_role === 'doctor' ? 'sent' : 'received'}`;
            messageDiv.setAttribute('data-message-id', message.id);
            
            const avatar = document.createElement('div');
            avatar.className = 'message-avatar';
            avatar.textContent = message.sender_name.charAt(0);
            
            const contentWrapper = document.createElement('div');
            contentWrapper.className = 'message-wrapper';
            
            const senderName = document.createElement('div');
            senderName.className = 'message-sender';
            senderName.textContent = message.sender_name;
            
            const messageContent = document.createElement('div');
            messageContent.className = 'message-content';
            messageContent.textContent = message.message;
            
            const timestamp = document.createElement('div');
            timestamp.className = 'message-time';
            timestamp.textContent = new Date(message.timestamp).toLocaleString();
            
            contentWrapper.appendChild(senderName);
            contentWrapper.appendChild(messageContent);
            contentWrapper.appendChild(timestamp);
            
            if (message.from_user_role === 'doctor') {
                messageDiv.appendChild(contentWrapper);
                messageDiv.appendChild(avatar);
            } else {
                messageDiv.appendChild(avatar);
                messageDiv.appendChild(contentWrapper);
            }
            
            messagesContainer.appendChild(messageDiv);
        });
        
        // Scroll to bottom
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    // Auto-resize textarea
    messageText.addEventListener('input', function() {
        this.style.height = '44px';
        this.style.height = (this.scrollHeight) + 'px';
    });
});
</script>

<?php include "includes/footer.php"; ?>
