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

    /**
     * Insere o Lead e o atribui imediatamente ao usuário (Regra RLS) usando Transação.
     */
    public static function criarLead(array $dados, int $idUsuario): bool
    {
        $pdo = Database::getConnection();

        try {
            // Inicia transação: garante que o lead só exista se a atribuição também for registrada
            $pdo->beginTransaction();

            // 1. Inserção do Lead [1]
            $sqlLead = "INSERT INTO leads (nome_contato, nome_agencia, telefone, valor_proposta, status, origem) 
                        VALUES (:nome_contato, :nome_agencia, :telefone, :valor_proposta, 'novo', :origem)";
            $stmtLead = $pdo->prepare($sqlLead);
            $stmtLead->execute([
                'nome_contato' => $dados['nome_contato'],
                'nome_agencia' => $dados['nome_agencia'],
                'telefone' => $dados['telefone'],
                'valor_proposta' => $dados['valor_proposta'],
                'origem' => $dados['origem']
            ]);

            $idLead = (int) $pdo->lastInsertId();

            // 2. Registro do Isolamento RLS [2]
            $sqlAtribuicao = "INSERT INTO atribuicao_leads (id_lead, id_usuario, compartilhado) 
                              VALUES (:id_lead, :id_usuario, 0)";
            $stmtAtribuicao = $pdo->prepare($sqlAtribuicao);
            $stmtAtribuicao->execute([
                'id_lead' => $idLead,
                'id_usuario' => $idUsuario
            ]);

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log("CRM-CHECKOUT CREATE LEAD ERROR: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Atualiza os dados do Lead garantindo o Isolamento (RLS).
     */
    public static function atualizarLead(int $idLead, array $dados, int $idUsuario): bool
    {
        if (!self::checkAcessoLead($idLead, $idUsuario)) {
            throw new \Exception("Violação de RLS: Acesso negado para edição.");
        }

        $pdo = Database::getConnection();
        $sql = "UPDATE leads SET nome_contato = :nome_contato, nome_agencia = :nome_agencia, 
                telefone = :telefone, valor_proposta = :valor_proposta 
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            'nome_contato' => $dados['nome_contato'],
            'nome_agencia' => $dados['nome_agencia'],
            'telefone'     => $dados['telefone'],
            'valor_proposta' => $dados['valor_proposta'],
            'id'           => $idLead
        ]);
    }

    /**
     * Deleta fisicamente o Lead e depende do ON DELETE CASCADE para limpar atribuições.
     */
    public static function deletarLead(int $idLead, int $idUsuario): bool
    {
        if (!self::checkAcessoLead($idLead, $idUsuario)) {
            throw new \Exception("Violação de RLS: Acesso negado para exclusão.");
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM leads WHERE id = :id");
        return $stmt->execute(['id' => $idLead]);
    }

    /**
     * Extrai métricas agregadas para o Dashboard garantindo Isolamento Estrito de Dados (RLS).
     */
    public static function getMetricasDashboard(int $idUsuario): array
    {
        $pdo = Database::getConnection();

        // Preparamos um array base para evitar erros no frontend caso o funil esteja vazio
        $metricas = [
            'faturamento' => 0.00,
            'funil' => [
                'novo' => 0,
                'em_negociacao' => 0,
                'aguardando_pagamento' => 0,
                'fechado' => 0,
                'perdido' => 0
            ],
            'contratos' => 0,
            'tempo_medio' => 0
        ];

        // 1. KPI FATURAMENTO: Soma o valor das propostas apenas de leads com status 'fechado'
        $sqlFaturamento = "SELECT COALESCE(SUM(L.valor_proposta), 0) 
                           FROM leads L 
                           INNER JOIN atribuicao_leads A ON L.id = A.id_lead 
                           WHERE A.id_usuario = :id_usuario AND L.status = 'fechado'";
        $stmt = $pdo->prepare($sqlFaturamento);
        $stmt->execute(['id_usuario' => $idUsuario]);
        $metricas['faturamento'] = (float) $stmt->fetchColumn();

        // 2. KPI FUNIL: Conta quantos leads existem em cada etapa (Agrupamento)
        $sqlFunil = "SELECT L.status, COUNT(L.id) as total 
                     FROM leads L 
                     INNER JOIN atribuicao_leads A ON L.id = A.id_lead 
                     WHERE A.id_usuario = :id_usuario 
                     GROUP BY L.status";
        $stmt = $pdo->prepare($sqlFunil);
        $stmt->execute(['id_usuario' => $idUsuario]);

        // O FETCH_KEY_PAIR transforma o resultado em um array no formato ['status' => quantidade]
        $funilData = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach ($funilData as $status => $total) {
            $metricas['funil'][$status] = (int) $total;
        }

        // 3. KPI CONTRATOS: Conta fisicamente quantos PDFs existem atrelados a estes leads
        $sqlContratos = "SELECT COUNT(C.id) 
                         FROM contratos C 
                         INNER JOIN leads L ON C.id_lead = L.id 
                         INNER JOIN atribuicao_leads A ON L.id = A.id_lead 
                         WHERE A.id_usuario = :id_usuario";
        $stmt = $pdo->prepare($sqlContratos);
        $stmt->execute(['id_usuario' => $idUsuario]);
        $metricas['contratos'] = (int) $stmt->fetchColumn();

        // 4. KPI TEMPO MÉDIO: Calcula a média de dias entre a criação do lead e o pagamento
        $sqlTempo = "SELECT COALESCE(AVG(DATEDIFF(P.data_pagamento, L.data_criacao)), 0) 
                     FROM pagamentos P 
                     INNER JOIN leads L ON P.id_lead = L.id 
                     INNER JOIN atribuicao_leads A ON L.id = A.id_lead 
                     WHERE A.id_usuario = :id_usuario";
        $stmt = $pdo->prepare($sqlTempo);
        $stmt->execute(['id_usuario' => $idUsuario]);
        $metricas['tempo_medio'] = (float) $stmt->fetchColumn();

        return $metricas;
    }
}
