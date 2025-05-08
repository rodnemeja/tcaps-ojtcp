
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
