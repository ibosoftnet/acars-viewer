<?php
// ============================================================
// TEMPORARY DEBUG — remove after diagnosing the 401 issue.
// Logs the state of $sso, headers, env and setcookie() result
// to the PHP error log on each request to /data-link.
// ============================================================
error_log('JWT-DEBUG reached jwt-issuer.php');
$_jwt_dbg_f = ''; $_jwt_dbg_l = 0;
error_log('JWT-DEBUG headers_sent: ' . (headers_sent($_jwt_dbg_f, $_jwt_dbg_l) ? "YES at {$_jwt_dbg_f}:{$_jwt_dbg_l}" : 'no'));
error_log('JWT-DEBUG sso_isset: ' . (isset($sso) ? 'yes' : 'NO'));
error_log('JWT-DEBUG sso_loggedin: ' . ((isset($sso) && $sso->isLoggedIn()) ? 'yes' : 'no'));
error_log('JWT-DEBUG secret_env: ' . (getenv('DATALINK_JWT_SECRET') ? 'SET' : 'EMPTY'));
error_log('JWT-DEBUG php_version: ' . PHP_VERSION);
$_jwt_dbg_ret = setcookie('jwt_debug_canary', 'test', ['path' => '/']);
error_log('JWT-DEBUG setcookie_returned: ' . ($_jwt_dbg_ret ? 'true' : 'FALSE'));
// ============================================================
// END TEMPORARY DEBUG
// ============================================================

/**
 * Data Link Backend — Session JWT Issuer
 *
 * Mints a short-lived HS256 JWT in an HttpOnly cookie that the data link
 * backend (atc-data-link-backend) validates at connection establishment.
 *
 * The static shared secret (DATALINK_JWT_SECRET) never leaves the server:
 * - atcweb signs JWTs with it here
 * - the data link backend verifies signatures with the same value
 * The browser only carries the JWT (per-session, short-lived).
 *
 * Requires the current request to be authenticated (\Ibosoft\SSO::isLoggedIn).
 * Must be included BEFORE any output (cookie is set via setcookie()).
 *
 * Designed to be included by atcweb's route-mappings.php on the 'data-link'
 * route. Deployed to atcweb/data-link-files/jwt-issuer.php and uses the $sso
 * instance that atcweb/config.php already initialises.
 */

if (!isset($sso) || !($sso instanceof \Ibosoft\SSO)) {
    // SSO not initialised — nothing to do.
    return;
}

if (!$sso->isLoggedIn()) {
    // Will be redirected by the regular auth pipeline; no JWT to issue.
    return;
}

$secret = getenv('DATALINK_JWT_SECRET');
if (!$secret) {
    error_log('jwt-issuer: DATALINK_JWT_SECRET is not set in atcweb .htaccess');
    return;
}

$now = time();
$exp = $now + (8 * 3600); // 8 hours

$header  = ['typ' => 'JWT', 'alg' => 'HS256'];
$payload = [
    'user_id' => $sso->getUserId(),
    'iat'     => $now,
    'exp'     => $exp,
];

$b64url = static function (string $bytes): string {
    return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
};

$encodedHeader  = $b64url(json_encode($header,  JSON_UNESCAPED_SLASHES));
$encodedPayload = $b64url(json_encode($payload, JSON_UNESCAPED_SLASHES));
$signingInput   = $encodedHeader . '.' . $encodedPayload;
$signature      = $b64url(hash_hmac('sha256', $signingInput, $secret, true));

$jwt = $signingInput . '.' . $signature;

// Domain '.ibosoft.net.tr' lets the browser auto-send the cookie to
// dlink-api.ibosoft.net.tr as well as atc.ibosoft.net.tr.
// SameSite=None + Secure is required because the data link backend lives on
// a different subdomain (cross-site fetch/EventSource carries the cookie only
// when both flags are present).
setcookie('datalink_session', $jwt, [
    'expires'  => $exp,
    'path'     => '/',
    'domain'   => '.ibosoft.net.tr',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'None',
]);
