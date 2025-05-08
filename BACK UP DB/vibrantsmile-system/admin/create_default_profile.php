<?php
// Create assets/img directory if it doesn't exist
$assets_dir = "../assets/img/";
if(!file_exists($assets_dir)){
    mkdir($assets_dir, 0777, true);
}

// Create uploads directory if it doesn't exist
$upload_dir = "../uploads/profile_pictures/";
if(!file_exists($upload_dir)){
    mkdir($upload_dir, 0777, true);
}

// Copy default profile picture from assets to img directory
$default_profile = "../assets/img/default-profile.png";
if(!file_exists($default_profile)){
    // Create a simple SVG profile picture
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">
        <rect width="200" height="200" fill="#f0f0f0"/>
        <circle cx="100" cy="80" r="40" fill="#787878"/>
        <path d="M100 130 C40 130 20 180 20 200 L180 200 C180 180 160 130 100 130Z" fill="#787878"/>
    </svg>';
    
    file_put_contents($default_profile, $svg);
}

echo "Default profile picture created successfully!";
?> 