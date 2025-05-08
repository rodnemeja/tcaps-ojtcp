<?php  

include_once("Includes/conn.php");

 if(!empty($_GET["file"]))  {

    $filename = basename($_GET["file"]);
    $path = "Upload/".$filename;

    if(!empty($filename) && file_exists($path)){
        header('Cache-control: public');
        header('Content-description: File Transfer');
        header('Content-disposition: attachement; filename="'.$filename.'"');
        header('Content-type: application/pdf');
        header('Content-transfer-encoding: binary');

        readfile($path);
        exit;
    }else{
        echo 'File not EXISTS';
    }
 }
?>
