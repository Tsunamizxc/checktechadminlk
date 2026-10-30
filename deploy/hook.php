<?php
/**
 * Deploy webhook for GitHub push (HMAC) or manual X-Deploy-Secret / ?secret=
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$secret = getenv('LKTECH_DEPLOY_SECRET') ?: 'lktech_deploy_7f3c9a2e1b';
$raw = file_get_contents('php://input') ?: '';
$ok = false;

$manual = $_SERVER['HTTP_X_DEPLOY_SECRET'] ?? ($_GET['secret'] ?? '');
if (is_string($manual) && $manual !== '' && hash_equals($secret, $manual)) {
    $ok = true;
}

$sig = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
if (!$ok && is_string($sig) && str_starts_with($sig, 'sha256=')) {
    $calc = 'sha256=' . hash_hmac('sha256', $raw, $secret);
    if (hash_equals($calc, $sig)) {
        $ok = true;
    }
}

if (!$ok) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden'], JSON_UNESCAPED_UNICODE);
    exit;
}

$script = __DIR__ . '/sync.sh';
if (!is_file($script)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'sync missing'], JSON_UNESCAPED_UNICODE);
    exit;
}

$cmd = 'bash ' . escapeshellarg($script) . ' 2>&1';
exec($cmd, $out, $code);

echo json_encode([
    'ok'     => $code === 0,
    'code'   => $code,
    'output' => implode("\n", $out),
], JSON_UNESCAPED_UNICODE);
