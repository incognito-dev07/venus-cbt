<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$user = findUserById($_SESSION['user_id']);
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['profile_image'])) {
  if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $error = "Invalid security token!";
  } else {
    $file = $_FILES['profile_image'];
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024;
        
    if ($file['error'] === UPLOAD_ERR_OK) {
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mime = finfo_file($finfo, $file['tmp_name']);
            
      if (!in_array($mime, $allowed_types)) {
        $error = "Only JPG, PNG, and GIF images are allowed!";
      } elseif ($file['size'] > $max_size) {
        $error = "Image size must be less than 5MB!";
      } else {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'user_' . $user['id'] . '.' . $extension;
        $upload_path = UPLOAD_DIR . $filename;
                
        $old_files = glob(UPLOAD_DIR . 'user_' . $user['id'] . '.*');
        foreach ($old_files as $old_file) {
          if (file_exists($old_file)) {
            unlink($old_file);
          }
        }
                
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
          $db = getDB();
          $stmt = $db->prepare('UPDATE users SET profile_image = :image WHERE id = :id');
          $stmt->bindValue(':image', $upload_path, SQLITE3_TEXT);
          $stmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
          $stmt->execute();
                    
          header("Location: /profile?upload=success");
          exit();
        } else {
          $error = "Failed to upload image!";
        }
      }
    } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
      $error = "Error uploading file. Code: " . $file['error'];
    }
  }
}

if ($error) {
  $_SESSION['upload_error'] = $error;
  header("Location: /profile");
  exit();
}