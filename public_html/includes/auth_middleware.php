<?php
require_once __DIR__ . '/db.php';

const AUTH_COOKIE_NAME = 'nts_auth_token';
const TOKEN_LIFETIME_DAYS = 7;

/**
 * Create a new auth token for a user, store it, and set the cookie.
 */
function create_auth_token(int $userId): string
{
    $pdo   = get_db();
    $token = bin2hex(random_bytes(32));
    $expiry = (new DateTime())->modify('+' . TOKEN_LIFETIME_DAYS . ' days')->format('Y-m-d H:i:s');

    $stmt = $pdo->prepare('INSERT INTO auth_tokens (token, user_id, expiry) VALUES (:token, :user_id, :expiry)');
    $stmt->execute(['token' => $token, 'user_id' => $userId, 'expiry' => $expiry]);

    setcookie(AUTH_COOKIE_NAME, $token, [
        'expires'  => time() + TOKEN_LIFETIME_DAYS * 86400,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    return $token;
}

/**
 * Look up the currently logged-in user from the auth cookie, or null.
 */
function current_user(): ?array
{
    static $cached = false;
    if ($cached !== false) {
        return $cached ?: null;
    }

    if (empty($_COOKIE[AUTH_COOKIE_NAME])) {
        $cached = null;
        return null;
    }

    $pdo = get_db();
    $stmt = $pdo->prepare(
        'SELECT u.* FROM auth_tokens t
         JOIN users u ON u.id = t.user_id
         WHERE t.token = :token AND t.expiry > NOW()'
    );
    $stmt->execute(['token' => $_COOKIE[AUTH_COOKIE_NAME]]);
    $user = $stmt->fetch();

    $cached = $user ?: null;
    return $cached;
}

/**
 * Invalidate the current session token and clear the cookie.
 */
function destroy_auth_token(): void
{
    if (!empty($_COOKIE[AUTH_COOKIE_NAME])) {
        $pdo = get_db();
        $stmt = $pdo->prepare('DELETE FROM auth_tokens WHERE token = :token');
        $stmt->execute(['token' => $_COOKIE[AUTH_COOKIE_NAME]]);
    }
    setcookie(AUTH_COOKIE_NAME, '', ['expires' => time() - 3600, 'path' => '/']);
    unset($_COOKIE[AUTH_COOKIE_NAME]);
}

/**
 * Redirect to login if nobody is signed in. Returns the user array.
 */
function require_login(): array
{
    $user = current_user();
    if (!$user) {
        header('Location: /login.php');
        exit;
    }
    return $user;
}

/**
 * Redirect away if the current user doesn't have one of the given roles.
 */
function require_role(array $roles): array
{
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        die('You do not have access to this page.');
    }
    return $user;
}

/** Same as require_login/require_role but for JSON API endpoints. */
function api_require_login(): array
{
    $user = current_user();
    if (!$user) {
        json_response(['error' => 'Authentication required.'], 401);
    }
    return $user;
}

function api_require_role(array $roles): array
{
    $user = api_require_login();
    if (!in_array($user['role'], $roles, true)) {
        json_response(['error' => 'You do not have access to this action.'], 403);
    }
    return $user;
}

function json_response($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
