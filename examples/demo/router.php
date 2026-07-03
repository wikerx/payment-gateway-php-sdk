<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/DemoApiCatalog.php';
require_once __DIR__ . '/DemoApiService.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (strpos($path, '/assets/') === 0) {
    $file = __DIR__ . $path;
    if (is_file($file)) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        header('Content-Type: ' . ($ext === 'css' ? 'text/css; charset=UTF-8' : 'application/javascript; charset=UTF-8'));
        readfile($file);
        return;
    }
    http_response_code(404);
    echo 'Not found';
    return;
}

$service = new DemoApiService();
$config = $service->config();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($path === '/' || $path === '/demo') {
    header('Location: /demo/apis');
    return;
}

if ($path === '/demo/apis') {
    $groups = DemoApiCatalog::groups();
    include __DIR__ . '/views/index.php';
    return;
}

if (preg_match('#^/demo/apis/([A-Za-z0-9_-]+)$#', $path, $matches) === 1) {
    try {
        $definition = DemoApiCatalog::get($matches[1]);
    } catch (InvalidArgumentException $exception) {
        http_response_code(404);
        echo h($exception->getMessage());
        return;
    }

    $params = $method === 'POST'
        ? array_map(static fn($value): string => is_string($value) ? $value : '', $_POST['params'] ?? [])
        : DemoApiCatalog::defaults($definition, $config->getMerchantNo());
    $invocation = $method === 'POST' ? $service->invoke($definition, $params) : null;
    include __DIR__ . '/views/api.php';
    return;
}

http_response_code(404);
echo 'Not found';

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

