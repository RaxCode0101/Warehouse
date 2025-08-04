<?php
session_start();
$_SESSION['user_id'] = 1; // Assuming user ID 1 exists

// Simulate a file upload
$_FILES['profilePicture'] = [
    'name' => 'test.jpg',
    'type' => 'image/jpeg',
    'size' => 1024,
    'tmp_name' => 'test.jpg',
    'error' => 0
];

$_POST['action'] = 'upload_profile_picture';

// Include the auth.php file to test the upload functionality
include 'auth.php';
