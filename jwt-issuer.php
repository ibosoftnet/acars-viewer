<?php
/**
 * Data Link Backend — Session JWT Issuer
 *
 * Mints a short-lived HS256 JWT that the data link backend
 * (atc-data-link-backend) validates at connection establishment. The token is
 * exposed to the page as $DATALINK_JWT; the browser sends it as a `?token=`
 * query parameter on the SSE stream and as an `Authorization: Bearer` header
 * on /decode. Unlike a cookie, this works whatever domains the frontend and
 * backend are served from.
 *
 * The static shared secret (DATALINK_JWT_SECRET) never leaves the server:
 * - atcweb signs JWTs with it here
 * - the data link backend verifies signatures with the same value
 * The browser only carries the JWT (per-session, short-lived).
 *
 * Requires the current request to be authenticated (\Ibosoft\SSO::isLoggedIn).
 * Must be included before the page script that reads $DATALINK_JWT.
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

// Handed to the page script (see DATALINK_TOKEN in page-data-link.php).
$DATALINK_JWT = $jwt;
