<?php
// Arquivo: /src/Controllers/AuthController.php

namespace Src\Controllers;

use Config\Database;
use PDO;
use PDOException;

class AuthController
{
    /**
     * Processa a autenticação via Fetch API (JSON) e estabelece a sessão segura.
     */
    public function login(): void
    {
        // Retorno estrito em JSON conforme regra arquitetural [1]
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Método HTTP não permitido.']);
            exit;
        }

        // Captura e sanitização do payload JSON vindo do frontend
        $payload = json_decode(file_get_contents('php://input'), true);
        $email = filter_var($payload['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $senha = $payload['senha'] ?? '';

        if (empty($email) || empty($senha)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'E-mail e senha são obrigatórios.']);
            exit;
        }

        try {
            $pdo = Database::getConnection();
            
            // Busca indexada otimizada; blindagem contra SQL Injection nativa do PDO [2]
            $stmt = $pdo->prepare("SELECT id, nome, senha, nivel_acesso FROM usuarios WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $usuario = $stmt->fetch();

            // Validação de hash criptográfico bcrypt/argon2
            if ($usuario && password_verify($senha, $usuario['senha'])) {
                
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                
                // CRÍTICO: Prevenção contra ataque de Session Fixation
                session_regenerate_id(true);

                // Armazenamento estrito para injeção da cláusula de Row Level Security (RLS) nas queries subsequentes [3]
                $_SESSION['usuario_id'] = (int) $usuario['id'];
                $_SESSION['nivel_acesso'] = $usuario['nivel_acesso'];
                $_SESSION['nome'] = $usuario['nome'];

                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Autenticação bem-sucedida.',
                    'redirect' => '/dashboard' // Roteamento frontend
                ]);
            } else {
                // Proteção contra brute-force e enumeração de usuários (mesma mensagem genérica)
                http_response_code(401);
                echo json_encode(['status' => 'error', 'message' => 'Credenciais de acesso inválidas.']);
            }
        } catch (PDOException $e) {
            error_log("CRM-CHECKOUT AUTH ERROR: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Falha de comunicação com o servidor.']);
        }
        
        exit;
    }

    /**
     * Destrói a sessão e bloqueia o acesso imediatamente.
     */
    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Esvazia e destrói os dados da sessão no servidor
        $_SESSION = [];
        session_destroy();
        
        // Remove o cookie de sessão do navegador do cliente
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        header('Content-Type: application/json');
        http_response_code(200);
        echo json_encode(['status' => 'success', 'redirect' => '/login']);
        exit;
    }
}