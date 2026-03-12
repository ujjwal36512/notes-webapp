<?php
$pageTitle = 'Register';
require_once 'includes/header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];
$username = '';
$email = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($username)) {
        $errors['username'] = 'Username is required';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors['username'] = 'Username must be between 3 and 50 characters';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors['username'] = 'Username can only contain letters, numbers, and underscores';
    } elseif (usernameExists($pdo, $username)) {
        $errors['username'] = 'Username is already taken';
    }

    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    } elseif (emailExists($pdo, $email)) {
        $errors['email'] = 'Email is already registered';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters';
    }

    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match';
    }

    // If no errors, register the user
    if (empty($errors)) {
        try {
            if (registerUser($pdo, $username, $email, $password)) {
                // Auto login after registration
                loginUser($pdo, $username, $password);
                setFlashMessage('success', 'Registration successful! Welcome, ' . $username . '!');
                redirect('index.php');
            }
        } catch (PDOException $e) {
            $errors['database'] = 'Registration failed. Please try again.';
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-card auth-card-wide">
        <!-- Left side: Image -->
        <div class="auth-image">
            <img src="images/register-illustration.svg" alt="Register illustration">
            <h2>Organize Your Thoughts</h2>
            <p>Keep all your notes in one place</p>
        </div>

        <!-- Right side: Form -->
        <div class="auth-content">
            <div class="auth-header">
                <h1>Create Account</h1>
                <p>Join us and start organizing your notes</p>
            </div>

            <?php if (isset($errors['database'])): ?>
                <div class="alert alert-error"><?= $errors['database'] ?></div>
            <?php endif; ?>

            <form method="POST" action="" class="auth-form">
                <div class="form-group inline">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?= sanitize($username) ?>" placeholder="Username" maxlength="50" required>
                    <?php if (isset($errors['username'])): ?>
                        <span class="error"><?= $errors['username'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group inline">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= sanitize($email) ?>" placeholder="Email" required>
                    <?php if (isset($errors['email'])): ?>
                        <span class="error"><?= $errors['email'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group inline">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Password" minlength="6" required>
                    <?php if (isset($errors['password'])): ?>
                        <span class="error"><?= $errors['password'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group inline">
                    <label for="confirm_password">Confirm</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm" required>
                    <?php if (isset($errors['confirm_password'])): ?>
                        <span class="error"><?= $errors['confirm_password'] ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Get Started</button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
