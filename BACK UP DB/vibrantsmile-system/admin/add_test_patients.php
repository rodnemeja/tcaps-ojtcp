<?php
require_once "../config/init.php";
require_once "../config/database.php";

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

// Test patient data
$test_patients = [
    [
        'username' => 'john_doe',
        'email' => 'john.doe@email.com',
        'full_name' => 'John Doe',
        'phone' => '09123456789',
        'date_of_birth' => '1990-05-15',
        'gender' => 'male',
        'address' => '123 Main St, Manila, Philippines',
        'medical_history' => 'No major health issues. Regular dental check-ups.'
    ],
    [
        'username' => 'jane_smith',
        'email' => 'jane.smith@email.com',
        'full_name' => 'Jane Smith',
        'phone' => '09234567890',
        'date_of_birth' => '1985-08-22',
        'gender' => 'female',
        'address' => '456 Park Ave, Quezon City, Philippines',
        'medical_history' => 'Allergic to penicillin. Regular dental maintenance.'
    ],
    [
        'username' => 'mike_wilson',
        'email' => 'mike.wilson@email.com',
        'full_name' => 'Mike Wilson',
        'phone' => '09345678901',
        'date_of_birth' => '1995-03-10',
        'gender' => 'male',
        'address' => '789 Oak St, Makati, Philippines',
        'medical_history' => 'Asthma. Needs regular dental cleaning.'
    ],
    [
        'username' => 'sarah_jones',
        'email' => 'sarah.jones@email.com',
        'full_name' => 'Sarah Jones',
        'phone' => '09456789012',
        'date_of_birth' => '1988-12-25',
        'gender' => 'female',
        'address' => '321 Pine St, Pasig, Philippines',
        'medical_history' => 'Diabetes. Requires special dental care.'
    ],
    [
        'username' => 'david_brown',
        'email' => 'david.brown@email.com',
        'full_name' => 'David Brown',
        'phone' => '09567890123',
        'date_of_birth' => '1992-07-18',
        'gender' => 'male',
        'address' => '654 Elm St, Taguig, Philippines',
        'medical_history' => 'No major health issues. Regular dental visits.'
    ],
    [
        'username' => 'emma_davis',
        'email' => 'emma.davis@email.com',
        'full_name' => 'Emma Davis',
        'phone' => '09678901234',
        'date_of_birth' => '1998-01-30',
        'gender' => 'female',
        'address' => '987 Maple St, Mandaluyong, Philippines',
        'medical_history' => 'Heart condition. Requires careful dental treatment.'
    ],
    [
        'username' => 'james_miller',
        'email' => 'james.miller@email.com',
        'full_name' => 'James Miller',
        'phone' => '09789012345',
        'date_of_birth' => '1983-11-05',
        'gender' => 'male',
        'address' => '147 Cedar St, Pasay, Philippines',
        'medical_history' => 'Hypertension. Regular dental check-ups.'
    ],
    [
        'username' => 'lisa_anderson',
        'email' => 'lisa.anderson@email.com',
        'full_name' => 'Lisa Anderson',
        'phone' => '09890123456',
        'date_of_birth' => '1993-04-20',
        'gender' => 'female',
        'address' => '258 Birch St, Manila, Philippines',
        'medical_history' => 'No major health issues. Regular dental maintenance.'
    ],
    [
        'username' => 'robert_taylor',
        'email' => 'robert.taylor@email.com',
        'full_name' => 'Robert Taylor',
        'phone' => '09901234567',
        'date_of_birth' => '1987-09-15',
        'gender' => 'male',
        'address' => '369 Willow St, Quezon City, Philippines',
        'medical_history' => 'Allergic to certain medications. Regular dental visits.'
    ],
    [
        'username' => 'sophia_white',
        'email' => 'sophia.white@email.com',
        'full_name' => 'Sophia White',
        'phone' => '09012345678',
        'date_of_birth' => '1996-06-28',
        'gender' => 'female',
        'address' => '741 Spruce St, Makati, Philippines',
        'medical_history' => 'No major health issues. Regular dental check-ups.'
    ]
];

$success_count = 0;
$error_count = 0;
$errors = [];

