<?php
include('../Includes/header.php');

include('../Includes/dean_navbar.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plagiarism Checker</title>
    <style>
        body {
            font-family: Book Antiqua;
            margin: 0px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
            font-family: Cornerstone;
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
            background-color: #1F75FE;
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
                <textarea id="textInput" placeholder="Enter text or upload file to check for plagiarism" ></textarea>
            </div>
            
            <div class="form-group">
                <label for="pdfInput">Upload a PDF File:</label>
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
</body>
</html>
