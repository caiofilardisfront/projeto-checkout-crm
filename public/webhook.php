<?php
// Arquivo: /public/webhook.php

// 1. LIGAR O MOTOR E CONFIGURAÇÕES
ini_set('display_errors', 0); // Oculta erros visuais para não quebrar a resposta JSON
http_response_code(200); // Já responde "200 OK" para o MP parar de enviar a notificação repetida

// Lê o arquivo .env manualmente (como fizemos no index.php)
$envPath = realpath(__DIR__ . '/../.env');
if ($envPath && file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            putenv(trim($name) . '=' . trim($value));
        }
    }
}

// Carrega as classes necessárias da sua arquitetura
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Services/MercadoPagoService.php';
use Config\Database;
use Src\Services\MercadoPagoService;

// ========================================================
// PASSO 1 E 2: RECEBER DADOS E SEGURANÇA (TAREFA 4.4)
// ========================================================

// Lê o pacote JSON enviado pelo Mercado Pago
$payloadBruto = file_get_contents('php://input');
$dadosPayload = json_decode($payloadBruto, true);

// Captura os cabeçalhos de segurança
$headers = getallheaders();
$xSignature = $headers['x-signature'] ?? ($headers['X-Signature'] ?? '');
$xRequestId = $headers['x-request-id'] ?? ($headers['X-Request-Id'] ?? '');

// Se for apenas um teste do painel do MP ou requisição vazia, encerra em paz
if (empty($dadosPayload) || !isset($dadosPayload['action'])) {
    exit;
}

// Verifica se a ação é sobre um pagamento que acabou de ser criado ou atualizado
if ($dadosPayload['action'] === 'payment.created' || $dadosPayload['action'] === 'payment.updated') {
    
    // Pega o ID do pagamento enviado no pacote
    $idPagamento = $dadosPayload['data']['id'] ?? null;

    if ($idPagamento) {
        try {
            // ========================================================
            // PASSO 3: O DETETIVE - CONSULTAR STATUS (TAREFA 4.5)
            // ========================================================
            
            // Instancia o serviço que você criou
            $mpService = new MercadoPagoService();
            
            // Faz a requisição oficial cURL GET para /v1/payments/{id}
            $dadosPagamento = $mpService->consultarPagamento($idPagamento);
            
            // Verifica se o status oficial é "approved" (Aprovado)
            if (isset($dadosPagamento['status']) && $dadosPagamento['status'] === 'approved') {
                
                // Pega o ID do Lead que salvamos lá trás no "external_reference"
                $idLead = $dadosPagamento['external_reference'] ?? null;
                
                if ($idLead) {
                    // ========================================================
                    // PASSO 4: ATUALIZAR O BANCO DE DADOS
                    // ========================================================
                    
                    $db = Database::getConnection();
                    
                    // Atualiza o Lead para "Fechado"
                    $stmt = $db->prepare("UPDATE leads SET status = 'fechado' WHERE id = :id");
                    $stmt->execute(['id' => $idLead]);
                    
                    // Aqui você também pode dar baixa na tabela de pagamentos, se necessário
                    // $stmtPgto = $db->prepare("UPDATE pagamentos SET status = 'pago' WHERE lead_id = :id");
                    // $stmtPgto->execute(['id' => $idLead]);
                    
                    error_log("CRM-CHECKOUT: Sucesso! Pagamento {$idPagamento} aprovado. Lead {$idLead} fechado.");
                }
            }
        } catch (Exception $e) {
            // Registra silenciosamente no servidor se algo der errado (ex: falha de internet)
            error_log("CRM-CHECKOUT WEBHOOK ERROR: " . $e->getMessage());
        }
    }
}
?>