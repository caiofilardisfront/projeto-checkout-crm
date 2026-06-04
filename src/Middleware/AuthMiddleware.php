<?php
// Arquivo: /src/Middleware/AuthMiddleware.php

namespace Src\Middleware;

class AuthMiddleware
{
    /**
     * Valida a sessão do usuário.
     * Injetado nas rotas do index.php antes de acionar os Controllers ou Views protegidas.
     */
    public static function handle(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Bloqueio estrito se o ID do usuário não existir na sessão
        if (!isset($_SESSION['usuario_id']) || !is_numeric($_SESSION['usuario_id'])) {
            self::denyAccess();
        }
    }

    /**
     * Validação de RBAC (Role-Based Access Control) exigida pelo PRD.
     * Aplica-se a rotas ou endpoints exclusivos para diretores (ex: métricas macro).
     */
    public static function requireDiretor(): void
    {
        self::handle(); // Garante a autenticação primária

        if ($_SESSION['nivel_acesso'] !== 'diretor') {
            self::denyAccess(403, 'Acesso negado. Privilégio de Diretor exigido.');
        }
    }

    /**
     * Identifica o tipo de requisição (Web ou Fetch API) e aplica o bloqueio correto.
     */
    private static function denyAccess(int $httpCode = 401, string $message = 'Sessão expirada ou não autorizada.'): void
    {
        // Limpa a sessão por segurança em caso de anomalia
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        // Verifica se é uma requisição assíncrona (Fetch API) aguardando JSON
        $isJsonRequest = (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) 
                      || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
                      || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        if ($isJsonRequest) {
            http_response_code($httpCode);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => $message,
                'redirect' => '/login'
            ]);
            exit;
        }

        // Roteamento padrão de Views (HTML)
        http_response_code($httpCode === 401 ? 302 : $httpCode);
        header("Location: /login");
        exit;
    }
}