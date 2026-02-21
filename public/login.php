<?php
require_once __DIR__ . '/../includes/config.php';

if (isLoggedIn()) {
  header("Location: /");
  exit();
}

$error = '';
$success = '';

if (isset($_GET['error'])) {
  switch($_GET['error']) {
    case 'google_access_denied':
      $error = "Google sign-in was cancelled.";
      break;
    case 'google_invalid_state':
      $error = "Security verification failed. Please try again.";
      break;
    case 'google_token_exchange':
      $error = "Failed to connect to Google. Please try again.";
      break;
    case 'google_no_email':
      $error = "Could not retrieve email from Google.";
      break;
    case 'google_auth_failed':
      $error = "Google authentication failed. Please try again.";
      break;
    default:
      $error = "An error occurred. Please try again.";
  }
}

if (isset($_GET['registered'])) {
  $success = "Registration successful! Please login.";
}

$csrf_token = generateCSRFToken();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $error = "Invalid security token!";
  } else {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
      $error = "Email and password are required!";
    } else {
      $user = findUserByEmail($email);
        
      if ($user && isset($user['password']) && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['login_time'] = time();
        
        header("Location: /");
        exit();
      } else {
        $error = "Invalid email or password!";
      }
    }
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Venus CBT - Login</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="/css/core.css">
  <link rel="stylesheet" href="/css/component.css">
  <link rel="stylesheet" href="/css/pages.css">
  <link rel="stylesheet" href="/css/responsive.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
</head>
<body>
  <?php include 'navbar.php'; ?>

  <div class="container" style="position: relative; bottom: 30px">
    <div class="form-box">
      <h2><i class="fas fa-sign-in-alt"></i> Login</h2>
            
      <?php if ($error): ?>
        <div class="error">
          <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>
            
      <?php if ($success): ?>
        <div class="success">
          <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
      <?php endif; ?>
            
      <form method="post" action="">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <div class="form-group">
          <label><i class="fas fa-envelope"></i> Email</label>
          <input type="email" name="email" required maxlength="255">
        </div>
                
        <div class="form-group">
          <label><i class="fas fa-lock"></i> Password</label>
          <input type="password" name="password" required>
        </div>
                
        <button type="submit" class="btn btn-primary btn-block">
          <i class="fas fa-sign-in-alt"></i> Login
        </button>
      </form>
      
      <div class="divider">
        <span>OR</span>
      </div>
      
      <a href="/api/google" class="btn btn-google btn-block">
        <i class="fab fa-google"></i> Sign in with Google
      </a>
            
      <div class="footer-text">
        <span style="font-weight: 500;">Don't have an account?</span><br>
        <a href="/register" style="text-decoration: none;"><i class="fas fa-user-plus"></i> Register here</a>
      </div>
    </div>
  </div>
    
  <script src="/js/script.js"></script>
</body>
</html>