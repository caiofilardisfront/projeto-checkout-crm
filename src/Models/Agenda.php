<?php

// Arquivo: /src/Models/Agenda.php

namespace Src\Models;

use Config\Database;
use Exception;

class Agenda
{
    /**
     * Registra um compromisso de Treinamento SDR garantindo Isolamento Estrito de Dados (RLS).
     */
    public static function agendarTreinamento(int $idLead, string $titulo, string $dataHora, int $idUsuario): bool
    {
        // 1. Blindagem RLS (IDOR Prevention): O diretor só agenda se o Lead pertencer a ele.
        if (!Lead::checkAcessoLead($idLead, $idUsuario)) {
            throw new Exception("Violação de Segurança (RLS): Acesso negado para agendar evento neste lead.");
        }

        // 2. Conexão PDO Limpa
        $pdo = Database::getConnection();

        // 3. Inserção na estrutura com espaço para a Fase 5 (API Google)
        $sql = "INSERT INTO agenda_compromissos (id_lead, titulo, data_hora, google_event_id) 
                VALUES (:id_lead, :titulo, :data_hora, NULL)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            'id_lead'   => $idLead,
            'titulo'    => $titulo,
            'data_hora' => $dataHora
        ]);
    }

     /**
     * Atualiza o ID do evento sincronizado via Google API garantindo RLS.
     */
    public static function setGoogleEventId(int $idLead, string $googleEventId, int $idUsuario): bool
    {
        // Trava RLS: Confirma que o evento pertence ao usuário antes de gravar o Token
        if (!Lead::checkAcessoLead($idLead, $idUsuario)) {
            throw new Exception("Violação de Segurança (RLS).");
        }

        $pdo = Database::getConnection();
        
        // Atualiza a linha recém-criada
        $sql = "UPDATE agenda_compromissos SET google_event_id = :google_event_id WHERE id_lead = :id_lead";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([
            'google_event_id' => $googleEventId,
            'id_lead' => $idLead
        ]);
    }
}
