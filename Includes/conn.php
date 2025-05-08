<?php 
    $host = "localhost";
    $username = "root";
    $password = "";
    $database = "tcaps_g8_system";

	$db = new mysqli($host, $username, $password, $database);

    if($db->connect_error){
        echo 'Not Connected';
    }else{

    }
    ?>
    
    
    
