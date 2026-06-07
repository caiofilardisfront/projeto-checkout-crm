<?php
// Arquivo: /public/index.php

// 1. BLINDAGEM DE PRODUÇÃO E PROTEÇÃO DE PAYLOAD (JSON)
ini_set('display_errors', 0); // Bloqueia Warnings do PHP que quebram a Fetch API
error_reporting(E_ALL);

// 2. MICRO-PARSER NATIVO DO .ENV (ESSENCIAL PARA O BANCO DE DADOS)
$envPath = realpath(__DIR__ . '/../.env');
if ($envPath && file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// 3. Interceptador Estrito para o Servidor Embutido do PHP
if (php_sapi_name() === 'cli-server') {
    $path = realpath(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if ($path && is_file($path)) {
        return false;
    }
}

// 4. Autoload manual com Normalização Case-Sensitive (CRÍTICO PARA A HOSTINGER)
spl_autoload_register(function ($class) {
    if ($class === 'Config\Database') {
        $dbPath = __DIR__ . '/../config/db.php';
        if (file_exists($dbPath)) require_once $dbPath;
        return;
    }

    $classPath = str_replace('\\', '/', $class);

    // Força o prefixo "Src/" a se tornar "src/" minúsculo para o Linux achar a pasta
    if (strpos($classPath, 'Src/') === 0) {
        $classPath = 'src/' . substr($classPath, 4);
    }

    $path = __DIR__ . '/../' . $classPath . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
});

use Src\Controllers\AuthController;
use Src\Controllers\LeadController;
use Src\Middleware\AuthMiddleware;
use Src\Controllers\ContratoController;
use Src\Controllers\PaymentController;

// Substitua o bloco 5 do index.php por este:
// 5. Captura Universal de URL adaptada para XAMPP
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptName = dirname($_SERVER['SCRIPT_NAME']); // Detecta a subpasta local

// Remove o caminho base do projeto da URI para isolar a rota
$url = str_replace($scriptName, '', $requestUri);
$url = ltrim($url, '/');
$url = rtrim($url, '/');

// Se a URL estiver vazia, redireciona para login
if (empty($url)) {
    $url = 'login';
}

// 6. Roteamento Estrito
switch ($url) {
    // ==========================================
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
        AuthMiddleware::handle();
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

    case 'api/leads/atualizar':
        (new LeadController())->update();
        break;

    case 'api/leads/deletar':
        (new LeadController())->delete();
        break;

    case 'api/leads/servicos':
        (new LeadController())->listarServicos();
        break;

    case 'api/agenda/agendar':
        (new \Src\Controllers\AgendaController())->store();
        break;

    case 'api/contratos/upload':
        (new ContratoController())->upload();
        break;

    case 'api/dashboard/metricas':
        (new LeadController())->metricas();
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
