<?php
// Arquivo: /src/Controllers/PaymentController.php

namespace Src\Controllers;

use Src\Services\MercadoPagoService;

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

        // Lê o payload enviado pelo Payment Brick no JavaScript
        $payload = json_decode(file_get_contents('php://input'), true);

        $leadId = filter_var($payload['lead_id'] ?? 0, FILTER_VALIDATE_INT);

        if (!$leadId) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Sessão inválida.']);
            exit;
        }

        try {
            $mpService = new MercadoPagoService();
            
            unset($payload['lead_id']);

            // Parâmetros obrigatórios para a API v1/payments
            $payload['external_reference'] = (string) $leadId;
            $payload['description'] = 'Sistema CRM Comercial Completo + Treinamento SDR';
            $payload['notification_url'] = "https://" . $_SERVER['HTTP_HOST'] . "/webhook.php";
            $payload['transaction_amount'] = 2100.00;

            // Envia a cobrança
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