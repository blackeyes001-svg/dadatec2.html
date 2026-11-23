<?php
// Database connection (PDO), session start and helper functions.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$db   = 'result_system';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $ex) {
    die('Database connection failed. Check the settings in config.php and make sure MySQL is running.');
}

// Escape output (prevents XSS)
function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

// Send the user to login if not logged in, or if the role does not match
function require_login($role = null)
{
    if (!is_logged_in() || ($role !== null && $_SESSION['role'] !== $role)) {
        header('Location: login.php');
        exit;
    }
}

// CSRF protection for forms
function csrf_token()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_check()
{
    $sent = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(419);
        die('Invalid or expired form. Go back, refresh the page and try again.');
    }
}

// One-time messages (e.g. "Student added")
function set_flash($message, $type = 'success')
{
    $_SESSION['flash'] = [$message, $type];
}

function show_flash()
{
    if (!isset($_SESSION['flash'])) {
        return '';
    }
    list($message, $type) = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return '<div class="alert ' . e($type) . '">' . e($message) . '</div>';
}

// Convert a score to a grade letter and grade point (5-point scale)
function grade_from_score($score)
{
    $score = (int)$score;
    if ($score >= 70) return ['A', 5];
    if ($score >= 60) return ['B', 4];
    if ($score >= 50) return ['C', 3];
    if ($score >= 45) return ['D', 2];
    if ($score >= 40) return ['E', 1];
    return ['F', 0];
}
