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
        // Captura o token isolado no ambiente do servidor (.env ou config do SO)
        $this->accessToken = getenv('MP_ACCESS_TOKEN') ?: '';

        if (empty($this->accessToken)) {
            error_log("CRM-CHECKOUT GATEWAY FATAL ERROR: Access Token do Mercado Pago não configurado no servidor.");
            throw new Exception("Falha de configuração no Gateway de Pagamento.");
        }
    }

    /**
     * Comunica estritamente com o endpoint /checkout/preferences via cURL nativo.
     * Rejeita o uso de SDKs pesados em prol de performance e controle de rede.
     */
    public function criarPreferencia(array $payload): array
    {
        $endpoint = $this->baseUrl . '/checkout/preferences';

        $ch = curl_init($endpoint);

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->accessToken
        ];

        // Configuração de rede blindada
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_SSL_VERIFYPEER => true, // Prevenção contra ataques Man-in-the-Middle (MITM)
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT        => 15    // Quebra a requisição caso o gateway esteja offline, evitando travamento do CRM
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

        // O Mercado Pago responde 200 ou 201 para sucesso na criação da preferência [1]
        if ($httpCode !== 200 && $httpCode !== 201) {
            error_log("CRM-CHECKOUT MP API ERROR [$httpCode]: " . $response);
            throw new Exception("O Mercado Pago rejeitou a estrutura de cobrança. Valide os logs do servidor.");
        }

        return $decodedResponse;
    }
}