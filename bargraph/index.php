<?php
$con = mysqli_connect("localhost","root","","tcaps_g8_system");
if(!$con){
    echo "Problem in database connection..." .mysqli_error();
}else{
    $sql = "SELECT * FROM upload";
    $result = mysqli_query($con,$sql);
    $chart_data = "";
    while($row = mysqli_fetch_array($result)){
        $studname[] = $row['upload_id'];
        $studsec[] = $row['upload_department'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Reports</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
</head>

<body>
  <div class="row">
    <div class="col-md-8 offset-md-2">
      <div class="card">
      <div class="card-header bg">
        <h1>Reports</h1>
      </div>
          <div class="card-body">
          <canvas id="chartjs_bar"></canvas>
          </div>
      </div>
    </div>
  </div>
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/Chart.js/2.4.0/Chart.min.js"></script>
<script type="text/javascript">
      var ctx = document.getElementById("chartjs_bar").getContext('2d');
      var myChart = new Chart(ctx,{
          type: 'bar',
          data: {
            labels:<?php echo json_encode($studname); ?>,
            datasets: [{
                backgroundcolor: [
                    "#ffd322",
                    "#5945fd",
                    "#25d5f2",
                    "#2ec551",
                    "#ff344e",
                ],
                data: <?php echo json_encode($studsec);?>
            }]
          },
          options:{
              legend: {
                  display:true,
                  position:'bottom',
                  labels: {
                      fontColor: '#71748d',
                      fontFamily: 'Circular Std Book',
                      fontSize: 14,
                  }
              },
          }
      });
    </script>
</body>
</html>