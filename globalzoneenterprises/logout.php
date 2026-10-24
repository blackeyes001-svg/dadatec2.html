<?php
session_start();

// Remove the session data.
$_SESSION = [];

// Expire the session cookie.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy the session.
session_destroy();

// Return the customer to the homepage.
header('Location: index.php');
exit;