<?php
// Arquivo: /src/Controllers/PaymentController.php

namespace Src\Controllers;

use Src\Services\MercadoPagoService;

class PaymentController
{
    /**
     * Processa o formulário de checkout público e gera a URL (init_point) no Mercado Pago.
     */
    public function processarCheckout(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Método não permitido.']);
            exit;
        }

        $payload = json_decode(file_get_contents('php://input'), true);

        // Sanitização de entradas do cliente final
        $nome = filter_var($payload['nome'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        $email = filter_var($payload['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $documento = preg_replace('/\D/', '', $payload['documento'] ?? ''); // Apenas números
        $leadId = filter_var($payload['lead_id'] ?? 0, FILTER_VALIDATE_INT);

        if (!$nome || !$email || !$leadId) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Dados de faturamento e/ou identificação do Lead incompletos.']);
            exit;
        }

        try {
            $mpService = new MercadoPagoService();
            
            // Definição dinâmica do Host para URLs de retorno
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
            $baseUrl = $protocol . ($_SERVER['HTTP_HOST'] ?? 'localhost:8000');

            // Montagem Estrita do Payload de Preferência
            $preferencia = [
                'items' => [
                    [
                        'id' => (string) $leadId,
                        'title' => 'CRM Comercial Completo + Treinamento SDR + Manutenção',
                        'quantity' => 1,
                        'currency_id' => 'BRL',
                        'unit_price' => 2100.00 // CRÍTICO: Preço definido obrigatoriamente no servidor
                    ]
                ],
                'payer' => [
                    'name' => $nome,
                    'email' => $email,
                    'identification' => [
                        'type' => strlen($documento) > 11 ? 'CNPJ' : 'CPF',
                        'number' => $documento
                    ]
                ],
                'back_urls' => [
                    'success' => $baseUrl . '/checkout/sucesso',
                    'failure' => $baseUrl . '/checkout/erro',
                    'pending' => $baseUrl . '/checkout/erro'
                ],
                'auto_return' => 'approved',
                'external_reference' => (string) $leadId // Vincula a transação MP ao ID do banco para o Webhook
            ];

            $response = $mpService->criarPreferencia($preferencia);

            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'init_point' => $response['init_point'] // Retorna a URL segura gerada pelo gateway
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
}