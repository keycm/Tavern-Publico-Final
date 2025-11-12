<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["avatarFile"])) {
    $user_id = $_SESSION['user_id'];
    $target_dir = "uploads/avatars/";
    $file = $_FILES["avatarFile"];
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid('', true) . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // --- Validation Checks ---
    // Check if image file is a actual image or fake image
    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        die("Error: File is not an image.");
    }

    // Check file size (e.g., limit to 2MB)
    if ($file["size"] > 2000000) {
        die("Error: Sorry, your file is too large.");
    }

    // Allow certain file formats
    $allowed_types = ['jpg', 'png', 'jpeg', 'gif'];
    if (!in_array($file_extension, $allowed_types)) {
        die("Error: Sorry, only JPG, JPEG, PNG & GIF files are allowed.");
    }
    
    // --- Upload File and Update Database ---
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        // Update database with new avatar path
        $sql = "UPDATE users SET avatar = ? WHERE user_id = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "si", $target_file, $user_id);
            if (mysqli_stmt_execute($stmt)) {
                // Update session variable
                $_SESSION['avatar'] = $target_file;
                // Redirect back to profile page on success
                header('Location: profile.php?status=success');
                exit;
            }
        }
    }
}

// Redirect back to profile page on error
header('Location: profile.php?status=error');
exit;
?>