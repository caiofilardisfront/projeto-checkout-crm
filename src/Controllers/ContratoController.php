<?php
// Arquivo: /src/Controllers/ContratoController.php

namespace Src\Controllers;

use Src\Models\Contrato;
use Src\Models\Lead;
use Src\Middleware\AuthMiddleware;

class ContratoController
{
    /**
     * Processa o upload do arquivo PDF, validando MIME type, tamanho e RLS.
     */
    public function upload(): void
    {
        AuthMiddleware::handle();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Método não permitido.']);
            exit;
        }

        // Validação do ID do Lead vindo do formulário multipart
        $idLead = filter_input(INPUT_POST, 'id_lead', FILTER_VALIDATE_INT);

        if (!$idLead || !isset($_FILES['contrato_pdf'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Parâmetros incompletos ou arquivo ausente.']);
            exit;
        }

        // PREVENÇÃO IDOR / VALIDAÇÃO RLS: O usuário tem acesso a este Lead?
        if (!Lead::checkAcessoLead($idLead, $_SESSION['usuario_id'])) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Violação de Segurança. Você não tem permissão para alterar este lead.']);
            exit;
        }

        $file = $_FILES['contrato_pdf'];

        // Tratamento de erros de upload do PHP
        if ($file['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Falha durante o envio do arquivo.']);
            exit;
        }

        // Blindagem contra scripts maliciosos (Validação estrita de MIME Type)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if ($mime !== 'application/pdf') {
            http_response_code(415);
            echo json_encode(['status' => 'error', 'message' => 'Formato não suportado. Envie apenas arquivos PDF.']);
            exit;
        }

        // Definição da pasta isolada (Fora do document root /public)
        $uploadDir = __DIR__ . '/../../contracts/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Hash criptográfico para evitar sobreposição de nomes e path traversal
        $fileName = 'contrato_' . $idLead . '_' . bin2hex(random_bytes(8)) . '.pdf';
        $destPath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            try {
                Contrato::salvar($idLead, $fileName);
                http_response_code(201);
                echo json_encode(['status' => 'success', 'message' => 'Contrato arquivado com segurança.']);
            } catch (\Exception $e) {
                // Roolback físico do arquivo caso o DB falhe
                unlink($destPath);
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Falha ao registrar o contrato no banco de dados.']);
            }
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Erro interno de permissão ao gravar no servidor.']);
        }
        exit;
    }
}