// Default password for all test accounts
$default_password = "Test@123";

// Connect to the database
$conn = mysqli_connect("localhost", "root", "", "schema");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Enable error reporting for MySQL
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Clear existing test patients
    $clear_patients = mysqli_query($conn, "DELETE FROM patients WHERE id > 0");
    $clear_users = mysqli_query($conn, "DELETE FROM users WHERE role = 'patient'");

    if (!$clear_patients || !$clear_users) {
        throw new Exception("Error clearing existing test patients: " . mysqli_error($conn));
    }

    // Prepare statements
    $user_stmt = mysqli_prepare($conn, "INSERT INTO users (username, password, role, full_name, email, phone, created_at, updated_at) VALUES (?, ?, 'patient', ?, ?, ?, NOW(), NOW())");
    $patient_stmt = mysqli_prepare($conn, "INSERT INTO patients (user_id, date_of_birth, gender, address, medical_history, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");

    if (!$user_stmt || !$patient_stmt) {
        throw new Exception("Error preparing statements: " . mysqli_error($conn));
    }

    foreach($test_patients as $patient) {
        // Check if username already exists
        $check_sql = "SELECT id FROM users WHERE username = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        if (!$check_stmt) {
            throw new Exception("Error preparing check statement: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($check_stmt, "s", $patient['username']);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if(mysqli_stmt_num_rows($check_stmt) > 0) {
            $error_count++;
            $errors[] = "Username {$patient['username']} already exists.";
            continue;
        }
        
        // Insert into users table
        $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
        mysqli_stmt_bind_param($user_stmt, "sssss", $patient['username'], $hashed_password, $patient['full_name'], $patient['email'], $patient['phone']);
        
        if (!mysqli_stmt_execute($user_stmt)) {
            throw new Exception("Error inserting user {$patient['username']}: " . mysqli_stmt_error($user_stmt));
        }

        $user_id = mysqli_insert_id($conn);
        echo "Successfully inserted user {$patient['username']} with ID: {$user_id}<br>";

        // Insert into patients table
        mysqli_stmt_bind_param($patient_stmt, "issss", $user_id, $patient['date_of_birth'], $patient['gender'], $patient['address'], $patient['medical_history']);
        
        if (!mysqli_stmt_execute($patient_stmt)) {
            throw new Exception("Error inserting patient for user {$patient['username']}: " . mysqli_stmt_error($patient_stmt));
        }

        $patient_id = mysqli_insert_id($conn);
        echo "Successfully inserted patient with ID: {$patient_id} for user {$patient['username']}<br>";

        $success_count++;
    }

    // Debug: Check current data in tables
    $users_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'patient'"))['count'];
    $patients_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM patients"))['count'];

    echo "<br>Final counts:<br>";
    echo "Users with role 'patient': {$users_count}<br>";
    echo "Total patients: {$patients_count}<br>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    $error_count++;
    $errors[] = $e->getMessage();
}

// Close statements
mysqli_stmt_close($user_stmt);
mysqli_stmt_close($patient_stmt);

// Close connection
mysqli_close($conn);

$page_title = "Add Test Patients";
$current_page = "patients";
require_once "includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Add Test Patients</h2>
    <a href="patients.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Patients
    </a>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">Test Patients Added</h5>
        <p class="card-text">
            Successfully added: <?php echo $success_count; ?> patients<br>
            Failed to add: <?php echo $error_count; ?> patients<br>
            Current users in database: <?php echo $users_count; ?><br>
            Current patients in database: <?php echo $patients_count; ?>
        </p>
        
        <?php if(!empty($errors)): ?>
            <div class="alert alert-danger">
                <h6>Errors encountered:</h6>
                <ul class="mb-0">
                    <?php foreach($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="alert alert-info">
            <h6>Default Login Credentials for all test accounts:</h6>
            <p class="mb-0">
                Username: (as specified in the list)<br>
                Password: <?php echo $default_password; ?>
            </p>
        </div>
        
        <a href="patients.php" class="btn btn-primary">
            <i class="fas fa-users me-2"></i>View Patients List
        </a>
    </div>
</div>

<?php require_once "includes/footer.php"; ?> 