<?php
session_start();
require_once "config/database.php";

// Check if user is logged in and is a patient
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "patient"){
    header("location: index.php");
    exit;
}

$user_id = $_SESSION["id"];
$role = $_SESSION["role"];

// Get patient info
$patient_sql = "SELECT p.*, u.first_name, u.last_name, u.email, u.phone 
               FROM patients p 
               JOIN users u ON p.user_id = u.id 
               WHERE p.user_id = ?";
$stmt = mysqli_prepare($conn, $patient_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$patient_result = mysqli_stmt_get_result($stmt);
$patient = mysqli_fetch_assoc($patient_result);

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
        // Get all messages between the patient and admins/doctors
        $sql = "SELECT m.*, 
                CASE 
                    WHEN m.from_user_role = 'patient' THEN CONCAT(pu.first_name, ' ', pu.last_name)
                    WHEN m.from_user_role = 'admin' THEN 'Admin'
                    WHEN m.from_user_role = 'doctor' THEN CONCAT('Dr. ', du.first_name, ' ', du.last_name)
                    WHEN m.from_user_role = 'system' THEN 'System'
                END as sender_name
                FROM messages m
                LEFT JOIN users pu ON m.from_user_id = pu.id AND m.from_user_role = 'patient'
                LEFT JOIN doctors d ON m.from_user_id = d.user_id AND m.from_user_role = 'doctor'
                LEFT JOIN users du ON d.user_id = du.id
                WHERE (m.to_user_id = ? AND m.to_user_role = 'patient')
                   OR (m.from_user_id = ? AND m.from_user_role = 'patient')
                ORDER BY m.timestamp ASC";
        
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to prepare statement: ' . mysqli_error($conn)
            ]);
            exit;
        }
        
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);
        
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
                       WHERE to_user_id = ? AND to_user_role = 'patient' AND is_read = 0";
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
        
        // If sending to a doctor, validate to_user_id
        if ($to_user_role === "doctor" && $to_user_id === 0) {
            echo json_encode([
                "success" => false,
                "message" => "Doctor must be selected"
            ]);
            exit;
        }
        
        // If sending to admin, use ID 1 (main admin)
        if ($to_user_role === "admin") {
            $to_user_id = 1; // Assuming ID 1 is the main admin
        }
        
        // Insert the message
        $sql = "INSERT INTO messages (from_user_id, to_user_id, from_user_role, to_user_role, message, is_read) 
                VALUES (?, ?, 'patient', ?, ?, 0)";
        
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

// Get the list of doctors for sending messages
$doctors_sql = "SELECT d.id as doctor_id, CONCAT(u.first_name, ' ', u.last_name) as doctor_name, d.specialization
               FROM doctors d
               JOIN users u ON d.user_id = u.id
               WHERE d.status = 'active'
               ORDER BY doctor_name";
$doctors_result = mysqli_query($conn, $doctors_sql);
$doctors = mysqli_fetch_all($doctors_result, MYSQLI_ASSOC);

// Page title for header
$page_title = "Messages";
?>

