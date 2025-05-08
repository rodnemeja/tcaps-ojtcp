<?php if(!empty($system_notifications)): ?>
    <div class="alert alert-info">
        <h5><i class="fas fa-bell me-2"></i>System Notifications</h5>
        <ul class="mb-0">
            <?php foreach($system_notifications as $notification): ?>
                <li><?php echo $notification; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Database Update Notification -->
<?php
$guardian_field_exists = false;
$query = "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'patients' AND COLUMN_NAME = 'guardian_name'";
$result = mysqli_query($conn, $query);
if($result && $row = mysqli_fetch_assoc($result)) {
    $guardian_field_exists = ($row['count'] > 0);
}

if(!$guardian_field_exists):
?>
<div class="alert alert-warning">
    <h5><i class="fas fa-exclamation-triangle me-2"></i>Database Update Required</h5>
    <p>The system has been updated to include guardian information for minor patients. Please run the following SQL update:</p>
    <ol>
        <li>Navigate to the <strong>sql_updates</strong> folder in your admin directory</li>
        <li>Run the <strong>guardian_fields.sql</strong> script on your database</li>
        <li>Refresh this page after the update is complete</li>
    </ol>
    <p class="mb-0"><a href="sql_updates/guardian_fields.sql" class="btn btn-sm btn-primary" download>Download SQL Update Script</a></p>
</div>
<?php endif; ?> 