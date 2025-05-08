<?php
include('Includes/header.php');
include('Includes/navbar1.php');

$query = "SELECT * FROM upload WHERE upload_department = $dept_id AND status = 'Approved'";
$result = mysqli_query($db, $query);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-2"></div>
        <div class="col-lg-8">
            <div class="card shadow text-gray-700" style="margin-top: 50px;">
                <div class="card-header text-primary">
                    <h2>Thesis/Capstone List</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Abstract</th>
                                    <!-- Other columns as needed -->
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                                    <tr>
                                        <td><?php echo $row['upload_name']; ?></td>
                                        <td><?php echo $row['upload_author']; ?></td>
                                        <td><?php echo $row['upload_abstract']; ?></td>
                                        <!-- Other columns as needed -->
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2"></div>
    </div>
</div>

<?php
include('../Includes/script.php');
?>
