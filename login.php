<?php
$pageTitle = 'Login';
require_once 'includes/header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];
$username = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation
    if (empty($username)) {
        $errors['username'] = 'Username or email is required';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }

    // If no errors, attempt login
    if (empty($errors)) {
        if (loginUser($pdo, $username, $password)) {
            // Set persistent cookie if "Remember Me" was checked
            if (!empty($_POST['remember_me'])) {
                setRememberMeToken($pdo, $_SESSION['user_id']);
            }
            setFlashMessage('success', 'Welcome back, ' . $_SESSION['username'] . '!');
            redirect('index.php');
        } else {
            $errors['login'] = 'Invalid username/email or password';
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-card auth-card-wide">
        <!-- Left side: Image -->
        <div class="auth-image">
            <img src="images/login-illustration.svg" alt="Login illustration">
            <h2>Welcome Back!</h2>
            <p>Access your notes anytime, anywhere</p>
        </div>

        <!-- Right side: Form -->
        <div class="auth-content">
            <div class="auth-header">
                <h1>Login</h1>
                <p>Enter your credentials to continue</p>
            </div>

            <?php if (isset($errors['login'])): ?>
                <div class="alert alert-error"><?= $errors['login'] ?></div>
            <?php endif; ?>

            <form method="POST" action="" class="auth-form">
                <div class="form-group inline">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?= sanitize($username) ?>" placeholder="Username or Email" required>
                    <?php if (isset($errors['username'])): ?>
                        <span class="error"><?= $errors['username'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group inline">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Password" required>
                    <?php if (isset($errors['password'])): ?>
                        <span class="error"><?= $errors['password'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group inline remember-me">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember_me" value="1"
                               <?= !empty($_POST['remember_me']) ? 'checked' : '' ?>>
                        <span class="checkbox-text">Remember me for 30 days</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
