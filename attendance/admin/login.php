<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
include 'config/db.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['emp_id']) && $_SESSION['emp_id'] > 0) {
  header("Location: dashboard/index.php");
  exit;
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = isset($_POST['email']) ? trim($_POST['email']) : '';
  $password = isset($_POST['password']) ? trim($_POST['password']) : '';

  if (empty($email) || empty($password)) {
    $error = 'Please enter both email and password.';
  } else {
    // Check employee credentials
    $stmt = $con->prepare("SELECT user_id, emp_code, name, email, status FROM employees WHERE email = ? AND status = 1 LIMIT 1");
    if (!$stmt) {
      $error = 'Database error: ' . $con->error;
    } else {
      $stmt->bind_param("s", $email);
      $stmt->execute();
      $result = $stmt->get_result();

      if ($result && $result->num_rows > 0) {
        $employee = $result->fetch_assoc();

        // Default password is 123456
        if ($password === '123456') {
          // Set session variables
          $_SESSION['emp_id'] = $employee['user_id'];
          $_SESSION['emp_name'] = $employee['name'];
          $_SESSION['emp_code'] = $employee['emp_code'];
          $_SESSION['emp_email'] = $employee['email'];

          // Sync localStorage for multi-tab support and redirect
          echo "<script>
            localStorage.setItem('admin_logged_in', Date.now());
            localStorage.removeItem('admin_logged_out');
            window.location.href = 'dashboard/index.php';
          </script>";
          exit;
        } else {
          $error = 'Invalid password. Please try again.';
        }
      } else {
        $error = 'Employee not found or account is inactive.';
      }
      $stmt->close();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Attendance System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .login-container {
      width: 100%;
      max-width: 420px;
    }

    .login-card {
      background: white;
      border-radius: 24px;
      padding: 40px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }

    .login-header {
      text-align: center;
      margin-bottom: 32px;
    }

    .login-logo {
      width: 72px;
      height: 72px;
      background: linear-gradient(135deg, #3b82f6, #8b5cf6);
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      font-size: 2rem;
    }

    .login-header h1 {
      font-size: 1.75rem;
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 8px;
    }

    .login-header p {
      font-size: 0.95rem;
      color: #64748b;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      font-size: 0.875rem;
      font-weight: 500;
      color: #374151;
      margin-bottom: 8px;
    }

    .form-group input {
      width: 100%;
      padding: 14px 16px;
      border: 2px solid #e5e7eb;
      border-radius: 12px;
      font-size: 1rem;
      transition: all 0.2s;
      background: #f9fafb;
    }

    .form-group input:focus {
      outline: none;
      border-color: #3b82f6;
      background: white;
      box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }

    .form-group input::placeholder {
      color: #9ca3af;
    }

    .btn-login {
      width: 100%;
      padding: 14px;
      border: none;
      border-radius: 12px;
      background: linear-gradient(135deg, #3b82f6, #2563eb);
      color: white;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      margin-top: 8px;
    }

    .btn-login:hover {
      background: linear-gradient(135deg, #2563eb, #1d4ed8);
      transform: translateY(-2px);
      box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
    }

    .error-message {
      background: #fef2f2;
      border: 1px solid #fecaca;
      color: #dc2626;
      padding: 12px 16px;
      border-radius: 10px;
      font-size: 0.9rem;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .divider {
      display: flex;
      align-items: center;
      margin: 24px 0;
      color: #9ca3af;
      font-size: 0.85rem;
    }

    .divider::before,
    .divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: #e5e7eb;
    }

    .divider span {
      padding: 0 16px;
    }

    .quick-login {
      text-align: center;
    }

    .quick-login p {
      font-size: 0.85rem;
      color: #64748b;
      margin-bottom: 16px;
    }

    .employee-pills {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      justify-content: center;
    }

    .employee-pill {
      padding: 8px 16px;
      background: #f1f5f9;
      border: 1px solid #e2e8f0;
      border-radius: 20px;
      font-size: 0.85rem;
      color: #475569;
      cursor: pointer;
      transition: all 0.2s;
      text-decoration: none;
    }

    .employee-pill:hover {
      background: #e0f2fe;
      border-color: #3b82f6;
      color: #1d4ed8;
    }

    .back-link {
      display: block;
      text-align: center;
      margin-top: 24px;
      color: white;
      font-size: 0.9rem;
      text-decoration: none;
      opacity: 0.9;
    }

    .back-link:hover {
      opacity: 1;
      color: white;
      text-decoration: underline;
    }

    .password-hint {
      font-size: 0.75rem;
      color: #9ca3af;
      margin-top: 6px;
    }
  </style>
</head>

<body>

  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <div class="login-logo">🔐</div>
        <h1>Welcome Back</h1>
        <p>Sign in to your attendance dashboard</p>
      </div>

      <?php if ($error): ?>
        <div class="error-message">
          <span>⚠️</span> <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" placeholder="Enter your email" required
            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="Enter your password" required>
          <div class="password-hint">Default password: 123456</div>
        </div>

        <button type="submit" class="btn-login">Sign In</button>
      </form>
    </div>

    <a href="hr/employees.php" class="back-link">← Back to HR Panel</a>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>