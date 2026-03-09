<?php
/**
 * Database Configuration
 * Simple Notes App - Supabase PostgreSQL Version
 */

require_once __DIR__ . '/../includes/env_loader.php';
loadEnv(__DIR__ . '/../.env');

$dbUrl = getenv('DATABASE_URL');

if (!$dbUrl) {
    die("DATABASE_URL environment variable is not set.");
}

// Parse the URL
$parts = parse_url($dbUrl);

$host = $parts['host'];
$port = $parts['port'] ?? 5432;
$dbname = ltrim($parts['path'], '/');
$user = $parts['user'];
$password = $parts['pass'];

$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

try {
    $pdo = new PDO(
        $dsn,
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Initialize database if tables don't exist
    initializeDatabase($pdo);

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

/**
 * Initialize PostgreSQL database with tables and sample data
 */
function initializeDatabase($pdo) {
    // Create Users Table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) DEFAULT NULL,
            bio TEXT DEFAULT NULL,
            profile_image VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Add columns if they don't exist (for existing databases)
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS full_name VARCHAR(100) DEFAULT NULL");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS bio TEXT DEFAULT NULL");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) DEFAULT NULL");
    } catch (PDOException $e) {
        // Ignore error if columns exist (generic fallback for older Postgres versions that don't support IF NOT EXISTS in ALTER)
    }


    // Create Categories Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id SERIAL PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            color VARCHAR(7) DEFAULT '#667eea',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Insert default categories if table is empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
    $catCount = $stmt->fetchColumn();
    
    if ($catCount == 0) {
        $pdo->exec("
            INSERT INTO categories (name, color) VALUES
            ('Personal', '#e91e63'),
            ('Work', '#2196f3'),
            ('Ideas', '#ff9800'),
            ('Shopping', '#4caf50'),
            ('Important', '#f44336')
        ");
    }

    // Create Notes Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notes (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NULL REFERENCES users(id) ON DELETE CASCADE,
            title VARCHAR(255) NOT NULL,
            content TEXT,
            color VARCHAR(7) DEFAULT '#ffffff',
            is_pinned INTEGER DEFAULT 0,
            is_archived INTEGER DEFAULT 0,
            category_id INTEGER NULL REFERENCES categories(id) ON DELETE SET NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Create indexes (Postgres specific if not exists logic is bit different, but CREATE INDEX IF NOT EXISTS works in modern PG)
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_notes_is_archived ON notes(is_archived)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_notes_is_pinned ON notes(is_pinned)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_notes_updated_at ON notes(updated_at)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_notes_user_id ON notes(user_id)");
}