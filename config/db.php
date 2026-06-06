<?php
// Arquivo: /config/db.php

namespace Config;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    /**
     * Bloqueia a instânciação direta e clonagem para garantir o padrão Singleton.
     */
    private function __construct() {}
    private function __clone() {}

    /**
     * Retorna a instância única da conexão PDO.
     *
     * @return PDO
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            // Variáveis de ambiente provenientes do arquivo .env com a sintaxe corrigida
            $host = getenv('DB_HOST') ?: 'localhost';
            $db   = getenv('DB_NAME') ?: 'u475511250_crm_checkout';
            $user = getenv('DB_USER') ?: 'u475511250_crm_checkout'; // ASPA CORRIGIDA
            $pass = getenv('DB_PASS') ?: '#F0rt&5252@!';
            $charset = 'utf8mb4';

            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

            // Opções críticas de segurança e performance
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lança exceções para captura global
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Retorna arrays associativos limpos
                PDO::ATTR_EMULATE_PREPARES   => false,                  // Desativa emulação para segurança contra SQL Injection
                PDO::ATTR_PERSISTENT         => false                   // Evita locks não intencionais em conexões persistentes
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                // Loga a string de erro real no servidor para o sysadmin, omitindo da interface
                error_log("CRM-CHECKOUT DB FATAL ERROR: " . $e->getMessage());
                
                // Retorna apenas JSON limpo conforme regra arquitetural
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Falha crítica na infraestrutura de dados. A equipe técnica foi notificada.'
                ]);
                exit;
            }
        }
        return self::$instance;
    }
}