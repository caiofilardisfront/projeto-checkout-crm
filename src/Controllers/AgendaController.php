<?php

// Arquivo: /src/Controllers/AgendaController.php

namespace Src\Controllers;

use Src\Models\Agenda;
use Src\Middleware\AuthMiddleware;
use Exception;

class AgendaController
{
    /**
     * Recebe os dados do Frontend, limpa as variáveis e manda salvar no banco.
     */
    public function store(): void
    {
        // 1. Parede de Segurança: Ninguém anônimo passa daqui
        AuthMiddleware::handle();
        header('Content-Type: application/json');

        // Aceita apenas envio de dados (POST)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Método HTTP não permitido.']);
            exit;
        }

        // 2. Captura do pacote de dados (JSON) enviado pelo navegador
        $payload = json_decode(file_get_contents('php://input'), true);

        // 3. Limpeza contra ataques (XSS e SQL Injection)
        $idLead = filter_var($payload['id_lead'] ?? 0, FILTER_VALIDATE_INT);
        $titulo = filter_var($payload['titulo'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        $dataHora = filter_var($payload['data_hora'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);

        // 4. Verificação se o usuário preencheu tudo
        if (!$idLead || empty($titulo) || empty($dataHora)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Data, hora ou ID do Lead ausentes.']);
            exit;
        }

        try {
            // 5. Aciona o Model (que tem a trava RLS) e grava no MySQL local
            Agenda::agendarTreinamento($idLead, $titulo, $dataHora, $_SESSION['usuario_id']);

            // 6. Tenta engatilhar a Sincronização Externa (Google Calendar)
            try {
                $googleService = new \Src\Services\GoogleCalendarService();
                $googleEventId = $googleService->criarEvento($titulo, $dataHora);

                // 7. Se o Google gerar com sucesso, vinculamos o ID Externo ao MySQL local
                Agenda::setGoogleEventId($idLead, $googleEventId, $_SESSION['usuario_id']);
            } catch (Exception $googleEx) {
                // Se a API externa cair ou as credenciais forem inválidas, gravamos o erro no Log do servidor,
                // mas NÃO impedimos a criação do registro local para não afetar o usuário final.
                error_log("Aviso de Sincronização Google: " . $googleEx->getMessage());
            }

            // 201 significa "Criado com sucesso" na internet
            http_response_code(201);
            echo json_encode(['status' => 'success', 'message' => 'Treinamento agendado com sucesso.']);
        } catch (Exception $e) {
            // Se cair aqui, o erro foi físico no banco de dados local ou violação de RLS
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
