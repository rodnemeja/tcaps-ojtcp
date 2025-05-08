<?php
require_once "config/database.php";
require_once "config/init.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

// Check if user is a patient
if($_SESSION["role"] !== "patient"){
    header("location: dashboard.php");
    exit;
}

$user_id = $_SESSION["id"];
$success_message = "";
$error_message = "";

// Get patient information
$sql = "SELECT p.*, u.first_name, u.last_name FROM patients p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.user_id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $patient = mysqli_fetch_assoc($result);
}

// Check if patient exists
if(!isset($patient) || !$patient){
    $error_message = "Patient record not found. Please complete your profile first.";
} else {
    // Check if medical history exists
    $sql = "SELECT * FROM medical_history WHERE patient_id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $patient['id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $has_medical_history = mysqli_num_rows($result) > 0;
        $medical_history = mysqli_fetch_assoc($result);
    }

    // Process form submission
    if($_SERVER["REQUEST_METHOD"] == "POST"){
        $errors = [];
        $success = false;

        // Validate allergies
        if (isset($_POST['has_allergies']) && $_POST['has_allergies'] == 'yes') {
            if (empty($_POST['allergies_details'])) {
                $errors[] = "Please provide allergy details if you have allergies.";
            } else {
                $allergies_details = trim($_POST['allergies_details']);
                if (strlen($allergies_details) > 500) {
                    $errors[] = "Allergy details must not exceed 500 characters.";
                }
            }
        }

        // Validate medications
        if (isset($_POST['has_medications']) && $_POST['has_medications'] == 'yes') {
            if (empty($_POST['medications_details'])) {
                $errors[] = "Please provide medication details if you are taking medications.";
            } else {
                $medications_details = trim($_POST['medications_details']);
                if (strlen($medications_details) > 500) {
                    $errors[] = "Medication details must not exceed 500 characters.";
                }
            }
        }

        // Validate medical conditions
        if (isset($_POST['med_conditions']) && is_array($_POST['med_conditions'])) {
            $valid_conditions = ['allergy', 'heart_disease', 'diabetes', 'hypertension', 'asthma', 'liver_disease', 'kidney_disease', 'cancer', 'other'];
            foreach ($_POST['med_conditions'] as $condition) {
                if (!in_array($condition, $valid_conditions)) {
                    $errors[] = "Invalid medical condition selected.";
                    break;
                }
            }
        }

        // Validate other conditions details
        if (isset($_POST['med_conditions']) && in_array('other', $_POST['med_conditions'])) {
            if (empty($_POST['other_conditions_details'])) {
                $errors[] = "Please provide details for other medical conditions.";
            } else {
                $other_conditions = trim($_POST['other_conditions_details']);
                if (strlen($other_conditions) > 500) {
                    $errors[] = "Other conditions details must not exceed 500 characters.";
                }
            }
        }

        // Validate hospitalization
        if (isset($_POST['has_hospitalization']) && $_POST['has_hospitalization'] == 'yes') {
            if (empty($_POST['hospitalization_info'])) {
                $errors[] = "Please provide hospitalization information.";
            } else {
                $hospitalization_info = trim($_POST['hospitalization_info']);
                if (strlen($hospitalization_info) > 500) {
                    $errors[] = "Hospitalization information must not exceed 500 characters.";
                }
            }
        }

        // Validate pregnancy status
        if (isset($_POST['is_pregnant']) && $_POST['is_pregnant'] == 'yes') {
            if (empty($_POST['pregnancy_details'])) {
                $errors[] = "Please provide pregnancy details.";
            } else {
                $pregnancy_details = trim($_POST['pregnancy_details']);
                if (strlen($pregnancy_details) > 500) {
                    $errors[] = "Pregnancy details must not exceed 500 characters.";
                }
            }
        }

        // Validate dental health status
        if (!empty($_POST['dental_health_status'])) {
            $dental_health = trim($_POST['dental_health_status']);
            if (strlen($dental_health) > 1000) {
                $errors[] = "Dental health status must not exceed 1000 characters.";
            }
        }

        // Validate oral prophylaxis
        if (!empty($_POST['oral_prophylaxis'])) {
            $prophylaxis = trim($_POST['oral_prophylaxis']);
            if (strlen($prophylaxis) > 1000) {
                $errors[] = "Oral prophylaxis details must not exceed 1000 characters.";
            }
        }

        // Validate blood type
        if (!empty($_POST['blood_type'])) {
            $valid_blood_types = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Unknown'];
            if (!in_array($_POST['blood_type'], $valid_blood_types)) {
                $errors[] = "Invalid blood type selected.";
            }
        }

        // Validate last dental visit
        if (!empty($_POST['last_dental_visit'])) {
            $last_visit = strtotime($_POST['last_dental_visit']);
            $current_date = strtotime(date('Y-m-d'));
            if ($last_visit > $current_date) {
                $errors[] = "Last dental visit date cannot be in the future.";
            }
        }

        // If no validation errors, proceed with saving
        if (empty($errors)) {
            try {
                // Prepare medical history data
        $medical_data = [
                    'has_allergies' => isset($_POST['has_allergies']) ? $_POST['has_allergies'] : 'no',
                    'allergies_details' => isset($_POST['allergies_details']) ? trim($_POST['allergies_details']) : '',
                    'has_medications' => isset($_POST['has_medications']) ? $_POST['has_medications'] : 'no',
                    'medications_details' => isset($_POST['medications_details']) ? trim($_POST['medications_details']) : '',
                    'medical_conditions' => isset($_POST['med_conditions']) ? implode(',', $_POST['med_conditions']) : '',
                    'other_conditions_details' => isset($_POST['other_conditions_details']) ? trim($_POST['other_conditions_details']) : '',
                    'blood_type' => isset($_POST['blood_type']) ? trim($_POST['blood_type']) : '',
                    'last_dental_visit' => isset($_POST['last_dental_visit']) ? trim($_POST['last_dental_visit']) : ''
                ];

                // Prepare additional notes
                $additional_notes = [
                    'dental_health_status' => isset($_POST['dental_health_status']) ? trim($_POST['dental_health_status']) : '',
                    'oral_prophylaxis' => isset($_POST['oral_prophylaxis']) ? trim($_POST['oral_prophylaxis']) : '',
                    'pregnancy_status' => isset($_POST['is_pregnant']) ? $_POST['is_pregnant'] : 'no',
                    'hospitalization' => isset($_POST['has_hospitalization']) ? $_POST['has_hospitalization'] : 'no',
                    'hospitalization_cause' => isset($_POST['hospitalization_info']) ? trim($_POST['hospitalization_info']) : '',
                    'dental_issues' => isset($_POST['has_dental_issues']) ? $_POST['has_dental_issues'] : 'no',
                    'prophylaxis' => isset($_POST['has_prophylaxis']) ? $_POST['has_prophylaxis'] : 'no'
                ];

                // Encrypt the data
                $encrypted_data = encryptMedicalData(json_encode($medical_data));
                $additional_notes_json = json_encode($additional_notes);

                if ($has_medical_history) {
            // Update existing medical history
                    $stmt = $conn->prepare("UPDATE medical_history SET 
                    has_allergies = ?,
                    allergies_details = ?,
                    has_medications = ?,
                    medications_details = ?,
                    medical_conditions = ?,
                    other_conditions_details = ?,
                    additional_notes = ?,
                        encrypted_data = ?
                        WHERE patient_id = ?");
                    
                    $stmt->bind_param("ssssssssi", 
                        $medical_data['has_allergies'],
                        $medical_data['allergies_details'],
                        $medical_data['has_medications'],
                        $medical_data['medications_details'],
                        $medical_data['medical_conditions'],
                        $medical_data['other_conditions_details'],
                        $additional_notes_json,
                        $encrypted_data,
                    $patient['id']
                );
        } else {
            // Insert new medical history
                    $stmt = $conn->prepare("INSERT INTO medical_history 
                        (patient_id, has_allergies, allergies_details, has_medications, 
                        medications_details, medical_conditions, other_conditions_details, 
                        additional_notes, encrypted_data) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    $stmt->bind_param("issssssss", 
                    $patient['id'],
                        $medical_data['has_allergies'],
                        $medical_data['allergies_details'],
                        $medical_data['has_medications'],
                        $medical_data['medications_details'],
                        $medical_data['medical_conditions'],
                        $medical_data['other_conditions_details'],
                        $additional_notes_json,
                        $encrypted_data
                    );
                }

                if ($stmt->execute()) {
                    $success = true;
                    $success_message = "Medical history updated successfully.";
                } else {
                    $errors[] = "Error saving medical history: " . $stmt->error;
                }
            } catch (Exception $e) {
                $errors[] = "Error: " . $e->getMessage();
            }
        }
    }
    
    // Decode medical history if it exists
    $medical_data = [];
    if($has_medical_history){
        $decrypted_data = openssl_decrypt(
            $medical_history['encrypted_data'],
            'AES-256-CBC',
            'dentalv-secret-key',
            0,
            substr(hash('sha256', 'dentalv-iv', true), 0, 16)
        );
        
        $medical_data = json_decode($decrypted_data, true);
        $additional_notes = $medical_data['additional_notes'];
    }
}

// Calculate age from date of birth
$dob = new DateTime($patient['date_of_birth'] ?? '');
$now = new DateTime();
$age = $now->diff($dob)->y;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical History - Dental Clinic Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fc;
        }
        .sidebar {
            min-height: 100vh;
            background: #4e73df;
            color: white;
            padding: 1rem;
            position: fixed;
            width: 16.66%;
            overflow-y: auto;
            z-index: 100;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.8);
            padding: 0.75rem 1rem;
            border-radius: 0.35rem;
            margin-bottom: 0.5rem;
        }
        .sidebar .nav-link:hover {
            color: white;
            background: rgba(255,255,255,.1);
        }
        .sidebar .nav-link.active {
            background: rgba(255,255,255,.1);
            color: white;
        }
        .main-content {
            padding: 2rem;
            margin-left: 16.66%;
        }
        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            transition: all 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2);
        }
        .card-header {
            background-color: #4e73df;
            border-bottom: none;
            padding: 1.25rem;
            border-radius: 0.75rem 0.75rem 0 0 !important;
        }
        .card-body {
            padding: 1.5rem;
        }
        
        /* Medical Conditions Cards */
        .condition-card {
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }
        .condition-card:hover {
            transform: translateY(-3px);
        }
        .condition-card.active {
            border-color: #4e73df;
            background-color: rgba(78, 115, 223, 0.1);
        }
        
        /* Blood Type Circle */
        .blood-type-circle {
            width: 80px !important;
            height: 80px !important;
            border-radius: 50%;
            background: linear-gradient(45deg, #e74a3b, #dc3545);
            box-shadow: 0 4px 20px rgba(231, 74, 59, 0.2);
        }
        
        /* Dental Chart Styles */
        .dental-chart {
            display: grid;
            grid-template-columns: repeat(16, 1fr);
            gap: 10px;
            margin: 20px 0;
            padding: 20px;
            background: #fff;
            border-radius: 0.75rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }
        
        .tooth {
            width: 45px;
            height: 55px;
            border: 2px solid #e3e6f0;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            background: #fff;
            transition: all 0.3s ease;
        }
        
        .tooth:hover {
            border-color: #4e73df;
            transform: translateY(-2px);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }
        
        .tooth-number {
            position: absolute;
            top: -25px;
            font-size: 12px;
            font-weight: 600;
            color: #4e73df;
        }
        
        /* Icons and Text */
        .text-primary {
            color: #4e73df !important;
        }
        
        .icon-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(78, 115, 223, 0.1);
            color: #4e73df;
            margin-right: 1rem;
        }
        
        /* Legend */
        .legend {
            background: #fff;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }
        
        .legend .icon {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-right: 0.75rem;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .dental-chart {
                grid-template-columns: repeat(8, 1fr);
                gap: 5px;
                padding: 10px;
            }
            
            .tooth {
                width: 35px;
                height: 45px;
            }
            
            .tooth-number {
                font-size: 10px;
                top: -20px;
            }
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php include "includes/header.php"; ?>
        
        <div class="col-md-9 col-lg-10 main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Medical History</h2>
                <div>
                <?php if($has_medical_history): ?>
                    <button type="button" class="btn btn-info me-2" data-bs-toggle="modal" data-bs-target="#viewMedicalHistoryModal">
                        <i class="fas fa-eye me-2"></i>View Details
                    </button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editMedicalHistoryModal">
                    <i class="fas fa-edit me-2"></i>Edit Medical History
                </button>
                <?php else: ?>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#editMedicalHistoryModal">
                    <i class="fas fa-plus me-2"></i>Add Medical History
                </button>
                <?php endif; ?>
                </div>
            </div>
            
            <?php if(!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>Patient Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-3"><i class="fas fa-user me-2 text-primary"></i><strong>Name:</strong><br><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></p>
                                    <p class="mb-3"><i class="fas fa-calendar me-2 text-primary"></i><strong>Date of Birth:</strong><br><?php echo htmlspecialchars($patient['date_of_birth'] ?? 'Not provided'); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-3"><i class="fas fa-birthday-cake me-2 text-primary"></i><strong>Age:</strong><br><?php echo $age; ?> years</p>
                                    <p class="mb-3"><i class="fas fa-venus-mars me-2 text-primary"></i><strong>Gender:</strong><br><?php echo htmlspecialchars($patient['gender'] ?? 'Not provided'); ?></p>
                                </div>
                            </div>
                            <div class="text-muted small">
                                <i class="fas fa-clock me-2"></i>Last Updated: <?php echo $has_medical_history ? date('F j, Y', strtotime($medical_history['updated_at'])) : 'Not provided'; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-heartbeat me-2"></i>Medical Information</h5>
                        </div>
                        <div class="card-body">
                            <?php if($has_medical_history): ?>
                                <div class="row">
                                    <!-- Blood Type Section -->
                                    <div class="col-12 mb-4">
                                        <div class="d-flex align-items-center">
                                            <div class="blood-type-circle bg-danger text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                                                <h4 class="mb-0"><?php echo htmlspecialchars($medical_data['blood_type'] ?? '?'); ?></h4>
                                    </div>
                                            <div>
                                                <h6 class="mb-1">Blood Type</h6>
                                                <p class="mb-0 text-muted"><?php echo htmlspecialchars($medical_data['blood_type'] ?? 'Not specified'); ?></p>
                                        </div>
                                        </div>
                                    </div>

                                    <!-- Medical Conditions -->
                                    <div class="col-12 mb-4">
                                        <h6 class="text-primary mb-3"><i class="fas fa-list-check me-2"></i>Medical Conditions</h6>
                                        <div class="row g-2">
                                            <?php
                                            $conditions = isset($medical_data['medical_conditions']) ? explode(',', $medical_data['medical_conditions']) : [];
                                            $condition_labels = [
                                                'allergy' => ['ALLERGY', 'fas fa-allergies'],
                                                'blood_pressure' => ['BLOOD PRESSURE', 'fas fa-heart-pulse'],
                                                'heart_disease' => ['HEART DISEASE', 'fas fa-heart'],
                                                'diabetes' => ['DIABETES', 'fas fa-syringe'],
                                                'liver_disease' => ['LIVER DISEASE', 'fas fa-lungs'],
                                                'kidney_disease' => ['KIDNEY DISEASE', 'fas fa-kidneys'],
                                                'asthma' => ['ASTHMA', 'fas fa-wind'],
                                                'epilepsy' => ['EPILEPSY', 'fas fa-brain'],
                                                'arthritis' => ['ARTHRITIS', 'fas fa-hand-holding-medical'],
                                                'cancer' => ['CANCER', 'fas fa-disease'],
                                                'hiv' => ['HIV', 'fas fa-virus'],
                                                'other' => ['OTHER', 'fas fa-circle-info']
                                            ];
                                            foreach($condition_labels as $value => $label):
                                            ?>
                                            <div class="col-md-4">
                                                <div class="card h-100 <?php echo in_array($value, $conditions) ? 'border-primary' : 'border-light'; ?>">
                                                    <div class="card-body p-2">
                                                        <div class="d-flex align-items-center">
                                                            <i class="<?php echo $label[1]; ?> me-2 <?php echo in_array($value, $conditions) ? 'text-primary' : 'text-muted'; ?>"></i>
                                                            <span class="<?php echo in_array($value, $conditions) ? 'text-primary' : 'text-muted'; ?>"><?php echo $label[0]; ?></span>
                                        </div>
                                    </div>
                                        </div>
                                    </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Allergies and Medications -->
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100 border-<?php echo isset($medical_data['has_allergies']) && $medical_data['has_allergies'] ? 'danger' : 'success'; ?>">
                                            <div class="card-body">
                                                <h6 class="text-<?php echo isset($medical_data['has_allergies']) && $medical_data['has_allergies'] ? 'danger' : 'success'; ?> mb-3">
                                                    <i class="fas fa-allergies me-2"></i>Allergies
                                                </h6>
                                                <p class="mb-0"><?php echo isset($medical_data['has_allergies']) && $medical_data['has_allergies'] ? htmlspecialchars($medical_data['allergies_details']) : 'No known allergies'; ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100 border-<?php echo isset($medical_data['has_medications']) && $medical_data['has_medications'] ? 'warning' : 'success'; ?>">
                                            <div class="card-body">
                                                <h6 class="text-<?php echo isset($medical_data['has_medications']) && $medical_data['has_medications'] ? 'warning' : 'success'; ?> mb-3">
                                                    <i class="fas fa-pills me-2"></i>Medications
                                                </h6>
                                                <p class="mb-0"><?php echo isset($medical_data['has_medications']) && $medical_data['has_medications'] ? htmlspecialchars($medical_data['medications_details']) : 'No current medications'; ?></p>
                                        </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Dental Health Status -->
                                    <div class="col-12 mb-4">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h6 class="text-primary mb-3"><i class="fas fa-tooth me-2"></i>Dental Health Status</h6>
                                                <p class="mb-0"><?php echo htmlspecialchars($additional_notes['dental_health_status'] ?? 'Not specified'); ?></p>
                                    </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Last Dental Visit -->
                                    <div class="col-12">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h6 class="text-primary mb-3"><i class="fas fa-calendar-check me-2"></i>Last Dental Visit</h6>
                                                <p class="mb-0"><?php echo htmlspecialchars($medical_data['last_dental_visit'] ?? 'Not specified'); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No medical history provided. Please add your medical history.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-tooth me-2"></i>Dental History</h5>
                        </div>
                        <div class="card-body">
                            <?php if($has_medical_history): ?>
                                <div class="dental-chart-container">
                                    <div class="text-center mb-4">
                                        <h6 class="text-primary">Dental Chart</h6>
                                        <p class="text-muted small">Current dental health status visualization</p>
                                    </div>
                                    
                                    <div class="dental-chart-wrapper">
                                        <div class="upper-jaw text-center mb-4">
                                            <h6 class="text-primary">Upper Jaw</h6>
                                        </div>
                                    <div class="dental-chart">
                                        <?php for($i = 18; $i >= 11; $i--): ?>
                                            <div class="tooth">
                                                <div class="tooth-number"><?php echo $i; ?></div>
                                                <?php 
                                                $hasOperation = isset($medical_data['dental_operations']) && in_array('tooth_'.$i, $medical_data['dental_operations']);
                                                $hasCondition = isset($medical_data['dental_conditions']) && in_array('tooth_'.$i, $medical_data['dental_conditions']);
                                                
                                                if($hasOperation && $hasCondition) {
                                                    echo '<i class="fas fa-circle text-danger"></i>';
                                                } elseif($hasOperation) {
                                                    echo '<i class="fas fa-times text-warning"></i>';
                                                } elseif($hasCondition) {
                                                    echo '<i class="fas fa-dot-circle text-info"></i>';
                                                } else {
                                                    echo '<i class="far fa-square text-success"></i>';
                                                }
                                                ?>
                                            </div>
                                        <?php endfor; ?>
                                        
                                        <?php for($i = 21; $i <= 28; $i++): ?>
                                            <div class="tooth">
                                                <div class="tooth-number"><?php echo $i; ?></div>
                                                <?php 
                                                $hasOperation = isset($medical_data['dental_operations']) && in_array('tooth_'.$i, $medical_data['dental_operations']);
                                                $hasCondition = isset($medical_data['dental_conditions']) && in_array('tooth_'.$i, $medical_data['dental_conditions']);
                                                
                                                if($hasOperation && $hasCondition) {
                                                    echo '<i class="fas fa-circle text-danger"></i>';
                                                } elseif($hasOperation) {
                                                    echo '<i class="fas fa-times text-warning"></i>';
                                                } elseif($hasCondition) {
                                                    echo '<i class="fas fa-dot-circle text-info"></i>';
                                                } else {
                                                    echo '<i class="far fa-square text-success"></i>';
                                                }
                                                ?>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                    
                                        <div class="lower-jaw text-center my-4">
                                            <h6 class="text-primary">Lower Jaw</h6>
                                        </div>
                                    <div class="dental-chart">
                                        <?php for($i = 48; $i >= 41; $i--): ?>
                                            <div class="tooth">
                                                <div class="tooth-number"><?php echo $i; ?></div>
                                                <?php 
                                                $hasOperation = isset($medical_data['dental_operations']) && in_array('tooth_'.$i, $medical_data['dental_operations']);
                                                $hasCondition = isset($medical_data['dental_conditions']) && in_array('tooth_'.$i, $medical_data['dental_conditions']);
                                                
                                                if($hasOperation && $hasCondition) {
                                                    echo '<i class="fas fa-circle text-danger"></i>';
                                                } elseif($hasOperation) {
                                                    echo '<i class="fas fa-times text-warning"></i>';
                                                } elseif($hasCondition) {
                                                    echo '<i class="fas fa-dot-circle text-info"></i>';
                                                } else {
                                                    echo '<i class="far fa-square text-success"></i>';
                                                }
                                                ?>
                                            </div>
                                        <?php endfor; ?>
                                        
                                        <?php for($i = 31; $i <= 38; $i++): ?>
                                            <div class="tooth">
                                                <div class="tooth-number"><?php echo $i; ?></div>
                                                <?php 
                                                $hasOperation = isset($medical_data['dental_operations']) && in_array('tooth_'.$i, $medical_data['dental_operations']);
                                                $hasCondition = isset($medical_data['dental_conditions']) && in_array('tooth_'.$i, $medical_data['dental_conditions']);
                                                
                                                if($hasOperation && $hasCondition) {
                                                    echo '<i class="fas fa-circle text-danger"></i>';
                                                } elseif($hasOperation) {
                                                    echo '<i class="fas fa-times text-warning"></i>';
                                                } elseif($hasCondition) {
                                                    echo '<i class="fas fa-dot-circle text-info"></i>';
                                                } else {
                                                    echo '<i class="far fa-square text-success"></i>';
                                                }
                                                ?>
                                            </div>
                                        <?php endfor; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="legend mt-4">
                                        <h6 class="text-primary mb-3">Legend</h6>
                                        <div class="d-flex flex-wrap justify-content-center gap-4">
                                            <div class="d-flex align-items-center">
                                                <i class="far fa-square text-success me-2"></i>
                                                <span>Healthy</span>
                                        </div>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-times text-warning me-2"></i>
                                                <span>Operation Needed</span>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-dot-circle text-info me-2"></i>
                                                <span>Condition Present</span>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-circle text-danger me-2"></i>
                                                <span>Operation & Condition</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-tooth fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No dental history provided. Please add your dental history.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Medical History Modal -->
<div class="modal fade" id="editMedicalHistoryModal" tabindex="-1" aria-labelledby="editMedicalHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editMedicalHistoryModalLabel">
                    <?php echo $has_medical_history ? 'Edit Medical History' : 'Add Medical History'; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" style="margin-top: 50px;">
                <div class="modal-body">
                            <div class="row">
                                <div class="form-group col-12 text-center mb-3">
                                    <hr class="m-0 p-0 mb-2">
                                    <label class="font-weight-bold text-dark" style="font-size: 1.2em;">Medical History</label>
                                    <hr class="m-0 p-0">
                                </div>
                                <div class="form-group col-12">
                                    <div class="row align-items-center">
                                        <div class="col-12 col-md-7">
                                            <label class="text-dark">1. Are you in good health?</label>
                                        </div>
                                        <div class="col-12 col-md-5">
                                            <div class="d-flex justify-content-around">
                                                <div class="custom-control custom-radio">
                                            <input class="custom-control-input" type="radio" id="one_yes" name="one" value="Yes" <?php echo (isset($medical_data['general_health']) && $medical_data['general_health'] == 'Yes') ? 'checked' : ''; ?> required>
                                                    <label for="one_yes" class="custom-control-label">Yes</label>
                                                </div>
                                                <div class="custom-control custom-radio">
                                            <input class="custom-control-input" type="radio" id="one_no" name="one" value="No" <?php echo (isset($medical_data['general_health']) && $medical_data['general_health'] == 'No') ? 'checked' : ''; ?>>
                                                    <label for="one_no" class="custom-control-label">No</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <hr class="mt-2">
                                <div class="form-group col-12">
                                    <div class="row align-items-center">
                                        <div class="col-12 col-md-7">
                                            <label class="text-dark">2. Are you under medical treatment now?</label>
                                        </div>
                                        <div class="col-12 col-md-5">
                                            <div class="d-flex justify-content-around">
                                                <div class="custom-control custom-radio">
                                            <input class="custom-control-input" type="radio" id="two_yes" name="two" value="Yes" <?php echo (isset($medical_data['medical_treatment']) && $medical_data['medical_treatment'] == 'Yes') ? 'checked' : ''; ?> required>
                                                    <label for="two_yes" class="custom-control-label">Yes</label>
                                                </div>
                                                <div class="custom-control custom-radio">
                                            <input class="custom-control-input" type="radio" id="two_no" name="two" value="No" <?php echo (isset($medical_data['medical_treatment']) && $medical_data['medical_treatment'] == 'No') ? 'checked' : ''; ?>>
                                                    <label for="two_no" class="custom-control-label">No</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            <input type="text" name="two_answer" id="two_answer" class="form-control" placeholder="If so, what is the condition being treated?" value="<?php echo isset($medical_data['medical_treatment_details']) ? htmlspecialchars($medical_data['medical_treatment_details']) : ''; ?>" />
                                </div>
                                <hr class="mt-2">
                                <div class="form-group col-12">
                                    <div class="row align-items-center">
                                        <div class="col-12 col-md-7">
                                    <label class="text-dark">3. Have you had any serious illness or operation?</label>
                                        </div>
                                        <div class="col-12 col-md-5">
                                            <div class="d-flex justify-content-around">
                                                <div class="custom-control custom-radio">
                                            <input class="custom-control-input" type="radio" id="three_yes" name="three" value="Yes" <?php echo (isset($medical_data['serious_illness']) && $medical_data['serious_illness'] == 'Yes') ? 'checked' : ''; ?> required>
                                                    <label for="three_yes" class="custom-control-label">Yes</label>
                                                </div>
                                                <div class="custom-control custom-radio">
                                            <input class="custom-control-input" type="radio" id="three_no" name="three" value="No" <?php echo (isset($medical_data['serious_illness']) && $medical_data['serious_illness'] == 'No') ? 'checked' : ''; ?>>
                                                    <label for="three_no" class="custom-control-label">No</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            <input type="text" name="three_answer" id="three_answer" class="form-control" placeholder="If so, what illness or operation?" value="<?php echo isset($medical_data['serious_illness_details']) ? htmlspecialchars($medical_data['serious_illness_details']) : ''; ?>" />
                                </div>
                                <hr class="mt-2">
                                <div class="form-group col-12">
                                    <div class="row align-items-center">
                                        <div class="col-12 col-md-7">
                                            <label class="text-dark">4. Have you ever been hospitalized?</label>
                                        </div>
                                        <div class="col-12 col-md-5">
                                            <div class="d-flex justify-content-around">
                                                <div class="custom-control custom-radio">
                                            <input class="custom-control-input" type="radio" id="four_yes" name="has_hospitalization" value="yes" <?php echo (isset($additional_notes['hospitalization']) && $additional_notes['hospitalization'] == 'yes') ? 'checked' : ''; ?> required>
                                                    <label for="four_yes" class="custom-control-label">Yes</label>
                                                </div>
                                                <div class="custom-control custom-radio">
                                            <input class="custom-control-input" type="radio" id="four_no" name="has_hospitalization" value="no" <?php echo (isset($additional_notes['hospitalization']) && $additional_notes['hospitalization'] == 'no') ? 'checked' : ''; ?>>
                                                    <label for="four_no" class="custom-control-label">No</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            <input type="text" name="hospitalization_info" id="hospitalization_info" class="form-control" placeholder="If so, when and why?" value="<?php echo isset($additional_notes['hospitalization_cause']) ? htmlspecialchars($additional_notes['hospitalization_cause']) : ''; ?>" />
                                </div>
                                <hr class="mt-2">
                                <div class="form-group col-12">
                                    <div class="row align-items-center">
                                        <div class="col-12 col-md-7">
                                            <label class="text-dark">5. Are you taking any prescription/non-prescription medication?</label>
                                        </div>
                                        <div class="col-12 col-md-5">
                                            <div class="d-flex justify-content-around">
                                                <div class="custom-control custom-radio">
                                            <input class="custom-control-input" type="radio" id="five_yes" name="five" value="Yes" <?php echo (isset($medical_data['has_medications']) && $medical_data['has_medications'] == 'Yes') ? 'checked' : ''; ?> required>
                                                    <label for="five_yes" class="custom-control-label">Yes</label>
                                                </div>
                                                <div class="custom-control custom-radio">
                                            <input class="custom-control-input" type="radio" id="five_no" name="five" value="No" <?php echo (isset($medical_data['has_medications']) && $medical_data['has_medications'] == 'No') ? 'checked' : ''; ?>>
                                                    <label for="five_no" class="custom-control-label">No</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            <input type="text" name="five_answer" id="five_answer" class="form-control" placeholder="If so, please specify" value="<?php echo isset($medical_data['medications_details']) ? htmlspecialchars($medical_data['medications_details']) : ''; ?>" />
                                </div>
                                <hr class="mt-2">
                                <div class="form-group col-12">
                                    <div class="row align-items-center">
                                        <div class="col-12 col-md-7">
                                    <label class="text-dark">6. Are you allergic to any medication?</label>
                                        </div>
                                        <div class="col-12 col-md-5">
                                            <div class="d-flex justify-content-around">
                                                <div class="custom-control custom-radio">
                                            <input class="custom-control-input" type="radio" id="six_yes" name="six" value="Yes" <?php echo (isset($medical_data['has_allergies']) && $medical_data['has_allergies'] == 'Yes') ? 'checked' : ''; ?> required>
                                                    <label for="six_yes" class="custom-control-label">Yes</label>
                                                </div>
                                                <div class="custom-control custom-radio">
                                            <input class="custom-control-input" type="radio" id="six_no" name="six" value="No" <?php echo (isset($medical_data['has_allergies']) && $medical_data['has_allergies'] == 'No') ? 'checked' : ''; ?>>
                                                    <label for="six_no" class="custom-control-label">No</label>
                                                </div>
                                            </div>
                                        </div>    
                                    </div>
                            <input type="text" name="six_answer" id="six_answer" class="form-control" placeholder="If so, what medication?" value="<?php echo isset($medical_data['allergies_details']) ? htmlspecialchars($medical_data['allergies_details']) : ''; ?>" />
                                </div>
                                <hr class="mt-2">
                                <div class="form-group col-12">
                                    <div class="row align-items-center">
                                        <div class="col-12 col-md-7">
                                    <label class="text-dark">7. Are you pregnant?</label>
                                        </div>
                                        <div class="col-12 col-md-5">
                                            <div class="d-flex justify-content-around">
                                                <div class="custom-control custom-radio">
                                            <input class="custom-control-input" type="radio" id="pregnant_yes" name="is_pregnant" value="yes" <?php echo (isset($additional_notes['pregnancy_status']) && $additional_notes['pregnancy_status'] == 'yes') ? 'checked' : ''; ?> required>
                                            <label for="pregnant_yes" class="custom-control-label">Yes</label>
                                                </div>
                                                <div class="custom-control custom-radio">
                                            <input class="custom-control-input" type="radio" id="pregnant_no" name="is_pregnant" value="no" <?php echo (isset($additional_notes['pregnancy_status']) && $additional_notes['pregnancy_status'] == 'no') ? 'checked' : ''; ?>>
                                            <label for="pregnant_no" class="custom-control-label">No</label>
                                                </div>
                                            </div>
                                        </div>    
                                    </div>
                                </div>
                                <hr class="mt-2">
                                <div class="form-group col-12">
                            <div class="row align-items-center">
                                        <div class="col-12 col-md-7">
                                    <label class="text-dark">8. Do you have any dental issues?</label>
                                        </div>
                                        <div class="col-12 col-md-5">
                                    <div class="d-flex justify-content-around">
                                                <div class="custom-control custom-radio">
                                            <input class="custom-control-input" type="radio" id="dental_issues_yes" name="has_dental_issues" value="yes" <?php echo (isset($additional_notes['dental_issues']) && $additional_notes['dental_issues'] == 'yes') ? 'checked' : ''; ?> required>
                                            <label for="dental_issues_yes" class="custom-control-label">Yes</label>
                                                </div>
                                                <div class="custom-control custom-radio">
                                            <input class="custom-control-input" type="radio" id="dental_issues_no" name="has_dental_issues" value="no" <?php echo (isset($additional_notes['dental_issues']) && $additional_notes['dental_issues'] == 'no') ? 'checked' : ''; ?>>
                                            <label for="dental_issues_no" class="custom-control-label">No</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                        </div>
                        <hr class="mt-2">
                        <div class="form-group col-12">
                            <div class="row align-items-center">
                                        <div class="col-12 col-md-7">
                                    <label class="text-dark">9. Have you had any dental prophylaxis?</label>
                                        </div>
                                        <div class="col-12 col-md-5">
                                    <div class="d-flex justify-content-around">
                                                <div class="custom-control custom-radio">
                                            <input class="custom-control-input" type="radio" id="prophylaxis_yes" name="has_prophylaxis" value="yes" <?php echo (isset($additional_notes['prophylaxis']) && $additional_notes['prophylaxis'] == 'yes') ? 'checked' : ''; ?> required>
                                            <label for="prophylaxis_yes" class="custom-control-label">Yes</label>
                                                </div>
                                                <div class="custom-control custom-radio">
                                            <input class="custom-control-input" type="radio" id="prophylaxis_no" name="has_prophylaxis" value="no" <?php echo (isset($additional_notes['prophylaxis']) && $additional_notes['prophylaxis'] == 'no') ? 'checked' : ''; ?>>
                                            <label for="prophylaxis_no" class="custom-control-label">No</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                        </div>
                        <hr class="mt-2">
                        <div class="form-group col-12">
                            <label class="text-dark">10. Medical Conditions (Check all that apply):</label>
                            <div class="row">
                                <?php
                                $conditions = isset($medical_data['medical_conditions']) ? explode(',', $medical_data['medical_conditions']) : [];
                                $condition_labels = [
                                    'allergy' => 'ALLERGY',
                                    'blood_pressure' => 'BLOOD PRESSURE',
                                    'heart_disease' => 'HEART DISEASE',
                                    'diabetes' => 'DIABETES',
                                    'liver_disease' => 'LIVER DISEASE',
                                    'kidney_disease' => 'KIDNEY DISEASE',
                                    'asthma' => 'ASTHMA',
                                    'epilepsy' => 'EPILEPSY',
                                    'arthritis' => 'ARTHRITIS',
                                    'cancer' => 'CANCER',
                                    'hiv' => 'HIV',
                                    'other' => 'OTHER'
                                ];
                                foreach($condition_labels as $value => $label):
                                ?>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="med_conditions[]" value="<?php echo $value; ?>" id="<?php echo $value; ?>"
                                            <?php echo in_array($value, $conditions) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="<?php echo $value; ?>"><?php echo $label; ?></label>
                                                </div>
                                                </div>
                                <?php endforeach; ?>
                                            </div>
                            <input type="text" name="other_illness" id="other_illness" class="form-control" placeholder="If other, please specify" value="<?php echo isset($medical_data['other_conditions_details']) ? htmlspecialchars($medical_data['other_conditions_details']) : ''; ?>" />
                                        </div>
                        <hr class="mt-2">
                        <div class="form-group col-12">
                            <label class="text-dark">11. Dental Health Status:</label>
                            <textarea name="dental_health_status" id="dental_health_status" class="form-control" rows="3" placeholder="Please describe your current dental health status"><?php echo isset($additional_notes['dental_health_status']) ? htmlspecialchars($additional_notes['dental_health_status']) : ''; ?></textarea>
                                </div>
                                <hr class="mt-2">
                                <div class="form-group col-12">
                            <label class="text-dark">12. Oral Prophylaxis:</label>
                            <textarea name="oral_prophylaxis" id="oral_prophylaxis" class="form-control" rows="3" placeholder="Please describe your oral prophylaxis history"><?php echo isset($additional_notes['oral_prophylaxis']) ? htmlspecialchars($additional_notes['oral_prophylaxis']) : ''; ?></textarea>
                                        </div>
                        <hr class="mt-2">
                        <div class="form-group col-12">
                            <label class="text-dark">13. Blood Type:</label>
                            <select name="blood_type" id="blood_type" class="form-control" required>
                                                <option value="" disabled selected>Select your blood type</option>
                                <option value="A+" <?php echo (isset($medical_data['blood_type']) && $medical_data['blood_type'] == 'A+') ? 'selected' : ''; ?>>A+</option>
                                <option value="A-" <?php echo (isset($medical_data['blood_type']) && $medical_data['blood_type'] == 'A-') ? 'selected' : ''; ?>>A-</option>
                                <option value="B+" <?php echo (isset($medical_data['blood_type']) && $medical_data['blood_type'] == 'B+') ? 'selected' : ''; ?>>B+</option>
                                <option value="B-" <?php echo (isset($medical_data['blood_type']) && $medical_data['blood_type'] == 'B-') ? 'selected' : ''; ?>>B-</option>
                                <option value="AB+" <?php echo (isset($medical_data['blood_type']) && $medical_data['blood_type'] == 'AB+') ? 'selected' : ''; ?>>AB+</option>
                                <option value="AB-" <?php echo (isset($medical_data['blood_type']) && $medical_data['blood_type'] == 'AB-') ? 'selected' : ''; ?>>AB-</option>
                                <option value="O+" <?php echo (isset($medical_data['blood_type']) && $medical_data['blood_type'] == 'O+') ? 'selected' : ''; ?>>O+</option>
                                <option value="O-" <?php echo (isset($medical_data['blood_type']) && $medical_data['blood_type'] == 'O-') ? 'selected' : ''; ?>>O-</option>
                                <option value="Unknown" <?php echo (isset($medical_data['blood_type']) && $medical_data['blood_type'] == 'Unknown') ? 'selected' : ''; ?>>Unknown</option>
                                            </select>
                                </div>
                                <hr class="mt-2">
                                <div class="form-group col-12">
                            <label class="text-dark">14. Last Dental Visit:</label>
                            <input type="date" name="last_dental_visit" id="last_dental_visit" class="form-control" 
                                   value="<?php echo isset($medical_data['last_dental_visit']) ? htmlspecialchars($medical_data['last_dental_visit']) : ''; ?>">
                            <small class="form-text text-muted">Please enter the date of your last dental visit</small>
                                        </div>
                                        </div>
                                    </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Medical History</button>
                                        </div>
            </form>
                                        </div>
                                    </div>
                                        </div>

<!-- View Medical History Modal -->
<div class="modal fade" id="viewMedicalHistoryModal" tabindex="-1" aria-labelledby="viewMedicalHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="viewMedicalHistoryModalLabel">
                    <i class="fas fa-file-medical me-2"></i>Medical History Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
            <div class="modal-body">
                <?php if($has_medical_history): ?>
                    <div class="row">
                        <div class="form-group col-12 text-center mb-3">
                            <hr class="m-0 p-0 mb-2">
                            <label class="font-weight-bold text-dark" style="font-size: 1.2em;">Medical History</label>
                            <hr class="m-0 p-0">
                                    </div>
                        <div class="form-group col-12">
                            <div class="row align-items-center">
                                <div class="col-12 col-md-7">
                                    <label class="text-dark">1. Are you in good health?</label>
                                        </div>
                                <div class="col-12 col-md-5">
                                    <p class="mb-0"><?php echo isset($medical_data['general_health']) ? $medical_data['general_health'] : 'Not specified'; ?></p>
                                        </div>
                                    </div>
                                        </div>
                        <hr class="mt-2">
                        <div class="form-group col-12">
                            <div class="row align-items-center">
                                <div class="col-12 col-md-7">
                                    <label class="text-dark">2. Are you under medical treatment now?</label>
                                        </div>
                                <div class="col-12 col-md-5">
                                    <p class="mb-0"><?php echo isset($medical_data['medical_treatment']) ? $medical_data['medical_treatment'] : 'Not specified'; ?></p>
                                    </div>
                                        </div>
                            <?php if(isset($medical_data['medical_treatment']) && $medical_data['medical_treatment'] === 'Yes'): ?>
                            <p class="mt-2"><strong>Treatment details:</strong> <?php echo htmlspecialchars($medical_data['medical_treatment_details'] ?? ''); ?></p>
                            <?php endif; ?>
                                        </div>
                        <hr class="mt-2">
                        <div class="form-group col-12">
                            <div class="row align-items-center">
                                <div class="col-12 col-md-7">
                                    <label class="text-dark">3. Have you had any serious illness or operation?</label>
                                    </div>
                                <div class="col-12 col-md-5">
                                    <p class="mb-0"><?php echo isset($medical_data['serious_illness']) ? $medical_data['serious_illness'] : 'Not specified'; ?></p>
                                        </div>
                                        </div>
                            <?php if(isset($medical_data['serious_illness']) && $medical_data['serious_illness'] === 'Yes'): ?>
                            <p class="mt-2"><strong>Details:</strong> <?php echo htmlspecialchars($medical_data['serious_illness_details'] ?? ''); ?></p>
                            <?php endif; ?>
                                    </div>
                        <hr class="mt-2">
                        <div class="form-group col-12">
                            <div class="row align-items-center">
                                <div class="col-12 col-md-7">
                                    <label class="text-dark">4. Have you ever been hospitalized?</label>
                                        </div>
                                <div class="col-12 col-md-5">
                                    <p class="mb-0"><?php echo isset($additional_notes['hospitalization']) ? ucfirst($additional_notes['hospitalization']) : 'Not specified'; ?></p>
                                        </div>
                                    </div>
                            <?php if(isset($additional_notes['hospitalization']) && $additional_notes['hospitalization'] === 'yes'): ?>
                            <p class="mt-2"><strong>Hospitalization details:</strong> <?php echo htmlspecialchars($additional_notes['hospitalization_cause'] ?? ''); ?></p>
                            <?php endif; ?>
                                        </div>
                        <hr class="mt-2">
                        <div class="form-group col-12">
                            <div class="row align-items-center">
                                <div class="col-12 col-md-7">
                                    <label class="text-dark">5. Are you taking any prescription/non-prescription medication?</label>
                                        </div>
                                <div class="col-12 col-md-5">
                                    <p class="mb-0"><?php echo isset($medical_data['has_medications']) ? ucfirst($medical_data['has_medications']) : 'Not specified'; ?></p>
                                    </div>
                                        </div>
                            <?php if(isset($medical_data['has_medications']) && $medical_data['has_medications'] === 'Yes'): ?>
                            <p class="mt-2"><strong>Medication details:</strong> <?php echo htmlspecialchars($medical_data['medications_details'] ?? ''); ?></p>
                            <?php endif; ?>
                                        </div>
                        <hr class="mt-2">
                        <div class="form-group col-12">
                            <div class="row align-items-center">
                                <div class="col-12 col-md-7">
                                    <label class="text-dark">6. Are you allergic to any medication?</label>
                                    </div>
                                <div class="col-12 col-md-5">
                                    <p class="mb-0"><?php echo isset($medical_data['has_allergies']) ? ucfirst($medical_data['has_allergies']) : 'Not specified'; ?></p>
                                        </div>
                                        </div>
                            <?php if(isset($medical_data['has_allergies']) && $medical_data['has_allergies'] === 'Yes'): ?>
                            <p class="mt-2"><strong>Allergy details:</strong> <?php echo htmlspecialchars($medical_data['allergies_details'] ?? ''); ?></p>
                            <?php endif; ?>
                                    </div>
                        <hr class="mt-2">
                        <div class="form-group col-12">
                            <div class="row align-items-center">
                                <div class="col-12 col-md-7">
                                    <label class="text-dark">7. Are you pregnant?</label>
                                        </div>
                                <div class="col-12 col-md-5">
                                    <p class="mb-0"><?php echo isset($additional_notes['pregnancy_status']) ? ucfirst($additional_notes['pregnancy_status']) : 'Not specified'; ?></p>
                                        </div>
                                    </div>
                                        </div>
                        <hr class="mt-2">
                        <div class="form-group col-12">
                            <div class="row align-items-center">
                                <div class="col-12 col-md-7">
                                    <label class="text-dark">8. Do you have any dental issues?</label>
                                        </div>
                                <div class="col-12 col-md-5">
                                    <p class="mb-0"><?php echo isset($additional_notes['dental_issues']) ? ucfirst($additional_notes['dental_issues']) : 'Not specified'; ?></p>
                                    </div>
                                        </div>
                                        </div>
                        <hr class="mt-2">
                        <div class="form-group col-12">
                            <div class="row align-items-center">
                                <div class="col-12 col-md-7">
                                    <label class="text-dark">9. Have you had any dental prophylaxis?</label>
                                    </div>
                                <div class="col-12 col-md-5">
                                    <p class="mb-0"><?php echo isset($additional_notes['prophylaxis']) ? ucfirst($additional_notes['prophylaxis']) : 'Not specified'; ?></p>
                                        </div>
                                        </div>
                                    </div>
                        <hr class="mt-2">
                        <div class="form-group col-12">
                            <label class="text-dark">10. Medical Conditions:</label>
                            <div class="row">
                                <?php
                                $conditions = isset($medical_data['medical_conditions']) ? explode(',', $medical_data['medical_conditions']) : [];
                                $condition_labels = [
                                    'allergy' => 'ALLERGY',
                                    'blood_pressure' => 'BLOOD PRESSURE',
                                    'heart_disease' => 'HEART DISEASE',
                                    'diabetes' => 'DIABETES',
                                    'liver_disease' => 'LIVER DISEASE',
                                    'kidney_disease' => 'KIDNEY DISEASE',
                                    'asthma' => 'ASTHMA',
                                    'epilepsy' => 'EPILEPSY',
                                    'arthritis' => 'ARTHRITIS',
                                    'cancer' => 'CANCER',
                                    'hiv' => 'HIV',
                                    'other' => 'OTHER'
                                ];
                                foreach($condition_labels as $value => $label):
                                ?>
                                <div class="col-md-4 mb-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-check-circle me-2 <?php echo in_array($value, $conditions) ? 'text-success' : 'text-muted'; ?>"></i>
                                        <span class="<?php echo in_array($value, $conditions) ? 'text-success' : 'text-muted'; ?>"><?php echo $label; ?></span>
                                        </div>
                                        </div>
                                <?php endforeach; ?>
                                    </div>
                            <?php if(isset($medical_data['other_conditions_details']) && !empty($medical_data['other_conditions_details'])): ?>
                            <p class="mt-2"><strong>Other conditions:</strong> <?php echo htmlspecialchars($medical_data['other_conditions_details']); ?></p>
                            <?php endif; ?>
                                        </div>
                        <hr class="mt-2">
                        <div class="form-group col-12">
                            <label class="text-dark">11. Dental Health Status:</label>
                            <p class="mb-0"><?php echo htmlspecialchars($additional_notes['dental_health_status'] ?? 'Not specified'); ?></p>
                                        </div>
                        <hr class="mt-2">
                        <div class="form-group col-12">
                            <label class="text-dark">12. Oral Prophylaxis:</label>
                            <p class="mb-0"><?php echo htmlspecialchars($additional_notes['oral_prophylaxis'] ?? 'Not specified'); ?></p>
                                    </div>
                        <hr class="mt-2">
                        <div class="form-group col-12">
                            <label class="text-dark">13. Blood Type:</label>
                            <p class="mb-0"><?php echo htmlspecialchars($medical_data['blood_type'] ?? 'Not specified'); ?></p>
                                </div>
                        <hr class="mt-2">
                        <div class="form-group col-12">
                            <label class="text-dark">14. Last Dental Visit:</label>
                            <p class="mb-0"><?php echo htmlspecialchars($medical_data['last_dental_visit'] ?? 'Not specified'); ?></p>
                            </div>
                        </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-file-medical fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No medical history has been added yet.</p>
                    </div>
                <?php endif; ?>
                </div>
                <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all modals
    var modals = document.querySelectorAll('.modal');
    modals.forEach(function(modal) {
        var modalInstance = new bootstrap.Modal(modal);
        
        // Add event listener for hidden.bs.modal event
        modal.addEventListener('hidden.bs.modal', function () {
            // Remove modal backdrop
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
            // Remove modal-open class from body
            document.body.classList.remove('modal-open');
            // Reset body padding
            document.body.style.paddingRight = '';
            // Reset body overflow
            document.body.style.overflow = '';
        });
    });

    // Add click event listener for view button
    document.querySelector('[data-bs-target="#viewMedicalHistoryModal"]')?.addEventListener('click', function() {
        var viewModal = new bootstrap.Modal(document.getElementById('viewMedicalHistoryModal'));
        viewModal.show();
    });

    // Add click event listener for edit button
    document.querySelector('[data-bs-target="#editMedicalHistoryModal"]')?.addEventListener('click', function() {
        var editModal = new bootstrap.Modal(document.getElementById('editMedicalHistoryModal'));
        editModal.show();
    });

    // Toggle allergies details
    const hasAllergiesCheckbox = document.getElementById('has_allergies');
    const allergiesDetails = document.getElementById('allergiesDetails');
    
    if(hasAllergiesCheckbox && allergiesDetails) {
    hasAllergiesCheckbox.addEventListener('change', function() {
        allergiesDetails.classList.toggle('d-none', !this.checked);
    });
    }
    
    // Toggle medications details
    const hasMedicationsCheckbox = document.getElementById('has_medications');
    const medicationsDetails = document.getElementById('medicationsDetails');
    
    if(hasMedicationsCheckbox && medicationsDetails) {
    hasMedicationsCheckbox.addEventListener('change', function() {
        medicationsDetails.classList.toggle('d-none', !this.checked);
    });
    }

    // Toggle hospitalization details
    const hospitalizationRadios = document.querySelectorAll('input[name="has_hospitalization"]');
    const hospitalizationDetails = document.getElementById('hospitalizationDetails');
    
    if(hospitalizationDetails) {
    hospitalizationRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            hospitalizationDetails.classList.toggle('d-none', this.value === 'no');
        });
    });
    }

    // Toggle dental health details
    const dentalRadios = document.querySelectorAll('input[name="has_dental_issues"]');
    const dentalDetails = document.getElementById('dentalDetails');
    
    if(dentalDetails) {
    dentalRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            dentalDetails.classList.toggle('d-none', this.value === 'no');
        });
    });
    }

    // Toggle prophylaxis details
    const prophylaxisRadios = document.querySelectorAll('input[name="has_prophylaxis"]');
    const prophylaxisDetails = document.getElementById('prophylaxisDetails');
    
    if(prophylaxisDetails) {
    prophylaxisRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            prophylaxisDetails.classList.toggle('d-none', this.value === 'no');
        });
    });
    }
});
</script>
</body>
</html> 