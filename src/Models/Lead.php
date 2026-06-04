<?php
// Arquivo: /src/Models/Lead.php

namespace Src\Models;

use Config\Database;
use PDO;

class Lead
{
    /**
     * Aplicação do Filtro SQL Dinâmico com Isolamento Estrito de Dados (RLS).
     * O INNER JOIN em atribuicao_leads garante a restrição inquebrável por Diretor/Equipe.
     */
    public static function getLeadsFiltrados(int $idUsuario, ?string $status = null, ?string $busca = null): array
    {
        $pdo = Database::getConnection();

        // 1. Cláusula Base (Regra de Ouro RLS obriga o JOIN para impedir IDOR)
        $sql = "SELECT L.id, L.nome_contato, L.nome_agencia, L.telefone, L.valor_proposta, L.status, L.origem, L.data_criacao 
                FROM leads L 
                INNER JOIN atribuicao_leads A ON L.id = A.id_lead 
                WHERE A.id_usuario = :id_usuario";
        
        $params = ['id_usuario' => $idUsuario];

        // 2. Filtro Opcional de Funil (Status)
        if (!empty($status)) {
            $sql .= " AND L.status = :status";
            $params['status'] = $status;
        }

        // 3. Filtro Opcional de Busca (Contato ou Agência) blindado contra SQL Injection
        if (!empty($busca)) {
            $sql .= " AND (L.nome_contato LIKE :busca OR L.nome_agencia LIKE :busca)";
            $params['busca'] = "%{$busca}%";
        }

        $sql .= " ORDER BY L.data_criacao DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Valida acesso estrito a uma linha (Row) específica antes de Updates ou Checkouts.
     */
    public static function checkAcessoLead(int $idLead, int $idUsuario): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT 1 FROM atribuicao_leads WHERE id_lead = :id_lead AND id_usuario = :id_usuario LIMIT 1");
        $stmt->execute(['id_lead' => $idLead, 'id_usuario' => $idUsuario]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Registra um novo vínculo de permissão (Delegação).
     */
    public static function delegarAtribuicao(int $idLead, int $idUsuarioDestino, bool $compartilhado = false): bool
    {
        $pdo = Database::getConnection();
        $sql = "INSERT INTO atribuicao_leads (id_lead, id_usuario, compartilhado) 
                VALUES (:id_lead, :id_usuario, :compartilhado) 
                ON DUPLICATE KEY UPDATE compartilhado = :compartilhado_update";
                
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            'id_lead' => $idLead,
            'id_usuario' => $idUsuarioDestino,
            'compartilhado' => (int) $compartilhado,
            'compartilhado_update' => (int) $compartilhado
        ]);
    }
}