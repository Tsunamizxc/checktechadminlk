<?php
/**
 * Deploy webhook: POST with header X-Deploy-Secret
 * Optional query: ?secret=
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$secret = getenv('LKTECH_DEPLOY_SECRET') ?: 'lktech_deploy_7f3c9a2e1b';
$got = $_SERVER['HTTP_X_DEPLOY_SECRET'] ?? ($_GET['secret'] ?? '');

if (!is_string($got) || !hash_equals($secret, $got)) {
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
