
 <?php  
 if(isset($_GET["file"]))  {

    $filename = $_GET["file"];
    $path = "../Upload/".$filename;
    header('Content-type: application/pdf');
    header('Content-Description: inline; filename="'.$filename.'"');
    header('Content-Transfer-Encoding: binary');
    header('Accept-ranges:bytes');

    @readfile($path);
     
 }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View PDF in Modal</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Button to open modal -->
    <button type="button" class="btn btn-primary" onclick="openPdf('example.pdf')">
        View PDF
    </button>

    <!-- Modal -->
    <div class="modal fade" id="pdfModal" tabindex="-1" aria-labelledby="pdfModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pdfModalLabel">PDF Viewer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Iframe to load PDF -->
                    <iframe id="pdfIframe" src="" frameborder="0" width="100%" height="600px"></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openPdf(file) {
            // Set the source of the iframe to the PHP script with the file parameter
            const iframe = document.getElementById('pdfIframe');
            iframe.src = `view_pdf.php?file=${encodeURIComponent(file)}`;

            // Show the modal
            const pdfModal = new bootstrap.Modal(document.getElementById('pdfModal'));
            pdfModal.show();
        }
    </script>
</body>
</html>
