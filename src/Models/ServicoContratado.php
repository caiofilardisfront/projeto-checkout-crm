<?php

// Arquivo: /src/Models/ServicoContratado.php

namespace Src\Models;

use Config\Database;
use Exception;
use Src\Models\Lead;

class ServicoContratado
{
    /**
     * Registra os itens fragmentados do pacote de Alto Ticket quando a venda é fechada.
     */
    public static function registrarPacoteFechado(int $idLead): void
    {
        try {
            // 1. Conexão limpa com o Banco de Dados
            $pdo = Database::getConnection();

            // 2. Array interno com os serviços fixos listados na interface
            $servicos = [
                [
                    'nome' => 'Sistema CRM Comercial Completo',
                    'descricao' => 'Licença de uso do ecossistema de gestão, funil e automação via Mercado Pago.'
                ],
                [
                    'nome' => 'Treinamento SDR Especializado',
                    'descricao' => 'Sessão de treinamento, roteiro de vendas e implantação da cultura comercial.'
                ],
                [
                    'nome' => 'Setup de Manutenção e Hospedagem',
                    'descricao' => 'Configuração inicial de infraestrutura, SSL e hospedagem em nuvem.'
                ]
            ];

            // 3. Prepara a query de inserção (Blindagem contra SQL Injection)
            $sql = "INSERT INTO servicos_contratados (id_lead, nome_servico, descricao) 
                    VALUES (:id_lead, :nome_servico, :descricao)";

            $stmt = $pdo->prepare($sql);

            // 4. Itera (faz um loop) sobre o array e insere linha por linha no banco
            foreach ($servicos as $item) {
                $stmt->execute([
                    'id_lead'      => $idLead,
                    'nome_servico' => $item['nome'],
                    'descricao'    => $item['descricao']
                ]);
            }
        } catch (Exception $e) {
            // 5. Bloco de Proteção: Se o banco falhar, o sistema NÃO trava a tela do cliente 
            // nem devolve erro para o Mercado Pago. Apenas escrevemos no log silenciosamente.
            error_log("CRM-CHECKOUT SERVICOS ERROR: Falha ao gravar itens do Lead {$idLead} - " . $e->getMessage());
        }
    }

    /**
     * Extrai os serviços contratados de um Lead garantindo o Isolamento (RLS).
     */
    public static function getServicosPorLead(int $idLead, int $idUsuario): array
    {
        // 1. Trava RLS (Prevenção IDOR): Verifica se o Lead pertence ao Diretor logado.
        if (!Lead::checkAcessoLead($idLead, $idUsuario)) {
            throw new Exception("Violação de Segurança (RLS). Acesso negado para ler serviços deste lead.");
        }

        // 2. Busca os serviços na tabela de auditoria
        $pdo = Database::getConnection();
        $sql = "SELECT nome_servico, descricao FROM servicos_contratados WHERE id_lead = :id_lead";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id_lead' => $idLead]);

        return $stmt->fetchAll();
    }
}
