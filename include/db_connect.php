<?php
// Carrega as configurações
$config = require 'config.php';

// Define as constantes de conexão
defined('DB_HOST') or define('DB_HOST', $config['DB_HOST']);
defined('DB_USER') or define('DB_USER', $config['DB_USER']);
defined('DB_PASSWORD') or define('DB_PASSWORD', $config['DB_PASSWORD']);
defined('DB_NAME') or define('DB_NAME', $config['DB_NAME']);

// Cria as variáveis de tabela
$table_usuarios                        = $config['table_usuarios'];
$table_concessionaria                  = $config['table_concessionaria'];
$table_prospectores                    = $config['table_prospectores'];
$table_tipo_atividade                  = $config['table_tipo_atividade'];
$table_atividades                      = $config['table_atividades'];
$table_leads                           = $config['table_leads'];
$table_usuario_concessionaria          = $config['table_usuario_concessionaria'];
$table_base_leads                      = $config['table_base_leads'];
$table_historico_status_base_leads     = $config['table_historico_status_base_leads'];

$dominioEvento     = "evento.dexp.online";
$nomeEvento     = "Evento DEXP Simple Dealers";
// $enderecoEvento     = "-";

try {
    // Conexão usando constantes definidas acima
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASSWORD);
    // Configurar o modo de erro do PDO para exceção
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Conexão falhou: " . $e->getMessage());
}
