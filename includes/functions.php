<?php
/**
 * Helper Functions
 * Simple Notes App
 */

/**
 * Sanitize string input
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a URL
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Set flash message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Format date for display
 */
function formatDate($date) {
    return date('M j, Y g:i A', strtotime($date));
}


/**
 * Normalize note content for display: trim lines, remove unwanted spaces, align text properly
 */
function normalizeNoteContentForDisplay($content) {
    if ($content === null || $content === '') {
        return '';
    }
    $content = trim($content);
    $lines = explode("\n", $content);
    $normalized = [];
    foreach ($lines as $line) {
        $line = trim($line);
        $line = preg_replace('/\s+/', ' ', $line); // collapse multiple spaces to one
        $normalized[] = $line;
    }
    return implode("\n", $normalized);
}

/**
 * Get all notes for current user
 */
function getAllNotes($pdo, $archived = false) {
    $userId = $_SESSION['user_id'] ?? null;

    $sql = "SELECT n.*, c.name as category_name, c.color as category_color
            FROM notes n
            LEFT JOIN categories c ON n.category_id = c.id
            WHERE n.is_archived = :archived";

    if ($userId) {
        $sql .= " AND n.user_id = :user_id";
    } else {
        $sql .= " AND n.user_id IS NULL";
    }

    $sql .= " ORDER BY n.is_pinned DESC, n.updated_at DESC";

    $stmt = $pdo->prepare($sql);
    $params = ['archived' => $archived ? 1 : 0];
    if ($userId) {
        $params['user_id'] = $userId;
    }
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Get single note by ID (only if owned by current user)
 */
function getNoteById($pdo, $id) {
    $userId = $_SESSION['user_id'] ?? null;

    if ($userId) {
        $stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ? AND user_id IS NULL");
        $stmt->execute([$id]);
    }
    return $stmt->fetch();
}

/**
 * Get all categories
 */
function getAllCategories($pdo) {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    return $stmt->fetchAll();
}

/**
 * Search notes for current user
 */
function searchNotes($pdo, $query) {
    $userId = $_SESSION['user_id'] ?? null;

    $sql = "SELECT n.*, c.name as category_name, c.color as category_color
            FROM notes n
            LEFT JOIN categories c ON n.category_id = c.id
            WHERE n.is_archived = 0
            AND (n.title ILIKE :query OR n.content ILIKE :query)";

    if ($userId) {
        $sql .= " AND n.user_id = :user_id";
    } else {
        $sql .= " AND n.user_id IS NULL";
    }

    $sql .= " ORDER BY n.updated_at DESC";

    $stmt = $pdo->prepare($sql);
    $params = ['query' => "%$query%"];
    if ($userId) {
        $params['user_id'] = $userId;
    }
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Toggle pin status
 */
function togglePin($pdo, $id) {
    $stmt = $pdo->prepare("UPDATE notes SET is_pinned = 1 - is_pinned WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Archive a note
 */
function archiveNote($pdo, $id) {
    $stmt = $pdo->prepare("UPDATE notes SET is_archived = 1 WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Restore a note from archive
 */
function restoreNote($pdo, $id) {
    $stmt = $pdo->prepare("UPDATE notes SET is_archived = 0 WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Get available note colors
 */
function getNoteColors() {
    return [
        '#ffffff' => 'White',
        '#fff9c4' => 'Yellow',
        '#c8e6c9' => 'Green',
        '#bbdefb' => 'Blue',
        '#f8bbd0' => 'Pink',
        '#ffccbc' => 'Orange',
        '#e1bee7' => 'Purple',
        '#b2ebf2' => 'Cyan'
    ];
}

// ==========================================
// Authentication Functions
// ==========================================

/**
 * Register a new user
 */
function registerUser($pdo, $username, $email, $password) {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password)
        VALUES (?, ?, ?)
    ");

    return $stmt->execute([$username, $email, $hashedPassword]);
}

/**
 * Check if username exists
 */
function usernameExists($pdo, $username) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch() !== false;
}

/**
 * Check if email exists
 */
function emailExists($pdo, $email) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch() !== false;
}

/**
 * Authenticate user login
 */
function loginUser($pdo, $username, $password) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['profile_image'] = $user['profile_image'] ?? null;
        return true;
    }

    return false;
}

/**
 * Logout user
 */
function logoutUser() {
    unset($_SESSION['user_id']);
    unset($_SESSION['username']);
    unset($_SESSION['email']);
    unset($_SESSION['profile_image']);
    session_destroy();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current logged in user
 */
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'profile_image' => $_SESSION['profile_image'] ?? null
        ];
    }
    return null;
}

/**
 * Update user profile
 */
function updateUser($pdo, $userId, $fullName, $bio, $profileImage = null) {
    $sql = "UPDATE users SET full_name = :full_name, bio = :bio";
    $params = [
        'full_name' => $fullName,
        'bio' => $bio,
        'user_id' => $userId
    ];

    if ($profileImage) {
        $sql .= ", profile_image = :profile_image";
        $params['profile_image'] = $profileImage;
    }

    $sql .= " WHERE id = :user_id";

    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

/**
 * Require user to be logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Please login to access this page.');
        redirect('login.php');
    }
}

// ==========================================
// Note Form Helpers
// ==========================================

/**
 * Validate and sanitize note form input.
 *
 * Usage:
 *   $result = validateNoteInput($_POST);
 *   $errors = $result['errors'];   // array of field => message
 *   $data   = $result['data'];     // clean, ready-to-use values
 */
function validateNoteInput(array $post): array {
    $data = [
        'title'       => trim($post['title']       ?? ''),
        'content'     => trim($post['content']     ?? ''),
        'color'       => $post['color']             ?? '#ffffff',
        'category_id' => $post['category_id'] ?: null,
    ];

    // Remove unwanted spaces and align content properly before save
    $data['content'] = normalizeNoteContentForDisplay($data['content']);

    $errors = [];

    if (empty($data['title'])) {
        $errors['title'] = 'Title is required';
    } elseif (strlen($data['title']) > 255) {
        $errors['title'] = 'Title must be less than 255 characters';
    }

    return ['errors' => $errors, 'data' => $data];
}

// ==========================================
// Remember Me Helpers
// ==========================================

define('REMEMBER_ME_COOKIE', 'remember_me');
define('REMEMBER_ME_DAYS',   30);

/**
 * Generate a secure token, store it in DB, and set a cookie.
 * Call this after a successful login when "Remember Me" is checked.
 */
function setRememberMeToken($pdo, int $userId): void {
    $token  = bin2hex(random_bytes(32));   // 64-char cryptographically secure token
    $expiry = date('Y-m-d H:i:s', strtotime('+' . REMEMBER_ME_DAYS . ' days'));

    $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
    $stmt->execute([$token, $userId]);

    // HttpOnly + SameSite=Lax prevents JavaScript access and CSRF
    setcookie(
        REMEMBER_ME_COOKIE,
        $token,
        [
            'expires'  => time() + (REMEMBER_ME_DAYS * 24 * 60 * 60),
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );
}

/**
 * Check for a remember-me cookie and, if valid, restore the session.
 * Call this early on every page (before isLoggedIn() checks).
 */
function loginUserWithToken($pdo): void {
    // Already logged in — nothing to do
    if (isset($_SESSION['user_id'])) {
        return;
    }

    $token = $_COOKIE[REMEMBER_ME_COOKIE] ?? '';
    if (empty($token)) {
        return;
    }

    // Look up user by token
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        // Restore session (same as loginUser)
        $_SESSION['user_id']       = $user['id'];
        $_SESSION['username']      = $user['username'];
        $_SESSION['email']         = $user['email'];
        $_SESSION['profile_image'] = $user['profile_image'] ?? null;

        // Rotate token on each use (prevents token reuse if cookie is stolen)
        setRememberMeToken($pdo, $user['id']);
    } else {
        // Invalid/expired token — clear the cookie
        clearRememberMeToken($pdo);
    }
}

/**
 * Clear the remember-me token from DB and delete the cookie.
 * Call this on logout.
 */
function clearRememberMeToken($pdo): void {
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    }

    // Delete cookie by setting expiry in the past
    setcookie(REMEMBER_ME_COOKIE, '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

