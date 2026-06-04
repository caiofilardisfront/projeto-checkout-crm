<?php
// Arquivo: /src/Controllers/LeadController.php

namespace Src\Controllers;

use Src\Models\Lead;
use Src\Middleware\AuthMiddleware;


class LeadController
{
    /**
     * Endpoint Genérico (Diretor e Equipe). Retorna apenas os dados isolados do usuário.
     */
    public function index(): void
    {
        // Validação estrita da sessão
        AuthMiddleware::handle();

        header('Content-Type: application/json');

        // Captura e sanitização das variáveis de filtro enviadas pela query string
        $status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        $busca = filter_input(INPUT_GET, 'busca', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;

        try {
            // Injeção intransponível da sessão do usuário atual para acionar o RLS nativo
            $leads = Lead::getLeadsFiltrados($_SESSION['usuario_id'], $status, $busca);
            
            http_response_code(200);
            echo json_encode(['status' => 'success', 'data' => $leads]);
        } catch (\Exception $e) {
            error_log("CRM-CHECKOUT RLS DB ERROR: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Falha arquitetural ao extrair dados RLS.']);
        }
        exit;
    }

    /**
     * Endpoint Restrito (Apenas Diretor). RBAC aplicado via Middleware.
     */
    public function delegarLead(): void
    {
        // Bloqueia a execução se nivel_acesso !== 'diretor'
        AuthMiddleware::requireDiretor();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Método HTTP não permitido.']);
            exit;
        }

        $payload = json_decode(file_get_contents('php://input'), true);
        $idLead = filter_var($payload['id_lead'] ?? null, FILTER_VALIDATE_INT);
        $idUsuarioDestino = filter_var($payload['id_usuario_destino'] ?? null, FILTER_VALIDATE_INT);
        $compartilhado = (bool) ($payload['compartilhado'] ?? false);

        if (!$idLead || !$idUsuarioDestino) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Parâmetros de delegação incompletos.']);
            exit;
        }

        // Validação RLS: O diretor só pode delegar um Lead que ele próprio tem permissão de visualizar
        if (!Lead::checkAcessoLead($idLead, $_SESSION['usuario_id'])) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Violação de segurança. Acesso ao lead negado.']);
            exit;
        }

        try {
            Lead::delegarAtribuicao($idLead, $idUsuarioDestino, $compartilhado);
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Lead delegado com sucesso.']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Falha na persistência da atribuição.']);
        }
        exit;
    }
}