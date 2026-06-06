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

    /**
     * Processa a criação via Fetch API (POST).
     */
    public function store(): void
    {
        AuthMiddleware::handle();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Método HTTP não permitido.']);
            exit;
        }

        $payload = json_decode(file_get_contents('php://input'), true);
        
        // Sanitização estrita contra injeção de scripts (XSS) e SQLi
        $nomeContato = filter_var($payload['nome_contato'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        $nomeAgencia = filter_var($payload['nome_agencia'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        $telefone = filter_var($payload['telefone'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        $valorProposta = filter_var($payload['valor_proposta'] ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $origem = filter_var($payload['origem'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);

        if (empty($nomeContato) || empty($nomeAgencia)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Nome do contato e agência são obrigatórios.']);
            exit;
        }

        try {
            Lead::criarLead([
                'nome_contato' => $nomeContato,
                'nome_agencia' => $nomeAgencia,
                'telefone' => $telefone,
                'valor_proposta' => (float) $valorProposta,
                'origem' => $origem
            ], $_SESSION['usuario_id']); // Injeção garantida pela sessão

            http_response_code(201);
            echo json_encode(['status' => 'success', 'message' => 'Lead registrado com sucesso.']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Falha de gravação no banco de dados.']);
        }
        exit;
    }

    public function update(): void
    {
        AuthMiddleware::handle();
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Método HTTP não permitido.']);
            exit;
        }

        $payload = json_decode(file_get_contents('php://input'), true);
        $idLead = filter_var($payload['id'] ?? 0, FILTER_VALIDATE_INT);
        
        if (!$idLead) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID do Lead inválido.']);
            exit;
        }

        try {
            Lead::atualizarLead($idLead, [
                'nome_contato'   => filter_var($payload['nome_contato'], FILTER_SANITIZE_SPECIAL_CHARS),
                'nome_agencia'   => filter_var($payload['nome_agencia'], FILTER_SANITIZE_SPECIAL_CHARS),
                'telefone'       => filter_var($payload['telefone'], FILTER_SANITIZE_SPECIAL_CHARS),
                'valor_proposta' => (float) ($payload['valor_proposta'] ?? 0)
            ], $_SESSION['usuario_id']);

            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Lead atualizado com segurança.']);
        } catch (\Exception $e) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function delete(): void
    {
        AuthMiddleware::handle();
        header('Content-Type: application/json');

        $payload = json_decode(file_get_contents('php://input'), true);
        $idLead = filter_var($payload['id'] ?? 0, FILTER_VALIDATE_INT);

        if (!$idLead) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID do Lead não informado.']);
            exit;
        }

        try {
            Lead::deletarLead($idLead, $_SESSION['usuario_id']);
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Lead removido da base de dados.']);
        } catch (\Exception $e) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
}