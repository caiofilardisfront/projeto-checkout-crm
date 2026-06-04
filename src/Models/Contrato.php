<?php
// Arquivo: /src/Models/Contrato.php

namespace Src\Models;

use Config\Database;

class Contrato
{
    /**
     * Registra o caminho do arquivo no banco de dados vinculado ao Lead.
     */
    public static function salvar(int $idLead, string $caminhoArquivo): bool
    {
        $pdo = Database::getConnection();
        $sql = "INSERT INTO contratos (id_lead, arquivo_caminho) VALUES (:id_lead, :caminho_arquivo)";
        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            'id_lead' => $idLead,
            'caminho_arquivo' => $caminhoArquivo
        ]);
    }
}
