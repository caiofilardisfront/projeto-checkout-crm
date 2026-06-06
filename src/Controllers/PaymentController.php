<?php
// Arquivo: /src/Controllers/PaymentController.php

namespace Src\Controllers;

use Src\Services\MercadoPagoService;
use Config\Database; // Importação necessária para consultar o banco

class PaymentController
{
    public function processarCheckout(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Método não permitido.']);
            exit;
        }

        $payload = json_decode(file_get_contents('php://input'), true);
        $leadId = filter_var($payload['lead_id'] ?? 0, FILTER_VALIDATE_INT);

        if (!$leadId) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Sessão inválida.']);
            exit;
        }

        try {
            // BUSCA DINÂMICA: Lê o valor exato da proposta no Banco de Dados
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT valor_proposta FROM leads WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $leadId]);
            $valorDinamico = (float) $stmt->fetchColumn();

            if ($valorDinamico <= 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'O valor da proposta não é válido para cobrança.']);
                exit;
            }

            $mpService = new MercadoPagoService();
            unset($payload['lead_id']);

            $payload['external_reference'] = (string) $leadId;
            $payload['description'] = 'Pagamento de Proposta Comercial - JONES GROUP';
            $payload['notification_url'] = "https://" . $_SERVER['HTTP_HOST'] . "/webhook.php";
            
            // INJEÇÃO DO VALOR REAL NO MERCADO PAGO
            $payload['transaction_amount'] = $valorDinamico;

            $response = $mpService->processarPagamento($payload);
            $statusPagamento = $response['status'] ?? 'rejected';

            if (in_array($statusPagamento, ['approved', 'in_process', 'pending'])) {
                http_response_code(200);
                echo json_encode(['status' => 'success']);
            } else {
                http_response_code(400);
                $detalhe = $response['status_detail'] ?? 'recusado pela operadora';
                echo json_encode(['status' => 'error', 'message' => "Transação não aprovada ($detalhe)."]);
            }

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
}