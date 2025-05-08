<?php
include('../Includes/header.php');
include('../Includes/conn.php');

include('../Includes/dean_navbar.php');
?>

<?php


if(isset($_POST['delete_upload_btn'])){
    $studID2 = $_POST['upload_ID'];
    $query2 = "DELETE FROM upload WHERE upload_id = $studID2";
	$query_run2 = mysqli_query($db, $query2);

    mysqli_close($db);

}
?>

<!-- Begin Page Content -->
<div class="container-fluid">



<style>
        body {

        }
        .container {

        }
        h1 {
            text-align: center;
            color: #333;
        }
        .form-group {
            margin-bottom: 20px;
        }
        textarea {
            width: 100%;
            height: 150px;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
        }
        .result {
            margin-top: 20px;
            padding: 10px;
            background-color: #e8e8e8;
            border-radius: 5px;
        }
        .result p {
            font-size: 18px;
            margin: 5px 0;
        }
        input[type="file"] {
            display: block;
            margin: 20px auto;
        }
        .pie-chart-container {
            width: 100%;
            height: 300px;
            margin: 30px 0;
        }
        .link-container {
            margin-top: 20px;
            text-align: center;
        }
        a {
            text-decoration: none;
            color: #4CAF50;
            font-weight: bold;
        }
        .fake-link {
            display: block;
            margin-top: 10px;
            color: #007bff;
            text-decoration: none;
        }
        .fake-link:hover {
            text-decoration: underline;
        }

        /* Spinner CSS */
        .spinner {
            border: 4px solid #f3f3f3; 
            border-top: 4px solid #4CAF50;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 2s linear infinite;
            margin: 0 auto;
            display: none; /* Hidden by default */
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>TCAS Plagiarism Checker</h1>
        
        <form id="plagiarism-form">
            <div class="form-group">
                <label for="textInput">Enter Text to Check:</label>
                <textarea id="textInput" placeholder="Paste your text here..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="pdfInput">Or Upload a PDF File:</label>
                <input type="file" id="pdfInput" accept="application/pdf">
            </div>
            
            <button type="submit">Check Plagiarism</button>
        </form>

        <!-- Loading Spinner -->
        <div id="loadingSpinner" class="spinner"></div>

        <!-- Result and Pie chart -->
        <div id="result" class="result" style="display: none;">
            <p><strong>Plagiarism Result:</strong></p>
            <p id="resultText"></p>
            <div class="pie-chart-container">
                <canvas id="plagiarismChart"></canvas>
            </div>
            <div class="link-container">
                <p>For more information, check the source:</p>
                <!-- Fake Archive Link (Books or Archives) -->
                <a href="https://www.archivebooks.com/" target="_blank" class="fake-link">Explore the Archive (ArchiveBooks)</a>
                <!-- Fake Google Scholar Link -->
                <a href="https://scholar.google.com/scholar" target="_blank" class="fake-link">Search Related Articles on Google Scholar</a>
            </div>
        </div>
    </div>

    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Simulate plagiarism checker result
        document.getElementById('plagiarism-form').addEventListener('submit', function(e) {
            e.preventDefault();

            // Show the loading spinner
            document.getElementById('loadingSpinner').style.display = 'block';
            const resultDiv = document.getElementById('result');
            resultDiv.style.display = 'none';  // Hide result initially

            const textInput = document.getElementById('textInput').value;
            const fileInput = document.getElementById('pdfInput').files[0];
            
            if (textInput.trim()) {
                // Simulate a result for the input text
                simulatePlagiarismResult(textInput);
            } else if (fileInput) {
                // Simulate a result for the uploaded file
                simulatePlagiarismResult(fileInput.name); // Just use file name for fake result
            } else {
                alert('Please enter some text or upload a PDF file to check.');
                document.getElementById('loadingSpinner').style.display = 'none';  // Hide the spinner
            }
        });

        // Simulate a plagiarism result with a random percentage
        function simulatePlagiarismResult(inputText) {
            // Simulate delay for fetching results (e.g., 2 seconds)
            setTimeout(function() {
                const randomPlagiarismScore = Math.floor(Math.random() * 101); // Generate a random score between 0-100
                const resultText = document.getElementById('resultText');
                const resultDiv = document.getElementById('result');
                
                // Simulate the result message
                if (randomPlagiarismScore < 20) {
                    resultText.innerHTML = `The content appears to be original. Plagiarism Score: ${randomPlagiarismScore}%`;
                } else if (randomPlagiarismScore < 50) {
                    resultText.innerHTML = `Some similarities were found. Plagiarism Score: ${randomPlagiarismScore}%`;
                } else {
                    resultText.innerHTML = `High likelihood of plagiarism detected. Plagiarism Score: ${randomPlagiarismScore}%`;
                }

                // Display pie chart
                displayPieChart(randomPlagiarismScore);

                // Hide the loading spinner and show results
                document.getElementById('loadingSpinner').style.display = 'none';
                resultDiv.style.display = 'block';  // Show result section
            }, 2000); // Simulate a 2-second delay for fetching the result
        }

        // Function to display the pie chart
        function displayPieChart(plagiarismScore) {
            const ctx = document.getElementById('plagiarismChart').getContext('2d');
            const plagiarismPercentage = plagiarismScore;
            const originalityPercentage = 100 - plagiarismPercentage;

            // Pie chart configuration
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['Plagiarized', 'Original'],
                    datasets: [{
                        data: [plagiarismPercentage, originalityPercentage],
                        backgroundColor: ['#ff6384', '#36a2eb'],
                        hoverBackgroundColor: ['#ff6384', '#36a2eb']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(tooltipItem) {
                                    return tooltipItem.label + ': ' + tooltipItem.raw + '%';
                                }
                            }
                        }
                    }
                }
            });
        }
    </script>
            <!-- View upload details Modal -->
        <div id="view_upload_details_modal" class="modal fade" tabindex="-1" aria-labelledby="view_details_modal_reservation" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-gray-700" id="exampleModalLabel">Thesis/Capstone Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body" id="upload_details">

                    </div>
                </div>
            </div>
        </div>
        <!-- End of View student Details Modal -->

        


        <!-- Delete  Modal -->
        <div class="modal fade" id="delete_modal" tabindex="-1" aria-labelledby="deletemodal" aria-hidden="true">
            <div class="modal-dialog ">
                <div class="modal-content">

                    <form action="#" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="upload_ID" id="delete_stud_id">
                        <div class="modal-body px-4 w3-center">
                            <i class="fa fa-check text-gray-400 fa-3x py-3"></i>
                            <h4> Are you sure to delete the uploaded Research Paper?</h4>
                            <h4 class="text-warning">This action cannot be undone!</h4>
                        </div>
                        <div class="pb-4 w3-center">
                            <button type="button" class="btn btn-warning w3-text-white px-5" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="delete_upload_btn" class="btn btn-primary px-5">Confirm</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>



            <?php
            include('../Includes/script.php');

            ?>




            <script>
                $(document).ready(function() {

                    load_data(1);

                    function load_data(page, query = '') {
                        $.ajax({
                            url: "fetch_upload.php",
                            method: "POST",
                            data: {
                                page: page,
                                query: query
                            },
                            success: function(data) {
                                $('#dynamic_content').html(data);
                            }
                        });
                    }


                    $(document).on('click', '.page-link', function() {
                        var page = $(this).data('page_number');
                        var query = $('#search_box').val();
                        load_data(page, query);
                    });



                    $('#search_box').keyup(function() {
                        var query = $('#search_box').val();
                        load_data(1, query);
                    });


                    $(document).on('click', '.editbtn', function() {
                        $('#editmodal').modal('show');

                        $tr = $(this).closest('tr');

                        var data = $tr.children("td").map(function() {
                            return $(this).text();
                        }).get();


                        console.log(data);
                        $('#deanID').val(data[0]);
                        $('#deanName').val(data[1]);
                        $('#deanUsername').val(data[2]);
                        $('#deanPassword').val(data[3]);

                    });

 


                    $(document).on('click', '.view_upload', function() {
                    var upload_id = $(this).attr("id");
                    if (upload_id != '') {
                        $.ajax({
                            url: "upload_details.php",
                            method: "POST",
                            data: {
                                upload_id: upload_id
                            },
                            success: function(data) {
                                $('#upload_details').html(data);
                                $('#view_upload_details_modal').modal('show');
                            }
                        });
                    }
                    });


                    $(document).on('click', '.confirm_btn', function(e) {
                    e.preventDefault();

                    var uploadID = $(this).closest('tr').find('.upload_id').text();
                    //console.log(staffid);
                    $('#confirm_stud_id').val(uploadID);
                    $('#confirmmodal').modal('show');
                    });


                    $(document).on('click', '.delete_btn', function(e) {
                    e.preventDefault();

                    var upload_ID = $(this).closest('tr').find('.upload_id').text();
                    //console.log(staffid);
                    $('#delete_stud_id').val(upload_ID);
                    $('#delete_modal').modal('show');
                    });




                });

                $(document).ready(function() {
                    $("#flash-msg").delay(2000).fadeOut("slow");
                });
            </script>