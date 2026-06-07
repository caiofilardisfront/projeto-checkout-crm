<?php

// Arquivo: /src/Services/GoogleCalendarService.php

namespace Src\Services;

use Exception;
use DateTime;
use DateTimeZone;

class GoogleCalendarService
{
    private string $clientId;
    private string $clientSecret;
    private string $refreshToken;
    private string $calendarId;

    public function __construct()
    {
        $this->clientId = getenv('GOOGLE_CLIENT_ID') ?: '';
        $this->clientSecret = getenv('GOOGLE_CLIENT_SECRET') ?: '';
        $this->refreshToken = getenv('GOOGLE_REFRESH_TOKEN') ?: '';
        $this->calendarId = getenv('GOOGLE_CALENDAR_ID') ?: 'primary';
    }

    /**
     * Autenticação OAuth2: Troca o Refresh Token por um Access Token válido.
     */
    private function getAccessToken(): string
    {
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $this->refreshToken,
                'grant_type' => 'refresh_token'
            ])
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        if (empty($data['access_token'])) {
            throw new Exception('Falha de autenticação (OAuth2) com a Google API.');
        }
        
        return $data['access_token'];
    }

    /**
     * Injeta o Evento na Google Agenda via REST API.
     */
    public function criarEvento(string $titulo, string $dataHora): string
    {
        $token = $this->getAccessToken();
        
        // Converte a data do HTML para o formato RFC3339 exigido pelo Google
        $start = new DateTime($dataHora, new DateTimeZone('America/Sao_Paulo'));
        
        // Define o fim da reunião para 1 hora após o início
        $end = clone $start;
        $end->modify('+1 hour');

        $payload = [
            'summary' => $titulo,
            'start' => ['dateTime' => $start->format(DateTime::RFC3339), 'timeZone' => 'America/Sao_Paulo'],
            'end' => ['dateTime' => $end->format(DateTime::RFC3339), 'timeZone' => 'America/Sao_Paulo'],
        ];

        $ch = curl_init("https://www.googleapis.com/calendar/v3/calendars/" . urlencode($this->calendarId) . "/events");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($payload)
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);

        // Se o Google não retornar Status 200, aborta o script de sincronização
        if ($httpCode !== 200 || empty($data['id'])) {
            error_log("CRM-CHECKOUT GOOGLE API ERROR: " . $response);
            throw new Exception('Falha ao injetar evento no Google Agenda.');
        }

        // Retorna o ID Oficial gerado pelo Google para salvarmos no nosso banco
        return $data['id'];
    }
}