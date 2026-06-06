<?php
// Arquivo: /src/Controllers/PaymentController.php

namespace Src\Controllers;

use Src\Services\MercadoPagoService;

class PaymentController
{
    /**
     * Processa o formulário de faturamento nativo e gera o link do Checkout Pro
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

        // Captura e sanitiza os dados vindos do seu formulário intermediário
        $nome = filter_var($payload['nome'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        $email = filter_var($payload['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $documento = preg_replace('/\D/', '', $payload['documento'] ?? ''); // Remove pontos e traços
        $leadId = filter_var($payload['lead_id'] ?? 0, FILTER_VALIDATE_INT);

        if (!$nome || !$email || !$leadId) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Dados de faturamento e/ou identificação do Lead incompletos.']);
            exit;
        }

        try {
            $mpService = new MercadoPagoService();
            
            // Definição dinâmica do Host para URLs de retorno na Hostinger
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
            $baseUrl = $protocol . ($_SERVER['HTTP_HOST'] ?? 'localhost:8000');

            // Montagem Estrutural da Preferência do Checkout Pro
            $preferencia = [
                'items' => [
                    [
                        'id' => (string) $leadId,
                        'title' => 'CRM Comercial Completo + Treinamento SDR + Manutenção',
                        'quantity' => 1,
                        'currency_id' => 'BRL',
                        'unit_price' => 2100.00 // Preço travado no backend contra fraudes de console
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
                'external_reference' => (string) $leadId, // Vincula a transação ao ID do banco para o Webhook
                'notification_url' => $baseUrl . '/webhook.php' // Avisa o Mercado Pago onde entregar a confirmação
            ];

            // Aciona o método de criação de preferência do Service
            $response = $mpService->criarPreferencia($preferencia);

            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'init_point' => $response['init_point'] // Entrega a URL oficial do MP para o javascript redirecionar
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
}