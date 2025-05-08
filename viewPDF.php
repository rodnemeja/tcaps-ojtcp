<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Viewer</title>
    <style>
        .pdf-container {
            width: 50%;
            height: 500px;
            border: 2px solid #ccc;
            overflow: hidden;
        }
        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
    </style>
</head>
<body>
    <div class="pdf-container">
        <?php  
        if (isset($_GET["file"])) {
            $filename = $_GET["file"];
            $filepath = "Upload/" . $filename;

            if (file_exists($filepath)) {
                echo '<iframe src="data:application/pdf;base64,' . base64_encode(file_get_contents($filepath)) . '"></iframe>';
            } else {
                echo "File not found.";
            }
        } else {
            echo "No file specified.";
        }
        ?>
    </div>
</body>
</html>