<?php include "includes/header.php"; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Removed sidebar include -->
        
        <!-- Main Content -->
        <div class="col-md-12 py-4 px-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3"><i class="fas fa-envelope me-2"></i>Messages</h1>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-0">
                            <div class="row g-0">
                                <!-- Message content area -->
                                <div class="col-12">
                                    <div class="d-flex flex-column h-100">
                                        <!-- Messages container -->
                                        <div id="messagesContainer" class="border-bottom p-3" style="height: 400px; overflow-y: auto;">
                                            <div class="text-center py-5">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <p class="mt-2">Loading messages...</p>
                                            </div>
                                        </div>
                                        
                                        <!-- Message compose form -->
                                        <div class="p-3 bg-light">
                                            <form id="messageForm" class="mb-0">
                                                <div class="mb-3">
                                                    <label class="form-label">Send to:</label>
                                                    <div class="d-flex">
                                                        <div class="form-check me-3">
                                                            <input class="form-check-input" type="radio" name="recipient" id="adminRecipient" value="admin" checked>
                                                            <label class="form-check-label" for="adminRecipient">
                                                                Admin
                                                            </label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="recipient" id="doctorRecipient" value="doctor">
                                                            <label class="form-check-label" for="doctorRecipient">
                                                                Doctor
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div id="doctorSelectContainer" class="mb-3 d-none">
                                                    <select class="form-select" id="doctorSelect">
                                                        <option value="">Select a doctor</option>
                                                        <?php foreach ($doctors as $doctor): ?>
                                                        <option value="<?php echo $doctor['doctor_id']; ?>">
                                                            Dr. <?php echo htmlspecialchars($doctor['doctor_name']); ?> - <?php echo htmlspecialchars($doctor['specialization']); ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="input-group">
                                                    <textarea id="messageInput" class="form-control" placeholder="Type your message here..." rows="3" required></textarea>
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-paper-plane me-1"></i> Send
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load messages immediately
    loadMessages();
    
    // Toggle doctor selection based on recipient type
    const recipientRadios = document.querySelectorAll('input[name="recipient"]');
    const doctorSelectContainer = document.getElementById('doctorSelectContainer');
    
    recipientRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'doctor') {
                doctorSelectContainer.classList.remove('d-none');
            } else {
                doctorSelectContainer.classList.add('d-none');
            }
        });
    });
    
    // Handle message submission
    const messageForm = document.getElementById('messageForm');
    const messageInput = document.getElementById('messageInput');
    const doctorSelect = document.getElementById('doctorSelect');
    
    // Add event listener to detect when user is typing
    messageInput.addEventListener('keydown', function(e) {
        // If Enter is pressed without Shift, submit the form
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            messageForm.dispatchEvent(new Event('submit'));
        }
    });
    
    messageForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Get selected recipient
        const recipientType = document.querySelector('input[name="recipient"]:checked').value;
        let recipientId = 0;
        
        if (recipientType === 'doctor') {
            recipientId = doctorSelect.value;
            if (!recipientId) {
                showAlert('Please select a doctor', 'warning');
                return;
            }
        }
        
        const message = messageInput.value.trim();
        if (!message) {
            showAlert('Please enter a message', 'warning');
            return;
        }
        
        // Show sending indicator
        const submitBtn = messageForm.querySelector('button[type="submit"]');
        const originalBtnHtml = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';
        
        // Optimistic UI update - add message to UI immediately before server responds
        addOptimisticMessage(message);
        
        // Send message via AJAX
        const formData = new FormData();
        formData.append('action', 'send_message');
        formData.append('to_user_role', recipientType);
        formData.append('to_user_id', recipientId);
        formData.append('message', message);
        
        fetch('messaging.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Clear form
                messageInput.value = '';
                
                // Reload messages after a short delay to ensure the message is in the database
                setTimeout(() => {
                    loadMessages();
                }, 200);
                
                // Notify user of successful send
                playMessageSound('sent');
            } else {
                showAlert(data.message || 'Failed to send message', 'danger');
                // Remove optimistic message if failed
                removeOptimisticMessage();
            }
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHtml;
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred. Please try again.', 'danger');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHtml;
            // Remove optimistic message if failed
            removeOptimisticMessage();
        });
    });
    
    // Set up super-fast message checking (every 2 seconds)
    let messageRefreshInterval = setInterval(checkForNewMessages, 2000);
    
    // Check if the page is visible or hidden
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            // Page is hidden, increase refresh interval to save resources
            clearInterval(messageRefreshInterval);
            messageRefreshInterval = setInterval(checkForNewMessages, 15000); // 15 seconds when inactive
        } else {
            // Page is visible, use faster refresh rate
            clearInterval(messageRefreshInterval);
            messageRefreshInterval = setInterval(checkForNewMessages, 2000); // 2 seconds when active
            loadMessages(); // Immediately refresh when page becomes visible
        }
    });
    
    // Request notification permission on page load
    requestNotificationPermission();
    
    // Make sure we always clean up intervals when the page is unloaded
    window.addEventListener('beforeunload', function() {
        clearInterval(messageRefreshInterval);
    });
    
    // Add focus to message input when page loads
    setTimeout(() => {
        messageInput.focus();
    }, 500);
});

let lastMessageCount = 0;
let lastMessageId = 0;
let optimisticMessageAdded = false;

// Function to add a temporary message to the UI before server confirmation
function addOptimisticMessage(messageText) {
    const messagesContainer = document.getElementById('messagesContainer');
    
    // Create optimistic message element
    const optimisticMessage = document.createElement('div');
    optimisticMessage.id = 'optimistic-message';
    optimisticMessage.className = 'message-bubble mb-3 align-self-end';
    optimisticMessage.style.maxWidth = '75%';
    optimisticMessage.style.opacity = '0.7';
    
    const time = new Date().toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
    
    optimisticMessage.innerHTML = `
        <div class="d-flex flex-column">
            <small class="text-light">You (sending...)</small>
            <div class="p-3 rounded shadow-sm bg-primary text-white" style="border-top-right-radius: 0">
                ${messageText.replace(/\n/g, '<br>')}
            </div>
            <small class="text-muted align-self-end mt-1">
                ${time}
            </small>
        </div>
    `;
    
    messagesContainer.appendChild(optimisticMessage);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    optimisticMessageAdded = true;
}

