<?php
// Arquivo: /public/index.php

// 1. Interceptador Estrito para o Servidor Embutido do PHP (Ignorado no Apache)
if (php_sapi_name() === 'cli-server') {
    $path = realpath(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    // Permite que o servidor entregue arquivos estáticos (CSS, JS) diretamente
    if ($path && is_file($path)) {
        return false;
    }
}

// 2. Autoload manual com Caminhos Absolutos ancorados em __DIR__
spl_autoload_register(function ($class) {
    if ($class === 'Config\Database') {
        $dbPath = __DIR__ . '/../config/db.php';
        if (file_exists($dbPath)) require_once $dbPath;
        return;
    }

    $path = __DIR__ . '/../' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
});

use Src\Controllers\AuthController;
use Src\Controllers\LeadController;
use Src\Middleware\AuthMiddleware;
use Src\Controllers\ContratoController;
use Src\Controllers\PaymentController;

// 3. Captura Universal de URL (Suporta .htaccess em Produção ou Built-in Server em Homologação)
if (isset($_GET['url'])) {
    $url = filter_input(INPUT_GET, 'url', FILTER_SANITIZE_URL) ?? '';
} else {
    $url = ltrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
}
$url = rtrim($url, '/');

// 4. Roteamento Estrito protegido
switch ($url) {
    /// ==========================================
    // ROTAS DE VIEW (HTML)
    // ==========================================
    case '':
    case 'login':
        require __DIR__ . '/../views/auth/login.php';
        break;

    case 'checkout':
        require __DIR__ . '/../views/checkout/form.php';
        break;

    case 'checkout/sucesso':
        require __DIR__ . '/../views/checkout/success.php';
        break;

    case 'checkout/erro':
        require __DIR__ . '/../views/checkout/erro.php';
        break;

    case 'dashboard':
        AuthMiddleware::handle(); // Bloqueia acesso sem sessão (RLS base)
        require __DIR__ . '/../views/dashboard/main.php';
        break;

    case 'config_pedido':
        AuthMiddleware::handle();
        require __DIR__ . '/../views/dashboard/config_pedido.php';
        break;

    // ==========================================
    // ROTAS DE API (JSON / FETCH)
    // ==========================================
    case 'api/login':
        (new AuthController())->login();
        break;

    case 'api/logout':
        (new AuthController())->logout();
        break;

    case 'api/leads':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new LeadController())->store();
        } else {
            (new LeadController())->index();
        }
        break;

    case 'api/leads/delegar':
        (new LeadController())->delegarLead();
        break;

    case 'api/contratos/upload':
        (new ContratoController())->upload();
        break;
    
    case 'api/checkout/processar':
        (new PaymentController())->processarCheckout();
        break;

    // ==========================================
    // FALLBACK (404)
    // ==========================================
    default:
        http_response_code(404);
        echo "404 - Rota não encontrada.";
        break;
}
