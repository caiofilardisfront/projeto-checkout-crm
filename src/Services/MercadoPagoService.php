<?php
// Arquivo: /src/Services/MercadoPagoService.php

namespace Src\Services;

use Exception;

class MercadoPagoService
{
    private string $accessToken;
    private string $baseUrl = 'https://api.mercadopago.com';

    public function __construct()
    {
        $this->accessToken = getenv('MP_ACCESS_TOKEN') ?: '';

        if (empty($this->accessToken)) {
            error_log("CRM-CHECKOUT GATEWAY FATAL ERROR: Access Token do Mercado Pago não configurado no servidor.");
            throw new Exception("Falha de configuração no Gateway de Pagamento.");
        }
    }

    /**
     * Cria a preferência do Checkout Pro e gera o link init_point
     */
    public function criarPreferencia(array $payload): array
    {
        $endpoint = $this->baseUrl . '/checkout/preferences';

        $ch = curl_init($endpoint);

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->accessToken
        ];

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_SSL_VERIFYPEER => true, 
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT        => 15    
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        if ($curlError) {
            error_log("CRM-CHECKOUT CURL ERROR: " . $curlError);
            throw new Exception("Falha de rede na comunicação com o gateway financeiro.");
        }

        $decodedResponse = json_decode($response, true);

        if ($httpCode !== 200 && $httpCode !== 201) {
            error_log("CRM-CHECKOUT MP API ERROR [$httpCode]: " . $response);
            throw new Exception("O Mercado Pago rejeitou a estrutura de cobrança. Valide os logs.");
        }

        return $decodedResponse;
    }

    /**
     * Consulta as informações do pagamento recebido no Webhook
     */
    public function consultarPagamento(string $idPagamento): array
    {
        $endpoint = $this->baseUrl . "/v1/payments/" . $idPagamento;

        $ch = curl_init($endpoint);
        $headers = ['Authorization: Bearer ' . $this->accessToken];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT        => 10
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("CRM-CHECKOUT MP PAYMENTS CONSULTA ERROR [$httpCode]: " . $response);
            throw new Exception("Erro ao consultar metadados do recurso de pagamento.");
        }

        return json_decode($response, true);
    }
}