// Function to remove the optimistic message if the send fails
function removeOptimisticMessage() {
    if (optimisticMessageAdded) {
        const optimisticMessage = document.getElementById('optimistic-message');
        if (optimisticMessage) {
            optimisticMessage.remove();
        }
        optimisticMessageAdded = false;
    }
}

// Function to check for new messages without replacing the entire conversation
function checkForNewMessages() {
    // Use a timestamp to prevent browser caching
    const timestamp = new Date().getTime();
    
    fetch(`messaging.php?cache=${timestamp}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'action=get_messages'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const messages = data.data;
            
            // Check if there are new messages
            if (messages.length > lastMessageCount) {
                // Find the newest message
                let newestMessage = messages[messages.length - 1];
                
                // Only notify and refresh if it's truly a new message
                if (newestMessage.id > lastMessageId) {
                    // Remove optimistic message if it exists
                    if (optimisticMessageAdded) {
                        removeOptimisticMessage();
                    }
                    
                    // Play notification sound if the new message is from someone else
                    if (newestMessage.from_user_role !== 'patient') {
                        playMessageSound('received');
                        showNotification('New message from ' + newestMessage.sender_name, newestMessage.message);
                    }
                    
                    // Update the UI with new messages
                    displayMessages(messages);
                    lastMessageId = newestMessage.id;
                }
            }
            
            lastMessageCount = messages.length;
        }
    })
    .catch(error => {
        console.error('Error checking for new messages:', error);
    });
}

// Function to request notification permission
function requestNotificationPermission() {
    if ("Notification" in window && Notification.permission !== "granted" && Notification.permission !== "denied") {
        // Show a small prompt to request notification permission
        const permissionPrompt = document.createElement('div');
        permissionPrompt.className = 'alert alert-info alert-dismissible fade show';
        permissionPrompt.style.position = 'fixed';
        permissionPrompt.style.bottom = '20px';
        permissionPrompt.style.right = '20px';
        permissionPrompt.style.maxWidth = '350px';
        permissionPrompt.style.zIndex = '9999';
        
        permissionPrompt.innerHTML = `
            <strong>Enable notifications?</strong> Get notified when new messages arrive.
            <div class="mt-2">
                <button id="enable-notifications" class="btn btn-sm btn-primary me-2">Enable</button>
                <button class="btn btn-sm btn-light" data-bs-dismiss="alert">Not now</button>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        document.body.appendChild(permissionPrompt);
        
        document.getElementById('enable-notifications').addEventListener('click', function() {
            Notification.requestPermission();
            permissionPrompt.remove();
        });
    }
}

// Function to load messages
function loadMessages() {
    // Show loading if this is the first load
    if (lastMessageCount === 0) {
        document.getElementById('messagesContainer').innerHTML = `
            <div class="text-center py-3">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="small mt-1 mb-0">Loading messages...</p>
            </div>
        `;
    }
    
    // Use a timestamp to prevent browser caching
    const timestamp = new Date().getTime();
    
    fetch(`messaging.php?cache=${timestamp}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'action=get_messages'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove optimistic message if it exists
            if (optimisticMessageAdded) {
                removeOptimisticMessage();
            }
            
            displayMessages(data.data);
            
            // Update our trackers for message count and latest ID
            if (data.data.length > 0) {
                lastMessageCount = data.data.length;
                lastMessageId = data.data[data.data.length - 1].id;
            }
        } else {
            document.getElementById('messagesContainer').innerHTML = `
                <div class="alert alert-danger m-3">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    ${data.message || 'Failed to load messages'}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('messagesContainer').innerHTML = `
            <div class="alert alert-danger m-3">
                <i class="fas fa-exclamation-circle me-2"></i>
                An error occurred. Please refresh the page.
            </div>
        `;
    });
}

// Function to play notification sounds
function playMessageSound(type) {
    const soundUrl = type === 'sent' ? 
        'data:audio/wav;base64,UklGRiQAAABXQVZFZm10IBAAAAABAAEARKwAAIhYAQACABAAZGF0YQAAAAA=' : // Short beep
        'data:audio/wav;base64,UklGRigAAABXQVZFZm10IBAAAAABAAEARKwAAIhYAQACABAAZGF0YQAAAAAAAAA='; // Notification sound
    
    // Create audio element
    const audio = new Audio(soundUrl);
    audio.volume = 0.5;
    
    // Play sound if browser allows it
    const playPromise = audio.play();
    if (playPromise !== undefined) {
        playPromise.catch(error => {
            console.log('Auto-play prevented:', error);
        });
    }
}

