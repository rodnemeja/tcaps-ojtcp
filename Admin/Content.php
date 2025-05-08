<?php
include('../Includes/conn.php');


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($_POST['content'] as $section_name => $content) {
        $section_name = mysqli_real_escape_string($conn, $section_name);
        $content = mysqli_real_escape_string($conn, $content);
        $sql = "UPDATE content_sections SET content = '$content' WHERE section_name = '$section_name'";
        mysqli_query($conn, $sql);
    }
    echo "Content updated successfully!";
}

// Fetch the current content from the database
$sql = "SELECT * FROM content_sections";
$result = mysqli_query( $sql);

$sections = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $sections[] = $row;
    }
}
?>

<form method="post">
    <?php foreach ($sections as $section): ?>
        <div>
            <label for="<?= $section['section_name'] ?>"><?= strtoupper($section['section_name']) ?></label>
            <textarea name="content[<?= $section['section_name'] ?>]" id="<?= $section['section_name'] ?>" rows="5" cols="100"><?= htmlspecialchars($section['content']) ?></textarea>
        </div>
    <?php endforeach; ?>
    <button type="submit">Save Changes</button>
</form>
