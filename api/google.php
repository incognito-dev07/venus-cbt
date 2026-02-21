<?php
require_once __DIR__ . '/../includes/config.php';

if (isLoggedIn()) {
  header("Location: /");
  exit();
}

$client_id = GOOGLE_CLIENT_ID;
$client_secret = GOOGLE_CLIENT_SECRET;
$redirect_uri = GOOGLE_REDIRECT_URI;

if (!isset($_GET['code']) && !isset($_GET['error'])) {
  $state = bin2hex(random_bytes(16));
  $_SESSION['google_state'] = $state;
  
  $params = [
    'client_id' => $client_id,
    'redirect_uri' => $redirect_uri,
    'response_type' => 'code',
    'scope' => 'email profile',
    'access_type' => 'online',
    'state' => $state,
    'prompt' => 'select_account'
  ];
  
  $auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
  
  header('Location: ' . $auth_url);
  exit();
}

if (isset($_GET['error'])) {
  $error = $_GET['error'];
  header("Location: /login?error=google_$error");
  exit();
}

if (isset($_GET['code'])) {
  if (!isset($_GET['state']) || !isset($_SESSION['google_state']) || $_GET['state'] !== $_SESSION['google_state']) {
    header("Location: /login?error=google_invalid_state");
    exit();
  }
  
  $token_data = [
    'code' => $_GET['code'],
    'client_id' => $client_id,
    'client_secret' => $client_secret,
    'redirect_uri' => $redirect_uri,
    'grant_type' => 'authorization_code'
  ];
  
  $result = curlPost('https://oauth2.googleapis.com/token', $token_data);
  
  if ($result['code'] !== 200) {
    header("Location: /login?error=google_token_exchange");
    exit();
  }
  
  $token = json_decode($result['response'], true);
  
  if (isset($token['error'])) {
    header("Location: /login?error=google_" . $token['error']);
    exit();
  }
  
  $user_info = curlGet('https://www.googleapis.com/oauth2/v2/userinfo', $token['access_token']);
  $google_user = json_decode($user_info, true);
  
  if (!isset($google_user['email'])) {
    header("Location: /login?error=google_no_email");
    exit();
  }
  
  $email = $google_user['email'];
  $name = $google_user['name'];
  $google_id = $google_user['id'];
  $picture = $google_user['picture'] ?? null;
  
  $db = getDB();
  
  $check = $db->prepare('SELECT * FROM users WHERE email = :email');
  $check->bindValue(':email', $email, SQLITE3_TEXT);
  $existing_user = $check->execute()->fetchArray(SQLITE3_ASSOC);
  
  if ($existing_user) {
    $update = $db->prepare('UPDATE users SET google_id = :google_id, auth_provider = :provider WHERE id = :id');
    $update->bindValue(':google_id', $google_id, SQLITE3_TEXT);
    $update->bindValue(':provider', 'google', SQLITE3_TEXT);
    $update->bindValue(':id', $existing_user['id'], SQLITE3_INTEGER);
    $update->execute();
    
    if (empty($existing_user['profile_image']) && $picture) {
      $update_img = $db->prepare('UPDATE users SET profile_image = :image WHERE id = :id');
      $update_img->bindValue(':image', $picture, SQLITE3_TEXT);
      $update_img->bindValue(':id', $existing_user['id'], SQLITE3_INTEGER);
      $update_img->execute();
    }
    
    $_SESSION['user_id'] = $existing_user['id'];
    $_SESSION['username'] = $existing_user['username'];
    
  } else {
    $privacy = json_encode(['hide_avg_leaderboard' => false]);
    
    $insert = $db->prepare('INSERT INTO users (username, email, google_id, profile_image, bio, auth_provider, privacy_settings) 
                            VALUES (:username, :email, :google_id, :image, :bio, :provider, :privacy)');
    
    $insert->bindValue(':username', $name, SQLITE3_TEXT);
    $insert->bindValue(':email', $email, SQLITE3_TEXT);
    $insert->bindValue(':google_id', $google_id, SQLITE3_TEXT);
    $insert->bindValue(':image', $picture, SQLITE3_TEXT);
    $insert->bindValue(':bio', 'Welcome to my profile!', SQLITE3_TEXT);
    $insert->bindValue(':provider', 'google', SQLITE3_TEXT);
    $insert->bindValue(':privacy', $privacy, SQLITE3_TEXT);
    
    $insert->execute();
    $user_id = $db->lastInsertRowID();
    
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $name;
  }
  
  unset($_SESSION['google_state']);
  
  header("Location: /profile");
  exit();
}

header("Location: /login?error=google_unknown");
exit();