// Show browser notification for new messages
function showNotification(title, body) {
    // Check if browser supports notifications
    if (!("Notification" in window)) {
        return;
    }
    
    // Check if permission is already granted
    if (Notification.permission === "granted") {
        createNotification(title, body);
    }
    // Otherwise, request permission
    else if (Notification.permission !== "denied") {
        Notification.requestPermission().then(permission => {
            if (permission === "granted") {
                createNotification(title, body);
            }
        });
    }
}

// Create and show the notification
function createNotification(title, body) {
    const notification = new Notification(title, {
        body: body,
        icon: 'assets/img/favicon.png'
    });
    
    // Close notification after 5 seconds
    setTimeout(() => {
        notification.close();
    }, 5000);
    
    // Handle click on notification
    notification.onclick = function() {
        window.focus();
        this.close();
    };
}

// Function to display messages
function displayMessages(messages) {
    const container = document.getElementById('messagesContainer');
    
    if (messages.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-envelope-open fa-3x text-muted mb-3"></i>
                <p class="mb-0">No messages yet. Send a message to get started.</p>
            </div>
        `;
        return;
    }
    
    // Group messages by date
    const groupedMessages = groupMessagesByDate(messages);
    
    let html = '';
    
    for (const date in groupedMessages) {
        html += `
            <div class="message-date-separator">
                <span class="badge bg-light text-dark px-3 py-2 shadow-sm">${date}</span>
            </div>
        `;
        
        groupedMessages[date].forEach(message => {
            const isFromMe = message.from_user_role === 'patient';
            const messageClass = isFromMe ? 'bg-primary text-white' : 'bg-light';
            const alignClass = isFromMe ? 'align-self-end' : 'align-self-start';
            const bubbleStyle = isFromMe ? 'border-top-right-radius: 0' : 'border-top-left-radius: 0';
            
            html += `
                <div class="message-bubble mb-3 ${alignClass}" style="max-width: 75%;">
                    <div class="d-flex flex-column">
                        <small class="text-${isFromMe ? 'light' : 'muted'}">${message.sender_name}</small>
                        <div class="p-3 rounded shadow-sm ${messageClass}" style="${bubbleStyle}">
                            ${formatMessageText(message.message)}
                        </div>
                        <small class="text-muted align-self-${isFromMe ? 'end' : 'start'} mt-1">
                            ${formatTime(message.timestamp)}
                        </small>
                    </div>
                </div>
            `;
        });
    }
    
    // Keep track of the previous scroll position and whether we were at the bottom
    const wasAtBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 50;
    
    container.innerHTML = html;
    
    // If we were at the bottom before, scroll to bottom again
    if (wasAtBottom) {
        container.scrollTop = container.scrollHeight;
    }
}

// Group messages by date
function groupMessagesByDate(messages) {
    const groups = {};
    
    messages.forEach(message => {
        const date = new Date(message.timestamp);
        const dateStr = formatDate(date);
        
        if (!groups[dateStr]) {
            groups[dateStr] = [];
        }
        
        groups[dateStr].push(message);
    });
    
    return groups;
}

// Format date for grouping
function formatDate(date) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    
    const messageDate = new Date(date);
    messageDate.setHours(0, 0, 0, 0);
    
    if (messageDate.getTime() === today.getTime()) {
        return 'Today';
    } else if (messageDate.getTime() === yesterday.getTime()) {
        return 'Yesterday';
    } else {
        return messageDate.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
    }
}

// Format time for display
function formatTime(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
}

// Format message text with line breaks
function formatMessageText(text) {
    return text.replace(/\n/g, '<br>');
}

// Show alert popup
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed start-50 translate-middle-x`;
    alertDiv.style.top = '20px';
    alertDiv.style.zIndex = '9999';
    alertDiv.style.maxWidth = '500px';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 3000); // Show alert for shorter time (3s instead of 5s)
}
</script>

<style>
#messagesContainer {
    display: flex;
    flex-direction: column;
    padding: 1rem;
    gap: 1rem;
    scroll-behavior: smooth; /* Add smooth scrolling */
}

.message-date-separator {
    display: flex;
    justify-content: center;
    margin: 1rem 0;
    position: relative;
}

.message-date-separator:after {
    content: "";
    position: absolute;
    height: 1px;
    background-color: #e9ecef;
    width: 100%;
    top: 50%;
    z-index: 0;
}

.message-date-separator .badge {
    position: relative;
    z-index: 1;
}

/* Add transition effects to message bubbles */
.message-bubble {
    transition: opacity 0.2s ease;
}

/* Add animation for new messages */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.message-bubble:last-child {
    animation: fadeIn 0.3s ease;
}

/* Improve focus styles for textarea */
#messageInput:focus {
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    border-color: #86b7fe;
}
</style>

<?php include "includes/footer.php"; ?> 