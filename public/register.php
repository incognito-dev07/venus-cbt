<?php
require_once __DIR__ . '/../includes/config.php';

if (isLoggedIn()) {
  header("Location: /");
  exit();
}

$error = '';
$csrf_token = generateCSRFToken();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $error = "Invalid security token!";
  } else {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($username) || empty($email) || empty($password)) {
      $error = "All fields are required!";
    } elseif ($password !== $confirm_password) {
      $error = "Passwords do not match!";
    } elseif (strlen($password) < 8) {
      $error = "Password must be at least 8 characters!";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
      $error = "Password must contain at least one uppercase letter, one lowercase letter, and one number!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $error = "Invalid email format!";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
      $error = "Username must be 3-50 characters and can only contain letters, numbers, and underscores!";
    } else {
      $db = getDB();
      
      $check_user = $db->prepare('SELECT id FROM users WHERE username = :username');
      $check_user->bindValue(':username', $username, SQLITE3_TEXT);
      $existing_user = $check_user->execute()->fetchArray();
      
      $check_email = $db->prepare('SELECT id FROM users WHERE email = :email');
      $check_email->bindValue(':email', $email, SQLITE3_TEXT);
      $existing_email = $check_email->execute()->fetchArray();
      
      if ($existing_user) {
        $error = "Username already exists!";
      } elseif ($existing_email) {
        $error = "Email already registered!";
      } else {
        $hash_algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        $hashed_password = password_hash($password, $hash_algo);
        
        $privacy = json_encode(['hide_avg_leaderboard' => false]);
        
        $stmt = $db->prepare('INSERT INTO users (username, email, password, bio, privacy_settings) 
                              VALUES (:username, :email, :password, :bio, :privacy)');
        
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':password', $hashed_password, SQLITE3_TEXT);
        $stmt->bindValue(':bio', 'Welcome to my profile!', SQLITE3_TEXT);
        $stmt->bindValue(':privacy', $privacy, SQLITE3_TEXT);
        
        $stmt->execute();
        
        header("Location: /login?registered=1");
        exit();
      }
    }
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Venus CBT - Register</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="/css/core.css">
  <link rel="stylesheet" href="/css/component.css">
  <link rel="stylesheet" href="/css/pages.css">
  <link rel="stylesheet" href="/css/responsive.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
</head>
<body>
  <?php include 'navbar.php'; ?>

  <div class="container" style="position: relative; bottom: 40px">
    <div class="form-box">
      <h2><i class="fas fa-user-plus"></i> Create Account</h2>
            
      <?php if ($error): ?>
        <div class="error">
          <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>
            
      <form method="post" action="">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <div class="form-group">
          <label><i class="fas fa-user"></i> Username</label>
          <input type="text" name="username" required pattern="[a-zA-Z0-9_]{3,50}" title="3-50 characters, letters, numbers, and underscores only">
        </div>
        
        <div class="form-group">
          <label><i class="fas fa-envelope"></i> Email</label>
          <input type="email" name="email" required maxlength="255">
        </div>
                
        <div class="form-group">
          <label><i class="fas fa-lock"></i> Password</label>
          <input type="password" name="password" required minlength="8" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$" title="Must contain at least 8 characters, one uppercase, one lowercase, and one number">
        </div>
                
        <div class="form-group">
          <label><i class="fas fa-lock"></i> Confirm Password</label>
          <input type="password" name="confirm_password" required minlength="8">
        </div>
                
        <button type="submit" class="btn btn-primary btn-block">
          <i class="fas fa-user-plus"></i> Register
        </button>
      </form>
      
      <div class="divider">
        <span>OR</span>
      </div>
      
      <a href="/api/google" class="btn btn-google btn-block">
        <i class="fab fa-google"></i> Sign up with Google
      </a>
            
      <div class="footer-text">
        <span style="font-weight: 500;">Already have an account?</span><br>
        <a href="/login" style="text-decoration: none;"><i class="fas fa-sign-in-alt"></i> Login here</a>
      </div>
    </div>
  </div>
    
  <script src="/js/script.js"></script>
</body>
</html>